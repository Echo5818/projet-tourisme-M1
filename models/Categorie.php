<?php
/**
 * Modèle Categorie
 * Gestion des catégories de lieux
 */

class Categorie extends Model {
    protected $table = 'categories';
    
    public function getAllWithCount() {
        $sql = "SELECT c.*, COUNT(l.id) as nombre_lieux
                FROM {$this->table} c
                LEFT JOIN lieux_touristiques l ON c.id = l.categorie_id AND l.statut = 'actif'
                GROUP BY c.id
                ORDER BY c.ordre ASC, c.nom ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    public function getBySlug($slug) {
        return $this->findOne([
            'where' => 'slug = ?',
            'params' => [$slug]
        ]);
    }
}
