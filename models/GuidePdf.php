<?php
/**
 * Modèle GuidePdf
 * Gestion des guides PDF téléchargeables
 */

class GuidePdf extends Model {
    protected $table = 'guides_pdf';
    
    public function getAllActifs($type = null) {
        $sql = "SELECT g.*, c.nom as categorie_nom, l.nom as lieu_nom,
                       u.prenom as createur_prenom, u.nom as createur_nom
                FROM {$this->table} g
                LEFT JOIN categories c ON g.categorie_id = c.id
                LEFT JOIN lieux_touristiques l ON g.lieu_id = l.id
                LEFT JOIN users u ON g.cree_par = u.id
                WHERE g.actif = 1";
        
        if ($type) {
            $sql .= " AND g.type = ?";
            $params = [$type];
        } else {
            $params = [];
        }
        
        $sql .= " ORDER BY g.date_creation DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    public function getByIdWithDetails($id) {
        $sql = "SELECT g.*, c.nom as categorie_nom, l.nom as lieu_nom, l.slug as lieu_slug,
                       u.prenom as createur_prenom, u.nom as createur_nom
                FROM {$this->table} g
                LEFT JOIN categories c ON g.categorie_id = c.id
                LEFT JOIN lieux_touristiques l ON g.lieu_id = l.id
                LEFT JOIN users u ON g.cree_par = u.id
                WHERE g.id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    public function getByLieu($lieuId) {
        return $this->findAll([
            'where' => 'lieu_id = ? AND actif = 1',
            'params' => [$lieuId],
            'order' => 'date_creation DESC'
        ]);
    }
    
    public function getByCategorie($categorieId) {
        return $this->findAll([
            'where' => 'categorie_id = ? AND actif = 1',
            'params' => [$categorieId],
            'order' => 'date_creation DESC'
        ]);
    }
    
    public function incrementTelechargements($id) {
        $sql = "UPDATE {$this->table} SET nombre_telechargements = nombre_telechargements + 1 WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$id]);
    }
    
    public function enregistrerTelechargement($guideId, $userId = null, $ipAddress = null) {
        $sql = "INSERT INTO telechargements_guides (guide_id, user_id, ip_address) VALUES (?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$guideId, $userId, $ipAddress]);
    }
    
    public function getPopulaires($limit = 5) {
        return $this->findAll([
            'where' => 'actif = 1',
            'order' => 'nombre_telechargements DESC',
            'limit' => $limit
        ]);
    }
    
    public function getRecents($limit = 5) {
        return $this->findAll([
            'where' => 'actif = 1',
            'order' => 'date_creation DESC',
            'limit' => $limit
        ]);
    }
    
    public function getStats() {
        $sql = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN actif = 1 THEN 1 ELSE 0 END) as actifs,
                    SUM(nombre_telechargements) as total_telechargements,
                    AVG(nombre_telechargements) as moyenne_telechargements
                FROM {$this->table}";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetch();
    }
    
    public function searchGuides($searchTerm) {
        $sql = "SELECT g.*, c.nom as categorie_nom, l.nom as lieu_nom
                FROM {$this->table} g
                LEFT JOIN categories c ON g.categorie_id = c.id
                LEFT JOIN lieux_touristiques l ON g.lieu_id = l.id
                WHERE g.actif = 1
                AND (g.titre LIKE ? OR g.description LIKE ?)
                ORDER BY g.nombre_telechargements DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(["%{$searchTerm}%", "%{$searchTerm}%"]);
        return $stmt->fetchAll();
    }
}
