<?php
/**
 * Contrôleur Guide
 * Gestion des guides PDF téléchargeables
 */

require_once BASE_PATH . '/config/config.php';
require_once BASE_PATH . '/config/database.php';

class GuideController extends Controller {
    
    private $guideModel;
    
    public function __construct() {
        $this->guideModel = new GuidePdf();
    }
    
    // Liste publique des guides
    public function index() {
        $type = $_GET['type'] ?? null;
        $search = $_GET['search'] ?? '';
        
        if ($search) {
            $guides = $this->guideModel->searchGuides($search);
        } else {
            $guides = $this->guideModel->getAllActifs($type);
        }
        
        $populaires = $this->guideModel->getPopulaires(5);
        $recents = $this->guideModel->getRecents(5);
        
        $this->view('guides/index', [
            'title' => 'Guides Touristiques PDF',
            'guides' => $guides,
            'populaires' => $populaires,
            'recents' => $recents,
            'selectedType' => $type,
            'search' => $search
        ]);
    }
    
    // Télécharger un guide
    public function telecharger($id) {
        $guide = $this->guideModel->getByIdWithDetails($id);
        
        if (!$guide || !$guide['actif']) {
            $this->setFlashMessage('error', 'Guide introuvable');
            $this->redirect('/guides');
            return;
        }
        
        $filePath = BASE_PATH . '/public/uploads/' . $guide['fichier'];
        
        if (!file_exists($filePath)) {
            $this->setFlashMessage('error', 'Fichier introuvable');
            $this->redirect('/guides');
            return;
        }
        
        // Incrémenter le compteur
        $this->guideModel->incrementTelechargements($id);
        
        // Enregistrer le téléchargement
        $userId = isLoggedIn() ? $_SESSION['user_id'] : null;
        $ipAddress = $_SERVER['REMOTE_ADDR'];
        $this->guideModel->enregistrerTelechargement($id, $userId, $ipAddress);
        
        // Forcer le téléchargement
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . basename($guide['fichier']) . '"');
        header('Content-Length: ' . filesize($filePath));
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');
        
