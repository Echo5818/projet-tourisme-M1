<?php
/**
 * Contrôleur Admin - Dashboard et gestion
 */

require_once BASE_PATH . '/config/config.php';
require_once BASE_PATH . '/config/database.php';

class AdminController extends Controller {
    
    public function __construct() {
        // Vérifier l'authentification admin
        if (!isLoggedIn() || !isAdmin()) {
            header("Location: " . APP_URL . "/login");
            exit();
        }
    }
    
    // Dashboard admin avec graphiques
    public function dashboard() {
        $userModel = new User();
        $lieuModel = new LieuTouristique();
        $reservationModel = new Reservation();
        $evaluationModel = new Evaluation();
        $guideModel = new GuidePdf();
        
        // Statistiques
        $userStats = $userModel->getUserStats();
        $lieuStats = $lieuModel->getStats();
        $reservationStats = $reservationModel->getStats();
        $evaluationStats = $evaluationModel->getStats();
        $guideStats = $guideModel->getStats();
        
        // Données pour graphiques
        $monthlyReservations = $reservationModel->getMonthlyReservations();
        $popularDestinations = $reservationModel->getPopularDestinations(5);
        $recentReservations = $reservationModel->getPendingReservations(5);
        $recentEvaluations = $evaluationModel->getPendingEvaluations(5);
        $recentUsers = $userModel->getRecentUsers(5);
        
        // Nouvelles données pour graphiques améliorés
        $userGrowth = $userModel->getMonthlyGrowth();
        $ratingsDistribution = $evaluationModel->getRatingsDistribution();
        $conversionStats = [
            'visiteurs' => 1000, // À remplacer par de vraies stats si analytics disponible
            'inscrits' => $userStats['total'],
            'reservations' => $reservationStats['total']
        ];
        
        $this->view('admin/dashboard', [
            'title' => 'Dashboard Administrateur',
            'userStats' => $userStats,
            'lieuStats' => $lieuStats,
            'reservationStats' => $reservationStats,
            'evaluationStats' => $evaluationStats,
            'guideStats' => $guideStats,
            'monthlyReservations' => $monthlyReservations,
            'popularDestinations' => $popularDestinations,
            'recentReservations' => $recentReservations,
            'recentEvaluations' => $recentEvaluations,
            'recentUsers' => $recentUsers,
            'userGrowth' => $userGrowth,
            'ratingsDistribution' => $ratingsDistribution,
            'conversionStats' => $conversionStats,
            'flash' => $this->getFlashMessage()
        ]);
    }
    
    // Gestion des lieux - Liste
    public function lieux() {
        $lieuModel = new LieuTouristique();
        $categorieModel = new Categorie();
        
        $search = $_GET['search'] ?? '';
        $statut = $_GET['statut'] ?? '';
        
        $conditions = [];
        if ($search) {
            $conditions['where'] = "(l.nom LIKE '%{$search}%' OR l.description LIKE '%{$search}%')";
        }
        if ($statut) {
            $where = $conditions['where'] ?? '';
            $conditions['where'] = ($where ? $where . ' AND ' : '') . "l.statut = '{$statut}'";
        }
        
        $lieux = $lieuModel->getAllWithCategories($conditions);
        $categories = $categorieModel->findAll();
        
        $this->view('admin/lieux/index', [
            'title' => 'Gestion des Lieux',
            'lieux' => $lieux,
            'categories' => $categories,
            'flash' => $this->getFlashMessage()
        ]);
    }
    
    // Gestion des réservations
    public function reservations() {
        $reservationModel = new Reservation();
        
        $statut = $_GET['statut'] ?? '';
        
        $conditions = [];
        if ($statut) {
            $conditions['where'] = "r.statut = '{$statut}'";
        }
        
        $reservations = $reservationModel->getAllWithDetails($conditions);
        
        $this->view('admin/reservations/index', [
            'title' => 'Gestion des Réservations',
            'reservations' => $reservations,
            'selectedStatut' => $statut,
            'flash' => $this->getFlashMessage()
        ]);
    }
    
    // Approuver une réservation
    public function approveReservation($id) {
        $reservationModel = new Reservation();
        $notificationModel = new Notification();
        $lieuModel = new LieuTouristique();
        
        // Récupérer les détails de la réservation avant validation
        $reservation = $reservationModel->findById($id);
        
        if (!$reservation) {
            $this->json(['success' => false, 'message' => 'Réservation introuvable'], 404);
            return;
        }
        
        // Valider la réservation
        $result = $reservationModel->approveReservation($id, $_SESSION['user_id']);
        
        if ($result) {
            // Récupérer le nom du lieu
            $lieu = $lieuModel->findById($reservation['lieu_id']);
            
            // Créer une notification pour l'utilisateur
            $notificationModel->notifyReservationApproved(
                $reservation['user_id'],
                $id,
                $lieu['nom']
            );
            
            $this->json(['success' => true, 'message' => 'Réservation approuvée et utilisateur notifié']);
        } else {
            $this->json(['success' => false, 'message' => 'Erreur'], 500);
        }
    }
    
