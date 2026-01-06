<?php
/**
 * Modèle Notification
 * Gestion des notifications utilisateurs
 */

class Notification extends Model {
    protected $table = 'notifications';
    
    /**
     * Créer une notification
     */
    public function createNotification($userId, $type, $titre, $message, $lien = null) {
        return $this->insert([
            'user_id' => $userId,
            'type' => $type,
            'titre' => $titre,
            'message' => $message,
            'lien' => $lien,
            'lu' => 0
        ]);
    }
    
    /**
     * Récupérer les notifications d'un utilisateur
     */
    public function getByUserId($userId, $limit = 20) {
        return $this->findAll([
            'where' => 'user_id = ?',
            'params' => [$userId],
            'order' => 'date_creation DESC',
            'limit' => $limit
        ]);
    }
    
    /**
     * Récupérer les notifications non lues
     */
    public function getUnreadByUserId($userId) {
        return $this->findAll([
            'where' => 'user_id = ? AND lu = 0',
            'params' => [$userId],
            'order' => 'date_creation DESC'
        ]);
    }
    
    /**
     * Compter les notifications non lues
     */
    public function countUnreadByUserId($userId) {
        return $this->count([
            'where' => 'user_id = ? AND lu = 0',
            'params' => [$userId]
        ]);
    }
    
    /**
     * Marquer comme lue
     */
    public function markAsRead($id) {
        return $this->update($id, ['lu' => 1]);
    }
    
    /**
     * Marquer toutes comme lues
     */
    public function markAllAsRead($userId) {
        $sql = "UPDATE {$this->table} SET lu = 1 WHERE user_id = ? AND lu = 0";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$userId]);
    }
    
    /**
     * Supprimer les anciennes notifications (plus de 30 jours)
     */
    public function deleteOldNotifications($days = 30) {
        $sql = "DELETE FROM {$this->table} 
                WHERE date_creation < DATE_SUB(NOW(), INTERVAL ? DAY)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$days]);
    }
    
    /**
     * Récupérer les nouvelles notifications depuis une date
     */
    public function getNewNotifications($userId, $lastCheck) {
        $sql = "SELECT * FROM {$this->table}
                WHERE user_id = ? AND date_creation > ?
                ORDER BY date_creation DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId, $lastCheck]);
        return $stmt->fetchAll();
    }
    
    // === NOTIFICATIONS SPÉCIFIQUES ===
    
    /**
     * Notification de réservation validée
     */
    public function notifyReservationApproved($userId, $reservationId, $lieuNom) {
        return $this->createNotification(
            $userId,
            'reservation_validee',
            'Réservation validée',
            "Votre réservation pour {$lieuNom} a été validée par l'équipe.",
            "/user/reservation/{$reservationId}"
        );
    }
    
    /**
     * Notification de réservation refusée
     */
    public function notifyReservationRejected($userId, $reservationId, $lieuNom, $motif) {
        return $this->createNotification(
            $userId,
            'reservation_refusee',
            'Réservation refusée',
            "Votre réservation pour {$lieuNom} a été refusée. Motif : {$motif}",
            "/user/reservation/{$reservationId}"
        );
    }
    
    /**
     * Notification d'évaluation approuvée
     */
    public function notifyEvaluationApproved($userId, $lieuSlug, $lieuNom) {
        return $this->createNotification(
            $userId,
            'evaluation_approuvee',
            'Avis publié',
            "Votre avis sur {$lieuNom} a été approuvé et est maintenant visible.",
            "/lieu/{$lieuSlug}"
        );
    }
    
    /**
     * Notification d'évaluation rejetée
     */
    public function notifyEvaluationRejected($userId, $lieuNom) {
        return $this->createNotification(
            $userId,
            'evaluation_rejetee',
            'Avis rejeté',
            "Votre avis sur {$lieuNom} n'a pas été approuvé car il ne respecte pas nos conditions.",
            "/user/dashboard"
        );
    }
    
    /**
     * Notification de rappel de visite
     */
    public function notifyVisitReminder($userId, $reservationId, $lieuNom, $dateVisite) {
        return $this->createNotification(
            $userId,
            'rappel_visite',
            'Rappel de visite',
            "N'oubliez pas votre visite à {$lieuNom} prévue le {$dateVisite}.",
            "/user/reservation/{$reservationId}"
        );
    }
    
    /**
     * Notification générale
     */
    public function notifyGeneral($userId, $titre, $message, $lien = null) {
        return $this->createNotification(
            $userId,
            'general',
            $titre,
            $message,
            $lien
        );
    }
}