        readfile($filePath);
        exit;
    }
    
    // ADMIN : Gestion des guides
    public function admin() {
        if (!isLoggedIn() || !isAdmin()) {
            $this->redirect('/login');
            return;
        }
        
        $guides = $this->guideModel->findAll(['order' => 'date_creation DESC']);
        $stats = $this->guideModel->getStats();
        
        $this->view('admin/guides/index', [
            'title' => 'Gestion des Guides PDF',
            'guides' => $guides,
            'stats' => $stats,
            'flash' => $this->getFlashMessage()
        ]);
    }
    
    // ADMIN : Ajouter un guide
    public function create() {
        if (!isLoggedIn() || !isAdmin()) {
            $this->redirect('/login');
            return;
        }
        
        $categorieModel = new Categorie();
        $lieuModel = new LieuTouristique();
        
        $categories = $categorieModel->findAll(['order' => 'nom ASC']);
        $lieux = $lieuModel->findAll(['where' => "statut = 'actif'", 'order' => 'nom ASC']);
        
        $this->view('admin/guides/create', [
            'title' => 'Ajouter un Guide PDF',
            'categories' => $categories,
            'lieux' => $lieux,
            'flash' => $this->getFlashMessage()
        ]);
    }
    
    // ADMIN : Enregistrer un guide
    public function store() {
        if (!isLoggedIn() || !isAdmin() || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/admin/guides');
            return;
        }
        
        $titre = trim($_POST['titre'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $type = $_POST['type'] ?? 'general';
        $langue = $_POST['langue'] ?? 'fr';
        $lieuId = $_POST['lieu_id'] ?? null;
        $categorieId = $_POST['categorie_id'] ?? null;
        
        // Validation
        if (empty($titre) || empty($_FILES['fichier']['name'])) {
            $this->setFlashMessage('error', 'Veuillez remplir tous les champs requis');
            $this->redirect('/admin/guides/create');
            return;
        }
        
        // Upload du fichier PDF
        $uploadResult = $this->uploadPdf($_FILES['fichier']);
        
        if (!$uploadResult['success']) {
            $this->setFlashMessage('error', $uploadResult['message']);
            $this->redirect('/admin/guides/create');
            return;
        }
        
        // Upload de la couverture (optionnel)
        $couverture = null;
        if (!empty($_FILES['couverture']['name'])) {
            $couvertureResult = $this->uploadImage($_FILES['couverture']);
            if ($couvertureResult['success']) {
                $couverture = $couvertureResult['path'];
            }
        }
        
        // Insérer le guide
        $guideId = $this->guideModel->insert([
            'titre' => $titre,
            'description' => $description,
            'fichier' => $uploadResult['path'],
            'taille_fichier' => $uploadResult['size'],
            'type' => $type,
            'langue' => $langue,
            'lieu_id' => $lieuId,
            'categorie_id' => $categorieId,
            'couverture' => $couverture,
            'actif' => true,
            'cree_par' => $_SESSION['user_id']
        ]);
        
        if ($guideId) {
            $this->setFlashMessage('success', 'Guide ajouté avec succès');
        } else {
            $this->setFlashMessage('error', 'Erreur lors de l\'ajout');
        }
        
        $this->redirect('/admin/guides');
    }
    
    // ADMIN : Supprimer un guide
    public function delete($id) {
        if (!isLoggedIn() || !isAdmin()) {
            $this->json(['success' => false], 403);
            return;
        }
        
        $guide = $this->guideModel->findById($id);
        
        if (!$guide) {
            $this->json(['success' => false, 'message' => 'Guide introuvable'], 404);
            return;
        }
        
        // Supprimer les fichiers physiques
        $filePath = BASE_PATH . '/public/uploads/' . $guide['fichier'];
        if (file_exists($filePath)) {
            unlink($filePath);
        }
        
        if ($guide['couverture']) {
            $couverturePath = BASE_PATH . '/public/uploads/' . $guide['couverture'];
            if (file_exists($couverturePath)) {
                unlink($couverturePath);
            }
        }
        
        // Supprimer de la BDD
        $result = $this->guideModel->delete($id);
        
        $this->json(['success' => $result]);
    }
    
    // Upload PDF
    private function uploadPdf($file) {
        $uploadDir = BASE_PATH . '/public/uploads/guides/';
        
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        // Vérifier le type
        if ($file['type'] !== 'application/pdf') {
            return ['success' => false, 'message' => 'Seuls les fichiers PDF sont acceptés'];
        }
        
        // Vérifier la taille (max 10 MB)
        if ($file['size'] > 10 * 1024 * 1024) {
            return ['success' => false, 'message' => 'Le fichier est trop volumineux (max 10 MB)'];
        }
        
        // Générer nom unique
        $extension = 'pdf';
        $newName = 'guide_' . uniqid() . '.' . $extension;
        $destination = $uploadDir . $newName;
        
        if (move_uploaded_file($file['tmp_name'], $destination)) {
            return [
                'success' => true,
                'path' => 'guides/' . $newName,
                'size' => $file['size']
            ];
        }
        
        return ['success' => false, 'message' => 'Erreur lors de l\'upload'];
    }
    
    // Upload image de couverture
    private function uploadImage($file) {
        $uploadDir = BASE_PATH . '/public/uploads/guides/couvertures/';
        
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        // Vérifier le type
        $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
        if (!in_array($file['type'], $allowedTypes)) {
            return ['success' => false];
        }
        
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $newName = 'couverture_' . uniqid() . '.' . $extension;
        $destination = $uploadDir . $newName;
        
        if (move_uploaded_file($file['tmp_name'], $destination)) {
            return ['success' => true, 'path' => 'guides/couvertures/' . $newName];
        }
        
        return ['success' => false];
    }
}
