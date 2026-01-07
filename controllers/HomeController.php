<?php
/**
 * Contrôleur de la page d'accueil
 */

require_once BASE_PATH . '/config/config.php';
require_once BASE_PATH . '/config/database.php';

class HomeController extends Controller {
    
    private $lieuModel;
    private $sliderModel;
    private $categorieModel;
    
    public function __construct() {
        $this->lieuModel = new LieuTouristique();
        $this->sliderModel = new Slider();
        $this->categorieModel = new Categorie();
    }
    
    // Page d'accueil
    public function index() {
        $sliders = $this->sliderModel->getActiveSliders();
        $popularPlaces = $this->lieuModel->getPopularPlaces(8);
        $recentPlaces = $this->lieuModel->getRecentPlaces(6);
        $categories = $this->categorieModel->getAllWithCount();
        
        $this->view('home/index', [
            'title' => 'Bienvenue à Ndere City',
            'sliders' => $sliders,
            'popularPlaces' => $popularPlaces,
            'recentPlaces' => $recentPlaces,
            'categories' => $categories
        ]);
    }
    
    // Liste des lieux
    public function places() {
        $search = $_GET['search'] ?? '';
        $categorieId = $_GET['categorie'] ?? '';
        $gratuit = isset($_GET['gratuit']) ? 1 : null;
        
        $filters = [];
        if ($categorieId) $filters['categorie_id'] = $categorieId;
        if ($gratuit !== null) $filters['est_gratuit'] = $gratuit;
        
        $lieux = $this->lieuModel->searchPlaces($search, $filters);
        $categories = $this->categorieModel->getAllWithCount();
        
        $this->view('home/places', [
            'title' => 'Découvrir nos lieux',
            'lieux' => $lieux,
            'categories' => $categories,
            'search' => $search,
            'selectedCategorie' => $categorieId
        ]);
    }
    
    // Détails d'un lieu
    public function placeDetails($slug) {
        $lieu = $this->lieuModel->getBySlug($slug);
        
        if (!$lieu) {
            $this->setFlashMessage('error', 'Lieu introuvable');
            $this->redirect('/lieux');
            return;
        }
        
        // Incrémenter le nombre de visites
        $this->lieuModel->incrementVisites($lieu['id']);
        
        // Récupérer les images
        $imageModel = new ImageLieu();
        $images = $imageModel->getByLieuId($lieu['id']);
        
        // Récupérer les évaluations
        $evaluationModel = new Evaluation();
        $evaluations = $evaluationModel->getByLieuId($lieu['id'], 'approuve');
        $noteDistribution = $evaluationModel->getNoteDistribution($lieu['id']);
        
        // Vérifier si l'utilisateur a déjà évalué
        $userEvaluation = null;
        $isFavorite = false;
        if (isLoggedIn()) {
            $userEvaluation = $evaluationModel->getUserEvaluationForLieu($_SESSION['user_id'], $lieu['id']);
            $favoriModel = new Favori();
            $isFavorite = $favoriModel->isFavorite($_SESSION['user_id'], $lieu['id']);
        }
        
        $this->view('home/place-details', [
            'title' => $lieu['nom'],
            'lieu' => $lieu,
            'images' => $images,
            'evaluations' => $evaluations,
            'noteDistribution' => $noteDistribution,
            'userEvaluation' => $userEvaluation,
            'isFavorite' => $isFavorite
        ]);
    }
    
    // À propos
    public function about() {
        $this->view('home/about', [
            'title' => 'À propos de Ndere City'
        ]);
    }
    
    // Contact
    public function contact() {
        $this->view('home/contact', [
            'title' => 'Contactez-nous',
            'flash' => $this->getFlashMessage()
        ]);
    }
    
    // Traitement du formulaire de contact
    public function contactPost() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/contact');
            return;
        }
        
        $nom = trim($_POST['nom'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $telephone = trim($_POST['telephone'] ?? '');
        $objet = trim($_POST['objet'] ?? '');
        $message = trim($_POST['message'] ?? '');
        
        // Validation
        if (empty($nom) || empty($email) || empty($objet) || empty($message)) {
            $this->setFlashMessage('error', 'Veuillez remplir tous les champs obligatoires');
            $this->redirect('/contact');
            return;
        }
        
        // Enregistrer le message
        $messageModel = $this->model('Message');
        $userId = isLoggedIn() ? $_SESSION['user_id'] : null;
        
        $messageId = $messageModel->insert([
            'user_id' => $userId,
            'nom' => $nom,
            'email' => $email,
            'telephone' => $telephone,
            'objet' => $objet,
            'contenu' => $message,
            'statut' => 'non_lu'
        ]);
        
        if ($messageId) {
            $this->setFlashMessage('success', 'Votre message a été envoyé avec succès. Nous vous répondrons dans les plus brefs délais.');
        } else {
            $this->setFlashMessage('error', 'Erreur lors de l\'envoi du message');
        }
        
        $this->redirect('/contact');
    }
}
