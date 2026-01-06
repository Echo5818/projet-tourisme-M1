<?php
/**
 * Modèle LieuTouristique
 * Gestion des lieux touristiques
 */

class LieuTouristique extends Model {
    protected $table = 'lieux_touristiques';
    
    public function getAllWithCategories($conditions = []) {
        $sql = "SELECT l.*, c.nom as categorie_nom, c.icone as categorie_icone,
                       (SELECT chemin_image FROM images_lieux WHERE lieu_id = l.id AND est_principale = 1 LIMIT 1) as image_principale,
                       (SELECT COUNT(*) FROM images_lieux WHERE lieu_id = l.id) as nombre_images,
                       (SELECT AVG(note) FROM evaluations WHERE lieu_id = l.id AND statut = 'approuve') as note_moyenne,
                       (SELECT COUNT(*) FROM evaluations WHERE lieu_id = l.id AND statut = 'approuve') as nombre_avis,
                       (SELECT COUNT(*) FROM favoris WHERE lieu_id = l.id) as nombre_favoris
                FROM {$this->table} l
                LEFT JOIN categories c ON l.categorie_id = c.id";
        
        if (!empty($conditions['where'])) {
            $sql .= " WHERE " . $conditions['where'];
        } else {
            $sql .= " WHERE l.statut = 'actif'";
        }
        
        if (!empty($conditions['order'])) {
            $sql .= " ORDER BY " . $conditions['order'];
        } else {
            $sql .= " ORDER BY l.popularite DESC, l.date_creation DESC";
        }
        
        if (!empty($conditions['limit'])) {
            $sql .= " LIMIT " . $conditions['limit'];
        }
        
        $stmt = $this->db->prepare($sql);
        
        if (!empty($conditions['params'])) {
            $stmt->execute($conditions['params']);
        } else {
            $stmt->execute();
        }
        
        return $stmt->fetchAll();
    }
    
    public function getByIdWithDetails($id) {
        $sql = "SELECT l.*, c.nom as categorie_nom, c.icone as categorie_icone,
                       (SELECT AVG(note) FROM evaluations WHERE lieu_id = l.id AND statut = 'approuve') as note_moyenne,
                       (SELECT COUNT(*) FROM evaluations WHERE lieu_id = l.id AND statut = 'approuve') as nombre_avis,
                       (SELECT COUNT(*) FROM favoris WHERE lieu_id = l.id) as nombre_favoris
                FROM {$this->table} l
                LEFT JOIN categories c ON l.categorie_id = c.id
                WHERE l.id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    public function getBySlug($slug) {
        $sql = "SELECT l.*, c.nom as categorie_nom, c.icone as categorie_icone,
                       (SELECT AVG(note) FROM evaluations WHERE lieu_id = l.id AND statut = 'approuve') as note_moyenne,
                       (SELECT COUNT(*) FROM evaluations WHERE lieu_id = l.id AND statut = 'approuve') as nombre_avis,
                       (SELECT COUNT(*) FROM favoris WHERE lieu_id = l.id) as nombre_favoris
                FROM {$this->table} l
                LEFT JOIN categories c ON l.categorie_id = c.id
                WHERE l.slug = ? AND l.statut = 'actif'";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$slug]);
        return $stmt->fetch();
    }
    
    public function getPopularPlaces($limit = 10) {
        return $this->getAllWithCategories([
            'where' => 'l.statut = "actif"',
            'order' => 'l.popularite DESC, l.nombre_visites DESC',
            'limit' => $limit
        ]);
    }
    
    public function getRecentPlaces($limit = 10) {
        return $this->getAllWithCategories([
            'where' => 'l.statut = "actif"',
            'order' => 'l.date_creation DESC',
            'limit' => $limit
        ]);
    }
    
    public function incrementVisites($id) {
        $sql = "UPDATE {$this->table} SET nombre_visites = nombre_visites + 1 WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$id]);
    }
    
    public function incrementPopularite($id, $amount = 1) {
        $sql = "UPDATE {$this->table} SET popularite = popularite + ? WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$amount, $id]);
    }
    
    public function searchPlaces($searchTerm, $filters = []) {
        $sql = "SELECT l.*, c.nom as categorie_nom, c.icone as categorie_icone,
                       (SELECT chemin_image FROM images_lieux WHERE lieu_id = l.id AND est_principale = 1 LIMIT 1) as image_principale,
                       (SELECT AVG(note) FROM evaluations WHERE lieu_id = l.id AND statut = 'approuve') as note_moyenne,
                       (SELECT COUNT(*) FROM evaluations WHERE lieu_id = l.id AND statut = 'approuve') as nombre_avis
                FROM {$this->table} l
                LEFT JOIN categories c ON l.categorie_id = c.id
                WHERE l.statut = 'actif'";
        
        $params = [];
        
        if (!empty($searchTerm)) {
            $sql .= " AND (l.nom LIKE ? OR l.description LIKE ?)";
            $params[] = "%{$searchTerm}%";
            $params[] = "%{$searchTerm}%";
        }
        
        if (!empty($filters['categorie_id'])) {
            $sql .= " AND l.categorie_id = ?";
            $params[] = $filters['categorie_id'];
        }
        
        if (isset($filters['est_gratuit'])) {
            $sql .= " AND l.est_gratuit = ?";
            $params[] = $filters['est_gratuit'];
        }
        
        $sql .= " ORDER BY l.popularite DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    public function getStats() {
        $sql = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN statut = 'actif' THEN 1 ELSE 0 END) as actifs,
                    SUM(CASE WHEN est_gratuit = 1 THEN 1 ELSE 0 END) as gratuits,
                    SUM(nombre_visites) as total_visites
                FROM {$this->table}";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetch();
    }
    
    public function createSlug($nom) {
        $slug = strtolower(trim($nom));
        $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);
        $slug = preg_replace('/-+/', '-', $slug);
        
        // Vérifier l'unicité
        $originalSlug = $slug;
        $counter = 1;
        
        while ($this->findOne(['where' => 'slug = ?', 'params' => [$slug]])) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }
        
        return $slug;
    }
}
