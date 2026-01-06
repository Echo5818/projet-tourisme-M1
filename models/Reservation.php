<?php
/**
 * Modèle Reservation
 * Gestion des réservations
 */

class Reservation extends Model {
    protected $table = 'reservations';
    
    public function findById($id) {
        $sql = "SELECT r.* 
                FROM {$this->table} r
                WHERE r.id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    public function getByUserId($userId, $statut = null) {
        $sql = "SELECT r.*, l.nom as lieu_nom, l.slug as lieu_slug,
                       (SELECT chemin_image FROM images_lieux WHERE lieu_id = l.id AND est_principale = 1 LIMIT 1) as lieu_image
                FROM {$this->table} r
                INNER JOIN lieux_touristiques l ON r.lieu_id = l.id
                WHERE r.user_id = ?";
        
        $params = [$userId];
        
        if ($statut) {
            $sql .= " AND r.statut = ?";
            $params[] = $statut;
        }
        
        $sql .= " ORDER BY r.date_visite DESC, r.date_reservation DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    public function getAllWithDetails($conditions = []) {
        $sql = "SELECT r.*, 
                       l.nom as lieu_nom, l.slug as lieu_slug,
                       u.nom as user_nom, u.prenom as user_prenom, u.email as user_email,
                       (SELECT chemin_image FROM images_lieux WHERE lieu_id = l.id AND est_principale = 1 LIMIT 1) as lieu_image
                FROM {$this->table} r
                INNER JOIN lieux_touristiques l ON r.lieu_id = l.id
                INNER JOIN users u ON r.user_id = u.id";
        
        if (!empty($conditions['where'])) {
            $sql .= " WHERE " . $conditions['where'];
        }
        
        if (!empty($conditions['order'])) {
            $sql .= " ORDER BY " . $conditions['order'];
        } else {
            $sql .= " ORDER BY r.date_reservation DESC";
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
    
    public function getPendingReservations($limit = null) {
        $conditions = [
            'where' => "r.statut = 'en_attente'",
            'order' => 'r.date_reservation ASC'
        ];
        
        if ($limit) {
            $conditions['limit'] = intval($limit);
        }
        
        return $this->getAllWithDetails($conditions);
    }
    
    public function approveReservation($id, $adminId) {
        return $this->update($id, [
            'statut' => 'validee',
            'date_traitement' => date('Y-m-d H:i:s'),
            'traite_par' => $adminId
        ]);
    }
    
    public function rejectReservation($id, $motif, $adminId) {
        return $this->update($id, [
            'statut' => 'refusee',
            'motif_refus' => $motif,
            'date_traitement' => date('Y-m-d H:i:s'),
            'traite_par' => $adminId
        ]);
    }
    
    public function cancelReservation($id) {
        return $this->update($id, [
            'statut' => 'annulee'
        ]);
    }
    
    public function getStats() {
        $sql = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN statut = 'en_attente' THEN 1 ELSE 0 END) as en_attente,
                    SUM(CASE WHEN statut = 'validee' THEN 1 ELSE 0 END) as validees,
                    SUM(CASE WHEN statut = 'refusee' THEN 1 ELSE 0 END) as refusees,
                    SUM(CASE WHEN statut = 'terminee' THEN 1 ELSE 0 END) as terminees,
                    SUM(CASE WHEN DATE(date_reservation) = CURDATE() THEN 1 ELSE 0 END) as aujourdhui
                FROM {$this->table}";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetch();
    }
    
    public function getMonthlyReservations($year = null) {
        if (!$year) {
            $year = date('Y');
        }
        
        $sql = "SELECT MONTH(date_visite) as mois, COUNT(*) as total
                FROM {$this->table}
                WHERE YEAR(date_visite) = ? AND statut IN ('validee', 'terminee')
                GROUP BY MONTH(date_visite)
                ORDER BY mois";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$year]);
        return $stmt->fetchAll();
    }
    
    public function getPopularDestinations($limit = 10) {
        $sql = "SELECT l.nom, l.slug, COUNT(r.id) as total_reservations,
                       (SELECT chemin_image FROM images_lieux WHERE lieu_id = l.id AND est_principale = 1 LIMIT 1) as image
                FROM {$this->table} r
                INNER JOIN lieux_touristiques l ON r.lieu_id = l.id
                WHERE r.statut IN ('validee', 'terminee')
                GROUP BY r.lieu_id
                ORDER BY total_reservations DESC
                LIMIT ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }
    
    public function getUserReservationCount($userId, $date) {
        $sql = "SELECT COUNT(*) as total
                FROM {$this->table}
                WHERE user_id = ? AND DATE(date_visite) = ? AND statut IN ('en_attente', 'validee')";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId, $date]);
        $result = $stmt->fetch();
        return $result['total'];
    }
}
