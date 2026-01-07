<?php
/**
 * Contrôleur pour la gestion des lieux touristiques (Admin)
 */

require_once BASE_PATH . '/config/config.php';
require_once BASE_PATH . '/config/database.php';

class LieuController extends Controller {
    
    private $lieuModel;
    private $categorieModel;
    private $imageModel;
    
    public function __construct() {
        if (!isLoggedIn() || !isAdmin()) {
            header("Location: " . APP_URL . "/login");
            exit();
        }
        
        $this->lieuModel = new LieuTouristique();
        $this->categorieModel = new Categorie();
        $this->imageModel = new ImageLieu();
    }
    
    // Ajouter un lieu
    public function create() {
        $categories = $this->categorieModel->findAll(['order' => 'nom ASC']);
        
        $this->view('admin/lieux/create', [
            'title' => 'Ajouter un Lieu',
            'categories' => $categories,
            'flash' => $this->getFlashMessage()
        ]);
    }
    
    // Traitement ajout lieu
    public function store() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/admin/lieux');
            return;
        }
        
        $nom = trim($_POST['nom'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $categorieId = $_POST['categorie_id'] ?? null;
        $latitude = floatval($_POST['latitude'] ?? 0);
        $longitude = floatval($_POST['longitude'] ?? 0);
        $adresse = trim($_POST['adresse'] ?? '');
        $estGratuit = isset($_POST['est_gratuit']) ? 1 : 0;
        $tarif = $estGratuit ? 0 : floatval($_POST['tarif'] ?? 0);
        $horaires = trim($_POST['horaires_ouverture'] ?? '');
        $telephone = trim($_POST['contact_telephone'] ?? '');
        $email = trim($_POST['contact_email'] ?? '');
        
        // Validation
        if (empty($nom) || empty($description) || !$latitude || !$longitude) {
            $this->setFlashMessage('error', 'Veuillez remplir tous les champs obligatoires');
            $this->redirect('/admin/lieux/create');
            return;
        }
        
        // Vérifier qu'il y a au moins une image
        if (empty($_FILES['images']['name'][0])) {
            $this->setFlashMessage('error', 'Vous devez ajouter au moins une image');
            $this->redirect('/admin/lieux/create');
            return;
        }
        
        // Créer le slug
        $slug = $this->lieuModel->createSlug($nom);
        
        // Insérer le lieu
        $lieuId = $this->lieuModel->insert([
            'nom' => $nom,
            'slug' => $slug,
            'description' => $description,
            'categorie_id' => $categorieId,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'adresse' => $adresse,
            'tarif' => $tarif,
            'est_gratuit' => $estGratuit,
            'horaires_ouverture' => $horaires,
            'contact_telephone' => $telephone,
            'contact_email' => $email,
            'statut' => 'actif',
            'cree_par' => $_SESSION['user_id']
        ]);
        
        if ($lieuId) {
            // Upload des images
            $this->uploadImages($lieuId, $_FILES['images']);
            
            $this->setFlashMessage('success', 'Lieu ajouté avec succès');
            $this->redirect('/admin/lieux');
        } else {
            $this->setFlashMessage('error', 'Erreur lors de l\'ajout du lieu');
            $this->redirect('/admin/lieux/create');
        }
    }
    
    // Modifier un lieu
    public function edit($id) {
        $lieu = $this->lieuModel->findById($id);
        
        if (!$lieu) {
            $this->setFlashMessage('error', 'Lieu introuvable');
            $this->redirect('/admin/lieux');
            return;
        }
        
        $categories = $this->categorieModel->findAll(['order' => 'nom ASC']);
        $images = $this->imageModel->getByLieuId($id);
        
        $this->view('admin/lieux/edit', [
            'title' => 'Modifier le Lieu',
            'lieu' => $lieu,
            'categories' => $categories,
            'images' => $images,
            'flash' => $this->getFlashMessage()
        ]);
    }
    
    // Traitement modification lieu
    public function update($id) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/admin/lieux');
            return;
        }
        
        $lieu = $this->lieuModel->findById($id);
        if (!$lieu) {
            $this->setFlashMessage('error', 'Lieu introuvable');
            $this->redirect('/admin/lieux');
            return;
        }
        
        $nom = trim($_POST['nom'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $categorieId = $_POST['categorie_id'] ?? null;
        $latitude = floatval($_POST['latitude'] ?? 0);
        $longitude = floatval($_POST['longitude'] ?? 0);
        $adresse = trim($_POST['adresse'] ?? '');
        $estGratuit = isset($_POST['est_gratuit']) ? 1 : 0;
        $tarif = $estGratuit ? 0 : floatval($_POST['tarif'] ?? 0);
        $horaires = trim($_POST['horaires_ouverture'] ?? '');
        $telephone = trim($_POST['contact_telephone'] ?? '');
        $email = trim($_POST['contact_email'] ?? '');
        $statut = $_POST['statut'] ?? 'actif';
        
        // Si le nom change, créer nouveau slug
        $slug = $lieu['slug'];
        if ($nom !== $lieu['nom']) {
            $slug = $this->lieuModel->createSlug($nom);
        }
        
        $result = $this->lieuModel->update($id, [
            'nom' => $nom,
            'slug' => $slug,
            'description' => $description,
            'categorie_id' => $categorieId,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'adresse' => $adresse,
            'tarif' => $tarif,
            'est_gratuit' => $estGratuit,
            'horaires_ouverture' => $horaires,
            'contact_telephone' => $telephone,
            'contact_email' => $email,
            'statut' => $statut
        ]);
        
        // Upload nouvelles images si présentes
        if (!empty($_FILES['images']['name'][0])) {
            $this->uploadImages($id, $_FILES['images']);
        }
        
        if ($result) {
            $this->setFlashMessage('success', 'Lieu modifié avec succès');
        } else {
            $this->setFlashMessage('error', 'Erreur lors de la modification');
        }
        
        $this->redirect('/admin/lieux/edit/' . $id);
    }
    
    // Supprimer un lieu
    public function delete($id) {
        $lieu = $this->lieuModel->findById($id);
        
        if (!$lieu) {
            $this->json(['success' => false, 'message' => 'Lieu introuvable'], 404);
            return;
        }
        
        // Supprimer les images physiques
        $this->imageModel->deleteByLieuId($id);
        
        // Supprimer le lieu
        $result = $this->lieuModel->delete($id);
        
        if ($result) {
            $this->json(['success' => true, 'message' => 'Lieu supprimé']);
        } else {
            $this->json(['success' => false, 'message' => 'Erreur'], 500);
        }
    }
    
    // Supprimer une image
    public function deleteImage($imageId) {
        $image = $this->imageModel->findById($imageId);
        
        if (!$image) {
            $this->json(['success' => false], 404);
            return;
        }
        
        // Supprimer le fichier physique
        $filePath = BASE_PATH . '/public/uploads/lieux/' . basename($image['chemin_image']);
        if (file_exists($filePath)) {
            unlink($filePath);
        }
        
        $result = $this->imageModel->delete($imageId);
        $this->json(['success' => $result]);
    }
    
    // Définir image principale
    public function setPrincipale() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false], 405);
            return;
        }
        
        $imageId = $_POST['image_id'] ?? 0;
        $lieuId = $_POST['lieu_id'] ?? 0;
        
        $result = $this->imageModel->setPrincipale($imageId, $lieuId);
        $this->json(['success' => $result]);
    }
    
    // Upload des images
    private function uploadImages($lieuId, $files) {
        $uploadDir = BASE_PATH . '/public/uploads/lieux/';
        
        // Créer le dossier s'il n'existe pas
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $uploaded = 0;
        $totalFiles = count($files['name']);
        
        for ($i = 0; $i < $totalFiles && $i < MAX_IMAGES_PER_PLACE; $i++) {
            if ($files['error'][$i] === UPLOAD_ERR_OK) {
                $tmpName = $files['tmp_name'][$i];
                $originalName = $files['name'][$i];
                $fileSize = $files['size'][$i];
                $fileType = $files['type'][$i];
                
                // Vérifier le type
                if (!in_array($fileType, ALLOWED_IMAGE_TYPES)) {
                    continue;
                }
                
                // Vérifier la taille
                if ($fileSize > MAX_FILE_SIZE) {
                    continue;
                }
                
                // Générer nom unique
                $extension = pathinfo($originalName, PATHINFO_EXTENSION);
                $newName = uniqid('lieu_' . $lieuId . '_') . '.' . $extension;
                $destination = $uploadDir . $newName;
                
                if (move_uploaded_file($tmpName, $destination)) {
                    // Enregistrer en BDD
                    $this->imageModel->insert([
                        'lieu_id' => $lieuId,
                        'chemin_image' => 'public/uploads/lieux/' . $newName,
                        'ordre' => $i,
                        'est_principale' => ($uploaded === 0) ? 1 : 0
                    ]);
                    
                    $uploaded++;
                }
            }
        }
        
        return $uploaded;
    }
}
