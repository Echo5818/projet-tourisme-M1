<?php
/**
 * Contrôleur utilisateur
 * Espace utilisateur, profil, réservations, favoris
 */

require_once BASE_PATH . '/config/config.php';
require_once BASE_PATH . '/config/database.php';

class UserController extends Controller {
    
    private $userModel;
    private $reservationModel;
    private $favoriModel;
    private $evaluationModel;
    
    public function __construct() {
        // Vérifier l'authentification
        if (!isLoggedIn() || isAdmin()) {
            // Si c'est une requête AJAX, renvoyer JSON
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                http_response_code(401);
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Non authentifié. Veuillez vous connecter.']);
                exit();
            }
            // Sinon, rediriger
            header("Location: " . APP_URL . "/login");
            exit();
        }
        
        $this->userModel = new User();
        $this->reservationModel = new Reservation();
        $this->favoriModel = new Favori();
        $this->evaluationModel = new Evaluation();
    }
    
    // Dashboard utilisateur
    public function dashboard() {
        $userId = $_SESSION['user_id'];
        
        $reservations = $this->reservationModel->getByUserId($userId);
        $favoris = $this->favoriModel->getByUserId($userId);
        $evaluations = $this->evaluationModel->getByUserId($userId);
        
        $stats = [
            'reservations_total' => count($reservations),
            'reservations_en_attente' => count(array_filter($reservations, fn($r) => $r['statut'] === 'en_attente')),
            'favoris_total' => count($favoris),
            'evaluations_total' => count($evaluations)
        ];
        
        $this->view('user/dashboard', [
            'title' => 'Mon Espace',
            'reservations' => array_slice($reservations, 0, 5),
            'favoris' => array_slice($favoris, 0, 6),
            'stats' => $stats,
            'flash' => $this->getFlashMessage()
        ]);
    }
    
    // Mes réservations
    public function reservations() {
        $userId = $_SESSION['user_id'];
        $statut = $_GET['statut'] ?? '';
        
        $reservations = $this->reservationModel->getByUserId($userId, $statut ?: null);
        
        $this->view('user/reservations', [
            'title' => 'Mes Réservations',
            'reservations' => $reservations,
            'selectedStatut' => $statut,
            'flash' => $this->getFlashMessage()
        ]);
    }
    
    // Détails d'une réservation
    public function reservationDetails($id) {
        $reservation = $this->reservationModel->findById($id);
        
        if (!$reservation || $reservation['user_id'] != $_SESSION['user_id']) {
            $this->setFlashMessage('error', 'Réservation introuvable');
            $this->redirect('/user/reservations');
            return;
        }
        
        $lieuModel = new LieuTouristique();
        $lieu = $lieuModel->getByIdWithDetails($reservation['lieu_id']);
        
        $this->view('user/reservation-details', [
            'title' => 'Détails de la réservation',
            'reservation' => $reservation,
            'lieu' => $lieu
        ]);
    }
    
    // Annuler une réservation
    public function cancelReservation($id) {
        $reservation = $this->reservationModel->findById($id);
        
        if (!$reservation || $reservation['user_id'] != $_SESSION['user_id']) {
            $this->json(['success' => false, 'message' => 'Réservation introuvable'], 404);
            return;
        }
        
        if (!in_array($reservation['statut'], ['en_attente', 'validee'])) {
            $this->json(['success' => false, 'message' => 'Cette réservation ne peut pas être annulée'], 400);
            return;
        }
        
        $result = $this->reservationModel->cancelReservation($id);
        
        if ($result) {
            $this->json(['success' => true, 'message' => 'Réservation annulée avec succès']);
        } else {
            $this->json(['success' => false, 'message' => 'Erreur lors de l\'annulation'], 500);
        }
    }
    
    // Nouvelle réservation
    public function createReservation() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'message' => 'Méthode non autorisée'], 405);
            return;
        }
        
        $userId = $_SESSION['user_id'];
        $lieuId = $_POST['lieu_id'] ?? 0;
        $dateVisite = $_POST['date_visite'] ?? '';
        $heureDepart = $_POST['heure_depart'] ?? '';
        $moyenTransport = $_POST['moyen_transport'] ?? 'taxi';
        $nombrePersonnes = intval($_POST['nombre_personnes'] ?? 1);
        $pointDepartLat = $_POST['point_depart_lat'] ?? null;
        $pointDepartLng = $_POST['point_depart_lng'] ?? null;
        $pointDepartAdresse = $_POST['point_depart_adresse'] ?? '';
        $distance = floatval($_POST['distance'] ?? 0);
        $dureeTrajet = intval($_POST['duree_trajet'] ?? 0);
        $coutEstime = floatval($_POST['cout_estime'] ?? 0);
        $notes = $_POST['notes'] ?? '';
        
        // Validation
        if (empty($lieuId) || empty($dateVisite)) {
            $this->json(['success' => false, 'message' => 'Données incomplètes'], 400);
            return;
        }
        
        // Vérifier la date
        if (strtotime($dateVisite) < strtotime('today')) {
            $this->json(['success' => false, 'message' => 'La date de visite doit être dans le futur'], 400);
            return;
        }
        
        // Vérifier le nombre de réservations par jour
        $maxReservations = 3; // À récupérer des paramètres
        $currentReservations = $this->reservationModel->getUserReservationCount($userId, $dateVisite);
        
        if ($currentReservations >= $maxReservations) {
            $this->json(['success' => false, 'message' => 'Vous avez atteint le nombre maximum de réservations pour ce jour'], 400);
            return;
        }
        
        // Créer la réservation
        $reservationId = $this->reservationModel->insert([
            'lieu_id' => $lieuId,
            'user_id' => $userId,
            'date_visite' => $dateVisite,
            'heure_depart' => $heureDepart,
            'moyen_transport' => $moyenTransport,
            'point_depart_lat' => $pointDepartLat,
            'point_depart_lng' => $pointDepartLng,
            'point_depart_adresse' => $pointDepartAdresse,
            'distance' => $distance,
            'duree_trajet' => $dureeTrajet,
            'cout_estime' => $coutEstime,
            'nombre_personnes' => $nombrePersonnes,
            'notes_utilisateur' => $notes,
            'statut' => 'en_attente'
        ]);
        
        if ($reservationId) {
            // TODO: Envoyer email de confirmation
            $this->json(['success' => true, 'message' => 'Réservation créée avec succès', 'reservation_id' => $reservationId]);
        } else {
            $this->json(['success' => false, 'message' => 'Erreur lors de la création de la réservation'], 500);
        }
    }
    
    // Mes favoris
    public function favoris() {
        $userId = $_SESSION['user_id'];
        $favoris = $this->favoriModel->getByUserId($userId);
        
        $this->view('user/favoris', [
            'title' => 'Mes Favoris',
            'favoris' => $favoris,
            'flash' => $this->getFlashMessage()
        ]);
    }
    
    // Toggle favori (AJAX)
    public function toggleFavori() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'message' => 'Méthode non autorisée'], 405);
            return;
        }
        
        $userId = $_SESSION['user_id'];
        $lieuId = $_POST['lieu_id'] ?? 0;
        
        if (!$lieuId) {
            $this->json(['success' => false, 'message' => 'Lieu non spécifié'], 400);
            return;
        }
        
        $result = $this->favoriModel->toggleFavorite($userId, $lieuId);
        
        if ($result['success']) {
            $message = $result['action'] === 'added' ? 'Ajouté aux favoris' : 'Retiré des favoris';
            $this->json(['success' => true, 'action' => $result['action'], 'message' => $message]);
        } else {
            $this->json(['success' => false, 'message' => 'Erreur lors de l\'opération'], 500);
        }
    }
    
    // Ajouter/Modifier une évaluation
    public function addEvaluation() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'message' => 'Méthode non autorisée'], 405);
            return;
        }
        
        $userId = $_SESSION['user_id'];
        $lieuId = $_POST['lieu_id'] ?? 0;
        $note = intval($_POST['note'] ?? 0);
        $commentaire = trim($_POST['commentaire'] ?? '');
        
        // Validation
        if (!$lieuId || $note < 1 || $note > 5) {
            $this->json(['success' => false, 'message' => 'Données invalides'], 400);
            return;
        }
        
        // Vérifier si l'utilisateur a déjà évalué
        $existingEvaluation = $this->evaluationModel->getUserEvaluationForLieu($userId, $lieuId);
        
        if ($existingEvaluation) {
            // Mise à jour
            $result = $this->evaluationModel->update($existingEvaluation['id'], [
                'note' => $note,
                'commentaire' => $commentaire,
                'statut' => 'en_attente' // Remettre en attente pour modération
            ]);
            $message = 'Évaluation mise à jour avec succès';
        } else {
            // Création
            $result = $this->evaluationModel->insert([
                'lieu_id' => $lieuId,
                'user_id' => $userId,
                'note' => $note,
                'commentaire' => $commentaire,
                'statut' => 'en_attente'
            ]);
            $message = 'Évaluation ajoutée avec succès';
        }
        
        if ($result) {
            $this->json(['success' => true, 'message' => $message]);
        } else {
            $this->json(['success' => false, 'message' => 'Erreur lors de l\'enregistrement'], 500);
        }
    }
    
    // Mon profil
    public function profile() {
        $userId = $_SESSION['user_id'];
        $user = $this->userModel->findById($userId);
        
        $this->view('user/profile', [
            'title' => 'Mon Profil',
            'user' => $user,
            'flash' => $this->getFlashMessage()
        ]);
    }
    
    // Mise à jour du profil
    public function updateProfile() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/user/profile');
            return;
        }
        
        $userId = $_SESSION['user_id'];
        $nom = trim($_POST['nom'] ?? '');
        $prenom = trim($_POST['prenom'] ?? '');
        $telephone = trim($_POST['telephone'] ?? '');
        
        if (empty($nom) || empty($prenom)) {
            $this->setFlashMessage('error', 'Le nom et le prénom sont requis');
            $this->redirect('/user/profile');
            return;
        }
        
        $result = $this->userModel->update($userId, [
            'nom' => $nom,
            'prenom' => $prenom,
            'telephone' => $telephone
        ]);
        
        if ($result) {
            $_SESSION['user_nom'] = $nom;
            $_SESSION['user_prenom'] = $prenom;
            $this->setFlashMessage('success', 'Profil mis à jour avec succès');
        } else {
            $this->setFlashMessage('error', 'Erreur lors de la mise à jour');
        }
        
        $this->redirect('/user/profile');
    }
    
    // Changer le mot de passe
    public function changePassword() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'message' => 'Méthode non autorisée'], 405);
            return;
        }
        
        $userId = $_SESSION['user_id'];
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        // Validation
        if (empty($currentPassword) || empty($newPassword)) {
            $this->json(['success' => false, 'message' => 'Tous les champs sont requis'], 400);
            return;
        }
        
        if (strlen($newPassword) < 6) {
            $this->json(['success' => false, 'message' => 'Le mot de passe doit contenir au moins 6 caractères'], 400);
            return;
        }
        
        if ($newPassword !== $confirmPassword) {
            $this->json(['success' => false, 'message' => 'Les mots de passe ne correspondent pas'], 400);
            return;
        }
        
        // Vérifier le mot de passe actuel
        $user = $this->userModel->findById($userId);
        if (!password_verify($currentPassword, $user['mot_de_passe'])) {
            $this->json(['success' => false, 'message' => 'Mot de passe actuel incorrect'], 400);
            return;
        }
        
        // Mettre à jour le mot de passe
        $result = $this->userModel->changePassword($userId, $newPassword);
        
        if ($result) {
            $this->json(['success' => true, 'message' => 'Mot de passe changé avec succès']);
        } else {
            $this->json(['success' => false, 'message' => 'Erreur lors du changement de mot de passe'], 500);
        }
    }
}