    // Refuser une réservation
    public function rejectReservation() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'message' => 'Méthode non autorisée'], 405);
            return;
        }
        
        $id = $_POST['reservation_id'] ?? 0;
        $motif = $_POST['motif'] ?? '';
        
        if (!$motif) {
            $this->json(['success' => false, 'message' => 'Le motif est requis'], 400);
            return;
        }
        
        $reservationModel = new Reservation();
        $notificationModel = new Notification();
        $lieuModel = new LieuTouristique();
        
        // Récupérer les détails de la réservation
        $reservation = $reservationModel->findById($id);
        
        if (!$reservation) {
            $this->json(['success' => false, 'message' => 'Réservation introuvable'], 404);
            return;
        }
        
        // Refuser la réservation
        $result = $reservationModel->rejectReservation($id, $motif, $_SESSION['user_id']);
        
        if ($result) {
            // Récupérer le nom du lieu
            $lieu = $lieuModel->findById($reservation['lieu_id']);
            
            // Créer une notification pour l'utilisateur
            $notificationModel->notifyReservationRejected(
                $reservation['user_id'],
                $id,
                $lieu['nom'],
                $motif
            );
            
            $this->json(['success' => true, 'message' => 'Réservation refusée et utilisateur notifié']);
        } else {
            $this->json(['success' => false, 'message' => 'Erreur'], 500);
        }
    }
    
    // Gestion des évaluations
    public function evaluations() {
        $evaluationModel = new Evaluation();
        
        $statut = $_GET['statut'] ?? 'en_attente';
        
        $sql = "SELECT e.*, u.nom, u.prenom, l.nom as lieu_nom, l.slug as lieu_slug
                FROM evaluations e
                INNER JOIN users u ON e.user_id = u.id
                INNER JOIN lieux_touristiques l ON e.lieu_id = l.id";
        
        if ($statut) {
            $sql .= " WHERE e.statut = '{$statut}'";
        }
        
        $sql .= " ORDER BY e.date_evaluation DESC";
        
        $evaluations = $evaluationModel->query($sql);
        
        $this->view('admin/evaluations/index', [
            'title' => 'Gestion des Évaluations',
            'evaluations' => $evaluations,
            'selectedStatut' => $statut,
            'flash' => $this->getFlashMessage()
        ]);
    }
    
    // Approuver évaluation
    public function approveEvaluation() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false], 405);
            return;
        }
        
        $id = $_POST['evaluation_id'] ?? 0;
        $reponse = $_POST['reponse'] ?? null;
        
        $evaluationModel = new Evaluation();
        $notificationModel = new Notification();
        $lieuModel = new LieuTouristique();
        
        // Récupérer les détails de l'évaluation
        $evaluation = $evaluationModel->findById($id);
        
        if ($evaluation) {
            // Approuver l'évaluation
            $result = $evaluationModel->approveEvaluation($id, $reponse);
            
            if ($result) {
                // Récupérer le lieu
                $lieu = $lieuModel->findById($evaluation['lieu_id']);
                
                // Notifier l'utilisateur
                $notificationModel->notifyEvaluationApproved(
                    $evaluation['user_id'],
                    $lieu['slug'],
                    $lieu['nom']
                );
            }
            
            $this->json(['success' => $result]);
        } else {
            $this->json(['success' => false]);
        }
    }
    
    // Rejeter évaluation
    public function rejectEvaluation() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false], 405);
            return;
        }
        
        $id = $_POST['evaluation_id'] ?? 0;
        $reponse = $_POST['reponse'] ?? null;
        
        $evaluationModel = new Evaluation();
        $notificationModel = new Notification();
        $lieuModel = new LieuTouristique();
        
        // Récupérer les détails de l'évaluation
        $evaluation = $evaluationModel->findById($id);
        
        if ($evaluation) {
            // Rejeter l'évaluation
            $result = $evaluationModel->rejectEvaluation($id, $reponse);
            
            if ($result) {
                // Récupérer le lieu
                $lieu = $lieuModel->findById($evaluation['lieu_id']);
                
                // Notifier l'utilisateur
                $notificationModel->notifyEvaluationRejected(
                    $evaluation['user_id'],
                    $lieu['nom']
                );
            }
            
            $this->json(['success' => $result]);
        } else {
            $this->json(['success' => false]);
        }
    }
    
    // Gestion des utilisateurs
    public function users() {
        $userModel = new User();
        $users = $userModel->findAll(['where' => "role = 'user'", 'order' => 'date_inscription DESC']);
        
        $this->view('admin/users/index', [
            'title' => 'Gestion des Utilisateurs',
            'users' => $users,
            'flash' => $this->getFlashMessage()
        ]);
    }
    
    // Gestion des admins (SuperAdmin uniquement)
    public function admins() {
        if (!isSuperAdmin()) {
            $this->setFlashMessage('error', 'Accès non autorisé');
            $this->redirect('/admin/dashboard');
            return;
        }
        
        $userModel = new User();
        $admins = $userModel->getAllAdmins();
        
        $this->view('admin/admins/index', [
            'title' => 'Gestion des Administrateurs',
            'admins' => $admins,
            'flash' => $this->getFlashMessage()
        ]);
    }
    
    // Gestion des sliders
    public function sliders() {
        $sliderModel = new Slider();
        $sliders = $sliderModel->getAllWithDetails();
        
        $this->view('admin/sliders/index', [
            'title' => 'Gestion des Sliders',
            'sliders' => $sliders,
            'flash' => $this->getFlashMessage()
        ]);
    }
}
