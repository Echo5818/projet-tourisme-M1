<?php
/**
 * Modèle ChatConversation
 * Gestion des conversations du chat
 */

class ChatConversation extends Model {
    protected $table = 'chat_conversations';
    
    public function getByUserId($userId) {
        $sql = "SELECT c.*, 
                       u.prenom as user_prenom, u.nom as user_nom, u.photo_profil as user_photo,
                       a.prenom as admin_prenom, a.nom as admin_nom, a.photo_profil as admin_photo,
                       (SELECT COUNT(*) FROM chat_messages WHERE conversation_id = c.id AND lu = 0 AND expediteur_id != ?) as messages_non_lus,
                       (SELECT message FROM chat_messages WHERE conversation_id = c.id ORDER BY date_envoi DESC LIMIT 1) as dernier_message,
                       (SELECT date_envoi FROM chat_messages WHERE conversation_id = c.id ORDER BY date_envoi DESC LIMIT 1) as date_dernier_message
                FROM {$this->table} c
                INNER JOIN users u ON c.user_id = u.id
                LEFT JOIN users a ON c.admin_id = a.id
                WHERE c.user_id = ?
                ORDER BY c.derniere_activite DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId, $userId]);
        return $stmt->fetchAll();
    }
    
    public function getAllConversationsAdmin($statut = null) {
        $sql = "SELECT c.*, 
                       u.prenom as user_prenom, u.nom as user_nom, u.photo_profil as user_photo, u.email as user_email,
                       a.prenom as admin_prenom, a.nom as admin_nom,
                       (SELECT COUNT(*) FROM chat_messages WHERE conversation_id = c.id AND lu = 0 AND expediteur_id = c.user_id) as messages_non_lus,
                       (SELECT message FROM chat_messages WHERE conversation_id = c.id ORDER BY date_envoi DESC LIMIT 1) as dernier_message,
                       (SELECT date_envoi FROM chat_messages WHERE conversation_id = c.id ORDER BY date_envoi DESC LIMIT 1) as date_dernier_message
                FROM {$this->table} c
                INNER JOIN users u ON c.user_id = u.id
                LEFT JOIN users a ON c.admin_id = a.id";
        
        if ($statut) {
            $sql .= " WHERE c.statut = ?";
            $params = [$statut];
        } else {
            $params = [];
        }
        
        $sql .= " ORDER BY c.derniere_activite DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    public function getOrCreateConversation($userId, $adminId = null) {
        // Chercher une conversation ouverte
        $existing = $this->findOne([
            'where' => 'user_id = ? AND statut != ?',
            'params' => [$userId, 'fermee']
        ]);
        
        if ($existing) {
            return $existing['id'];
        }
        
        // Créer une nouvelle conversation
        return $this->insert([
            'user_id' => $userId,
            'admin_id' => $adminId,
            'statut' => 'ouverte'
        ]);
    }
    
    public function assignerAdmin($conversationId, $adminId) {
        return $this->update($conversationId, [
            'admin_id' => $adminId,
            'statut' => 'en_cours'
        ]);
    }
    
    public function fermerConversation($conversationId) {
        return $this->update($conversationId, [
            'statut' => 'fermee'
        ]);
    }
    
    public function rouvrir($conversationId) {
        return $this->update($conversationId, [
            'statut' => 'ouverte',
            'admin_id' => null
        ]);
    }
    
    public function getStats() {
        $sql = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN statut = 'ouverte' THEN 1 ELSE 0 END) as ouvertes,
                    SUM(CASE WHEN statut = 'en_cours' THEN 1 ELSE 0 END) as en_cours,
                    SUM(CASE WHEN statut = 'fermee' THEN 1 ELSE 0 END) as fermees,
                    SUM(CASE WHEN DATE(date_creation) = CURDATE() THEN 1 ELSE 0 END) as aujourdhui
                FROM {$this->table}";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetch();
    }
    
    public function getMessagesNonLus($userId) {
        $sql = "SELECT COUNT(*) as total
                FROM chat_messages cm
                INNER JOIN chat_conversations cc ON cm.conversation_id = cc.id
                WHERE cc.user_id = ? AND cm.expediteur_id != ? AND cm.lu = 0";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId, $userId]);
        $result = $stmt->fetch();
        return $result['total'];
    }
    
    /**
     * Récupérer les conversations pour l'admin avec détails
     */
    public function getAdminConversations($statut = 'ouverte') {
        $sql = "SELECT c.*, 
                       u.prenom as user_prenom, u.nom as user_nom, 
                       u.email as user_email, u.photo_profil as user_photo,
                       a.prenom as admin_prenom, a.nom as admin_nom,
                       (SELECT COUNT(*) FROM chat_messages 
                        WHERE conversation_id = c.id AND lu = 0 
                        AND expediteur_id = c.user_id) as messages_non_lus
                FROM {$this->table} c
                INNER JOIN users u ON c.user_id = u.id
                LEFT JOIN users a ON c.admin_id = a.id
                WHERE c.statut = ?
                ORDER BY c.derniere_activite DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$statut]);
        return $stmt->fetchAll();
    }
    
    /**
     * Récupérer les compteurs de conversations
     */
    public function getConversationCounts() {
        $sql = "SELECT 
                    SUM(CASE WHEN statut = 'ouverte' THEN 1 ELSE 0 END) as ouverte,
                    SUM(CASE WHEN statut = 'en_cours' THEN 1 ELSE 0 END) as en_cours,
                    SUM(CASE WHEN statut = 'fermee' THEN 1 ELSE 0 END) as fermee
                FROM {$this->table}";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetch();
    }
    
    /**
     * Récupérer une conversation avec les infos utilisateur
     */
    public function getConversationWithUser($conversationId) {
        $sql = "SELECT c.*, 
                       u.prenom as user_prenom, u.nom as user_nom, 
                       u.email as user_email, u.photo_profil as user_photo,
                       u.role as user_role
                FROM {$this->table} c
                INNER JOIN users u ON c.user_id = u.id
                WHERE c.id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$conversationId]);
        return $stmt->fetch();
    }
}
