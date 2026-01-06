<?php
/**
 * Modèle ChatMessage
 * Gestion des messages du chat
 */

class ChatMessage extends Model {
    protected $table = 'chat_messages';
    
    public function getByConversationId($conversationId, $limit = 50) {
        $sql = "SELECT m.*, 
                       u.prenom, u.nom, u.photo_profil, u.role
                FROM {$this->table} m
                INNER JOIN users u ON m.expediteur_id = u.id
                WHERE m.conversation_id = ?
                ORDER BY m.date_envoi ASC
                LIMIT ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$conversationId, $limit]);
        return $stmt->fetchAll();
    }
    
    public function envoyerMessage($conversationId, $expediteurId, $message, $type = 'texte', $fichier = null) {
        $messageId = $this->insert([
            'conversation_id' => $conversationId,
            'expediteur_id' => $expediteurId,
            'message' => $message,
            'type' => $type,
            'fichier' => $fichier,
            'lu' => false
        ]);
        
        // Mettre à jour la dernière activité de la conversation
        $conversationModel = new ChatConversation();
        $conversationModel->update($conversationId, [
            'derniere_activite' => date('Y-m-d H:i:s')
        ]);
        
        return $messageId;
    }
    
    public function marquerCommeLu($conversationId, $userId) {
        $sql = "UPDATE {$this->table} 
                SET lu = 1 
                WHERE conversation_id = ? 
                AND expediteur_id != ? 
                AND lu = 0";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$conversationId, $userId]);
    }
    
    public function getNouveauxMessages($conversationId, $dernierMessageId) {
        $sql = "SELECT m.*, 
                       u.prenom, u.nom, u.photo_profil, u.role
                FROM {$this->table} m
                INNER JOIN users u ON m.expediteur_id = u.id
                WHERE m.conversation_id = ? 
                AND m.id > ?
                ORDER BY m.date_envoi ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$conversationId, $dernierMessageId]);
        return $stmt->fetchAll();
    }
    
    public function getMessagesNonLus($userId) {
        $sql = "SELECT COUNT(*) as total
                FROM {$this->table} m
                INNER JOIN chat_conversations c ON m.conversation_id = c.id
                WHERE c.user_id = ? 
                AND m.expediteur_id != ? 
                AND m.lu = 0";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId, $userId]);
        $result = $stmt->fetch();
        return $result['total'];
    }
    
    public function supprimerMessage($messageId, $userId) {
        // Vérifier que l'utilisateur est l'expéditeur
        $message = $this->findById($messageId);
        
        if ($message && $message['expediteur_id'] == $userId) {
            return $this->delete($messageId);
        }
        
        return false;
    }
    
    public function getStatistiquesConversation($conversationId) {
        $sql = "SELECT 
                    COUNT(*) as total_messages,
                    SUM(CASE WHEN type = 'fichier' THEN 1 ELSE 0 END) as fichiers,
                    SUM(CASE WHEN type = 'image' THEN 1 ELSE 0 END) as images,
                    MIN(date_envoi) as premier_message,
                    MAX(date_envoi) as dernier_message
                FROM {$this->table}
                WHERE conversation_id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$conversationId]);
        return $stmt->fetch();
    }
    
    /**
     * Récupérer les messages avec infos utilisateur (pour admin)
     */
    public function getMessagesWithUser($conversationId) {
        $sql = "SELECT m.*, 
                       u.prenom as expediteur_prenom, 
                       u.nom as expediteur_nom,
                       u.role as expediteur_role,
                       u.photo_profil as expediteur_photo
                FROM {$this->table} m
                INNER JOIN users u ON m.expediteur_id = u.id
                WHERE m.conversation_id = ?
                ORDER BY m.date_envoi ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$conversationId]);
        return $stmt->fetchAll();
    }
}
