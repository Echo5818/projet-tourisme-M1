<?php
/**
 * Modèle Favori
 * Gestion des favoris des utilisateurs
 */

class Favori extends Model {
    protected $table = 'favoris';
    
    public function getByUserId($userId) {
        $sql = "SELECT f.*, l.nom, l.slug, l.description,
                       (SELECT chemin_image FROM images_lieux WHERE lieu_id = l.id AND est_principale = 1 LIMIT 1) as image_principale,
                       (SELECT AVG(note) FROM evaluations WHERE lieu_id = l.id AND statut = 'approuve') as note_moyenne,
                       (SELECT COUNT(*) FROM evaluations WHERE lieu_id = l.id AND statut = 'approuve') as nombre_avis
                FROM {$this->table} f
                INNER JOIN lieux_touristiques l ON f.lieu_id = l.id
                WHERE f.user_id = ? AND l.statut = 'actif'
                ORDER BY f.date_ajout DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }
    
    public function isFavorite($userId, $lieuId) {
        $favori = $this->findOne([
            'where' => 'user_id = ? AND lieu_id = ?',
            'params' => [$userId, $lieuId]
        ]);
        
        return $favori !== false;
    }
    
    public function addFavorite($userId, $lieuId) {
        // Vérifier si déjà en favoris
        if ($this->isFavorite($userId, $lieuId)) {
            return false;
        }
        
        return $this->insert([
            'user_id' => $userId,
            'lieu_id' => $lieuId
        ]);
    }
    
    public function removeFavorite($userId, $lieuId) {
        $sql = "DELETE FROM {$this->table} WHERE user_id = ? AND lieu_id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$userId, $lieuId]);
    }
    
    public function toggleFavorite($userId, $lieuId) {
        if ($this->isFavorite($userId, $lieuId)) {
            $this->removeFavorite($userId, $lieuId);
            return ['action' => 'removed', 'success' => true];
        } else {
            $result = $this->addFavorite($userId, $lieuId);
            return ['action' => 'added', 'success' => $result];
        }
    }
    
    public function countByUserId($userId) {
        return $this->count([
            'where' => 'user_id = ?',
            'params' => [$userId]
        ]);
    }
}
