<?php
/**
 * Contrôleur ContenuController
 * Gestion des contenus dynamiques (admin)
 */

require_once BASE_PATH . '/models/ContenuDynamique.php';

class ContenuController extends Controller {
    private $contenuModel;
    
    public function __construct() {
        parent::__construct();
        $this->contenuModel = new ContenuDynamique();
        
        // Vérifier si l'utilisateur est admin
        if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'superadmin'])) {
            header('Location: /auth/login');
            exit;
        }
    }
    
    /**
     * Liste des contenus dynamiques
     */
    public function index() {
        $contenus = $this->contenuModel->getAllContenus();
        $availableKeys = $this->contenuModel->getAvailableKeys();
        
        $this->render('admin/contenu/index', [
            'contenus' => $contenus,
            'availableKeys' => $availableKeys
        ]);
    }
    
    /**
     * Afficher le formulaire d'édition d'un contenu
     */
    public function edit($cle = null) {
        if (!$cle) {
            $this->redirect('/admin/contenu');
        }
        
        $contenu = $this->contenuModel->getByCle($cle);
        $availableKeys = $this->contenuModel->getAvailableKeys();
        
        // Si le contenu n'existe pas, créer un nouveau
        if (!$contenu) {
            $contenu = [
                'cle' => $cle,
                'titre' => $availableKeys[$cle]['label'] ?? ucfirst(str_replace('_', ' ', $cle)),
                'contenu' => ''
            ];
        }
        
        $this->render('admin/contenu/edit', [
            'contenu' => $contenu,
            'keyInfo' => $availableKeys[$cle] ?? null
        ]);
    }
    
    /**
     * Sauvegarder un contenu
     */
    public function save() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/admin/contenu');
        }
        
        $cle = $_POST['cle'] ?? '';
        $titre = $_POST['titre'] ?? '';
        $contenu = $_POST['contenu'] ?? '';
        
        if (empty($cle) || empty($titre)) {
            $_SESSION['error'] = 'La clé et le titre sont obligatoires';
            $this->redirect('/admin/contenu/edit/' . $cle);
        }
        
        $success = $this->contenuModel->updateContenu($cle, $titre, $contenu);
        
        if ($success) {
            $_SESSION['success'] = 'Contenu mis à jour avec succès';
        } else {
            $_SESSION['error'] = 'Erreur lors de la mise à jour du contenu';
        }
        
        $this->redirect('/admin/contenu');
    }
    
    /**
     * Initialiser les contenus par défaut
     */
    public function initialize() {
        if ($_SESSION['user_role'] !== 'superadmin') {
            $_SESSION['error'] = 'Action réservée au superadmin';
            $this->redirect('/admin/contenu');
        }
        
        $this->contenuModel->initializeDefaults();
        $_SESSION['success'] = 'Contenus par défaut initialisés avec succès';
        $this->redirect('/admin/contenu');
    }
}
