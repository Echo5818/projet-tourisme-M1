<?php
/**
 * Contrôleur Notification
 * Gestion des notifications en temps réel
 */

require_once BASE_PATH . '/config/config.php';
require_once BASE_PATH . '/config/database.php';

class NotificationController extends Controller {
    
    private $db;
    
    public function __construct() {
        if (!isLoggedIn()) {
            $this->json(['success' => false], 401);
            exit;
        }
        
        $this->db = Database::getInstance()->getConnection();
    }
    
    // Récupérer toutes les notifications de l'utilisateur
    public function getAll() {
        $userId = $_SESSION['user_id'];
        $limit = intval($_GET['limit'] ?? 20);
        
        $sql = "SELECT * FROM notifications 
                WHERE user_id = ? 
                ORDER BY lu ASC, date_creation DESC 
                LIMIT ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId, $limit]);
        $notifications = $stmt->fetchAll();
        
        $this->json([
            'success' => true,
            'notifications' => $notifications
        ]);
    }
    
    // Compter les notifications non lues
    public function getUnreadCount() {
        $userId = $_SESSION['user_id'];
        
        $sql = "SELECT COUNT(*) as total 
                FROM notifications 
                WHERE user_id = ? 
                AND lu = 0";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        $result = $stmt->fetch();
        
        $this->json([
            'success' => true,
            'count' => $result['total']
        ]);
    }
    
    // Marquer une notification comme lue
    public function markAsRead() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false], 405);
            return;
        }
        
        $notificationId = $_POST['notification_id'] ?? 0;
        $userId = $_SESSION['user_id'];
        
        $sql = "UPDATE notifications 
                SET lu = 1 
                WHERE id = ? AND user_id = ?";
        
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute([$notificationId, $userId]);
        
        $this->json(['success' => $result]);
    }
    
    // Marquer toutes les notifications comme lues
    public function markAllAsRead() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false], 405);
            return;
        }
        
        $userId = $_SESSION['user_id'];
        
        $sql = "UPDATE notifications 
                SET lu = 1 
                WHERE user_id = ? AND lu = 0";
        
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute([$userId]);
        
        $this->json(['success' => $result]);
    }
    
    // Supprimer une notification
    public function delete() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false], 405);
            return;
        }
        
        $notificationId = $_POST['notification_id'] ?? 0;
        $userId = $_SESSION['user_id'];
        
        $sql = "DELETE FROM notifications 
                WHERE id = ? AND user_id = ?";
        
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute([$notificationId, $userId]);
        
        $this->json(['success' => $result]);
    }
    
    // Polling pour nouvelles notifications
    public function poll() {
        $userId = $_SESSION['user_id'];
        $lastCheck = $_GET['last_check'] ?? date('Y-m-d H:i:s', strtotime('-1 minute'));
        
        $sql = "SELECT * FROM notifications 
                WHERE user_id = ? 
                AND date_creation > ?
                ORDER BY date_creation DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId, $lastCheck]);
        $nouvelles = $stmt->fetchAll();
        
        $this->json([
            'success' => true,
            'notifications' => $nouvelles,
            'count' => count($nouvelles)
        ]);
    }
    
    // Créer une notification (interne)
    public static function creerNotification($userId, $type, $titre, $message, $options = []) {
        $db = Database::getInstance()->getConnection();
        
        $priority = $options['priority'] ?? 'normale';
        $icone = $options['icone'] ?? 'fa-bell';
        $couleur = $options['couleur'] ?? '#3b82f6';
        $lien = $options['lien'] ?? null;
        $actionUrl = $options['action_url'] ?? null;
        $actionTexte = $options['action_texte'] ?? null;
        $expireLe = $options['expire_le'] ?? null;
        
        $sql = "INSERT INTO notifications 
                (user_id, type, titre, message, priority, icone, couleur, lien, action_url, action_texte, expire_le) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $db->prepare($sql);
        return $stmt->execute([
            $userId, $type, $titre, $message, 
            $priority, $icone, $couleur, $lien, 
            $actionUrl, $actionTexte, $expireLe
        ]);
    }
    
    // Préférences de notifications
    public function getPreferences() {
        $userId = $_SESSION['user_id'];
        
        $sql = "SELECT * FROM notifications_preferences WHERE user_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        $preferences = $stmt->fetchAll();
        
        $this->json([
            'success' => true,
            'preferences' => $preferences
        ]);
    }
    
    // Mettre à jour les préférences
    public function updatePreferences() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false], 405);
            return;
        }
        
        $userId = $_SESSION['user_id'];
        $typeNotification = $_POST['type'] ?? '';
        $actif = isset($_POST['actif']) ? 1 : 0;
        $canal = $_POST['canal'] ?? 'tous';
        
        $sql = "INSERT INTO notifications_preferences (user_id, type_notification, actif, canal) 
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE actif = ?, canal = ?";
        
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute([
            $userId, $typeNotification, $actif, $canal,
            $actif, $canal
        ]);
        
        $this->json(['success' => $result]);
    }
    
    // Nettoyer les anciennes notifications
    public function cleanup() {
        if (!isAdmin()) {
            $this->json(['success' => false], 403);
            return;
        }
        
        // Supprimer les notifications lues de plus de 30 jours
        $sql = "DELETE FROM notifications 
                WHERE lu = 1 
                AND lu_le < DATE_SUB(NOW(), INTERVAL 30 DAY)";
        
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute();
        
        $affected = $stmt->rowCount();
        
        $this->json([
            'success' => true,
            'deleted' => $affected
        ]);
    }
}
