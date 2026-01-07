<?php
/**
 * Contrôleur d'authentification
 * Gestion de l'inscription, connexion, déconnexion
 */

require_once BASE_PATH . '/config/config.php';
require_once BASE_PATH . '/config/database.php';

class AuthController extends Controller {
    
    private $userModel;
    
    public function __construct() {
        $this->userModel = new User();
    }
    
    // Afficher le formulaire de connexion
    public function login() {
        if (isLoggedIn()) {
            if (isAdmin()) {
                $this->redirect('/admin/dashboard');
            } else {
                $this->redirect('/user/dashboard');
            }
            return;
        }
        
        $this->view('auth/login', [
            'title' => 'Connexion',
            'flash' => $this->getFlashMessage()
        ]);
    }
    
    // Traitement de la connexion
    public function loginPost() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/login');
            return;
        }
        
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        $remember = isset($_POST['remember']);
        
        // Validation
        if (empty($email) || empty($password)) {
            $this->setFlashMessage('error', 'Veuillez remplir tous les champs');
            $this->redirect('/login');
            return;
        }
        
        // Vérification des identifiants
        $user = $this->userModel->verifyPassword($email, $password);
        
        if ($user) {
            // Vérifier le statut du compte
            if ($user['statut'] !== 'actif') {
                $this->setFlashMessage('error', 'Votre compte a été désactivé');
                $this->redirect('/login');
                return;
            }
            
            // Créer la session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_nom'] = $user['nom'];
            $_SESSION['user_prenom'] = $user['prenom'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_photo'] = $user['photo_profil'];
            
            // Mettre à jour la dernière connexion
            $this->userModel->updateLastLogin($user['id']);
            
            // Redirection selon le rôle
            if (in_array($user['role'], ['admin', 'superadmin'])) {
                $this->redirect('/admin/dashboard');
            } else {
                $this->redirect('/user/dashboard');
            }
        } else {
            $this->setFlashMessage('error', 'Email ou mot de passe incorrect');
            $this->redirect('/login');
        }
    }
    
    // Afficher le formulaire d'inscription
    public function register() {
        if (isLoggedIn()) {
            $this->redirect('/user/dashboard');
            return;
        }
        
        $this->view('auth/register', [
            'title' => 'Inscription',
            'flash' => $this->getFlashMessage()
        ]);
    }
    
    // Traitement de l'inscription
    public function registerPost() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/register');
            return;
        }
        
        $nom = trim($_POST['nom'] ?? '');
        $prenom = trim($_POST['prenom'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $telephone = trim($_POST['telephone'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        // Validation
        $errors = [];
        
        if (empty($nom)) $errors[] = 'Le nom est requis';
        if (empty($prenom)) $errors[] = 'Le prénom est requis';
        if (empty($email)) $errors[] = 'L\'email est requis';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email invalide';
        if (empty($password)) $errors[] = 'Le mot de passe est requis';
        if (strlen($password) < 6) $errors[] = 'Le mot de passe doit contenir au moins 6 caractères';
        if ($password !== $confirmPassword) $errors[] = 'Les mots de passe ne correspondent pas';
        
        // Vérifier si l'email existe déjà
        if ($this->userModel->findByEmail($email)) {
            $errors[] = 'Cet email est déjà utilisé';
        }
        
        if (!empty($errors)) {
            $this->setFlashMessage('error', implode('<br>', $errors));
            $this->redirect('/register');
            return;
        }
        
        // Créer l'utilisateur
        $userId = $this->userModel->createUser([
            'nom' => $nom,
            'prenom' => $prenom,
            'email' => $email,
            'telephone' => $telephone,
            'mot_de_passe' => $password,
            'role' => 'user',
            'statut' => 'actif',
            'email_verifie' => true // Simplification, à améliorer avec email de vérification
        ]);
        
        if ($userId) {
            $this->setFlashMessage('success', 'Inscription réussie ! Vous pouvez maintenant vous connecter');
            $this->redirect('/login');
        } else {
            $this->setFlashMessage('error', 'Erreur lors de l\'inscription');
            $this->redirect('/register');
        }
    }
    
    // Déconnexion
    public function logout() {
        session_destroy();
        $this->redirect('/');
    }
    
    // Mot de passe oublié
    public function forgotPassword() {
        $this->view('auth/forgot-password', [
            'title' => 'Mot de passe oublié',
            'flash' => $this->getFlashMessage()
        ]);
    }
    
    // Traitement mot de passe oublié
    public function forgotPasswordPost() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/forgot-password');
            return;
        }
        
        $email = trim($_POST['email'] ?? '');
        
        if (empty($email)) {
            $this->setFlashMessage('error', 'Veuillez entrer votre email');
            $this->redirect('/forgot-password');
            return;
        }
        
        $user = $this->userModel->findByEmail($email);
        
        if ($user) {
            // Générer un token
            $token = bin2hex(random_bytes(32));
            $this->userModel->setResetToken($email, $token);
            
            // TODO: Envoyer l'email avec le lien de réinitialisation
            // Pour l'instant, afficher le message
            $this->setFlashMessage('success', 'Un email de réinitialisation vous a été envoyé');
        } else {
            // Pour la sécurité, on affiche le même message même si l'email n'existe pas
            $this->setFlashMessage('success', 'Si cet email existe, un lien de réinitialisation vous a été envoyé');
        }
        
        $this->redirect('/login');
    }
}
