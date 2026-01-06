<?php
/**
 * Modèle Message
 * Gestion des messages de contact
 */

class Message extends Model {
    protected $table = 'messages';
    
    public function getAllWithUsers() {
        $sql = "SELECT m.*, u.nom as user_nom, u.prenom as user_prenom
                FROM {$this->table} m
                LEFT JOIN users u ON m.user_id = u.id
                ORDER BY m.date_envoi DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    public function getUnread() {
        return $this->findAll([
            'where' => "statut = 'non_lu'",
            'order' => 'date_envoi DESC'
        ]);
    }
    
    public function markAsRead($id) {
        return $this->update($id, ['statut' => 'lu']);
    }
    
    public function reply($id, $reponse, $adminId) {
        return $this->update($id, [
            'reponse' => $reponse,
            'statut' => 'traite',
            'date_reponse' => date('Y-m-d H:i:s'),
            'repondu_par' => $adminId
        ]);
    }
}
