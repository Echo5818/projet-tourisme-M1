<?php
/**
 * Modèle Evaluation
 * Gestion des évaluations et commentaires
 */

class Evaluation extends Model {
    protected $table = 'evaluations';
    
    public function getByLieuId($lieuId, $statut = 'approuve') {
        $sql = "SELECT e.*, u.nom, u.prenom, u.photo_profil
                FROM {$this->table} e
                INNER JOIN users u ON e.user_id = u.id
                WHERE e.lieu_id = ?";
        
        $params = [$lieuId];
        
        if ($statut) {
            $sql .= " AND e.statut = ?";
            $params[] = $statut;
        }
        
        $sql .= " ORDER BY e.date_evaluation DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    public function getByUserId($userId) {
        $sql = "SELECT e.*, l.nom as lieu_nom, l.slug as lieu_slug,
                       (SELECT chemin_image FROM images_lieux WHERE lieu_id = l.id AND est_principale = 1 LIMIT 1) as lieu_image
                FROM {$this->table} e
                INNER JOIN lieux_touristiques l ON e.lieu_id = l.id
                WHERE e.user_id = ?
                ORDER BY e.date_evaluation DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }
    
    public function getUserEvaluationForLieu($userId, $lieuId) {
        return $this->findOne([
            'where' => 'user_id = ? AND lieu_id = ?',
            'params' => [$userId, $lieuId]
        ]);
    }
    
    public function getAverageNote($lieuId) {
        $sql = "SELECT AVG(note) as moyenne, COUNT(*) as total
                FROM {$this->table}
                WHERE lieu_id = ? AND statut = 'approuve'";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$lieuId]);
        return $stmt->fetch();
    }
    
    public function getNoteDistribution($lieuId) {
        $sql = "SELECT note, COUNT(*) as count
                FROM {$this->table}
                WHERE lieu_id = ? AND statut = 'approuve'
                GROUP BY note
                ORDER BY note DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$lieuId]);
        return $stmt->fetchAll();
    }
    
    public function getPendingEvaluations($limit = null) {
        $sql = "SELECT e.*, u.nom, u.prenom, l.nom as lieu_nom
                FROM {$this->table} e
                INNER JOIN users u ON e.user_id = u.id
                INNER JOIN lieux_touristiques l ON e.lieu_id = l.id
                WHERE e.statut = 'en_attente'
                ORDER BY e.date_evaluation DESC";
        
        if ($limit) {
            $sql .= " LIMIT " . intval($limit);
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    public function approveEvaluation($id, $reponse = null) {
        $data = ['statut' => 'approuve'];
        if ($reponse) {
            $data['reponse_admin'] = $reponse;
        }
        return $this->update($id, $data);
    }
    
    public function rejectEvaluation($id, $reponse = null) {
        $data = ['statut' => 'rejete'];
        if ($reponse) {
            $data['reponse_admin'] = $reponse;
        }
        return $this->update($id, $data);
    }
    
    public function getStats() {
        $sql = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN statut = 'en_attente' THEN 1 ELSE 0 END) as en_attente,
                    SUM(CASE WHEN statut = 'approuve' THEN 1 ELSE 0 END) as approuves,
                    AVG(note) as note_moyenne
                FROM {$this->table}";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetch();
    }
    
    public function getRecentEvaluations($limit = 10) {
        $sql = "SELECT e.*, u.nom, u.prenom, l.nom as lieu_nom, l.slug as lieu_slug
                FROM {$this->table} e
                INNER JOIN users u ON e.user_id = u.id
                INNER JOIN lieux_touristiques l ON e.lieu_id = l.id
                ORDER BY e.date_evaluation DESC
                LIMIT ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }
    
    public function getRatingsDistribution() {
        $sql = "SELECT note, COUNT(*) as count
                FROM {$this->table}
                WHERE statut = 'approuve'
                GROUP BY note
                ORDER BY note";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        
        // Transformer en tableau associatif
        $results = $stmt->fetchAll();
        $distribution = [];
        foreach ($results as $row) {
            $distribution[$row['note']] = $row['count'];
        }
        
        return $distribution;
    }
}
