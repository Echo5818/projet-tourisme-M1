<?php
/**
 * Modèle Slider
 * Gestion des slides de la page d'accueil
 */

class Slider extends Model {
    protected $table = 'sliders';
    
    public function getActiveSliders() {
        $sql = "SELECT s.*, l.nom as lieu_nom, l.slug as lieu_slug
                FROM {$this->table} s
                LEFT JOIN lieux_touristiques l ON s.lieu_id = l.id
                WHERE s.actif = 1
                AND (s.date_debut IS NULL OR s.date_debut <= CURDATE())
                AND (s.date_fin IS NULL OR s.date_fin >= CURDATE())
                ORDER BY s.ordre ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    public function getAllWithDetails() {
        $sql = "SELECT s.*, l.nom as lieu_nom, l.slug as lieu_slug
                FROM {$this->table} s
                LEFT JOIN lieux_touristiques l ON s.lieu_id = l.id
                ORDER BY s.ordre ASC, s.date_creation DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    public function reorderSliders($ordres) {
        foreach ($ordres as $sliderId => $ordre) {
            $this->update($sliderId, ['ordre' => $ordre]);
        }
        return true;
    }
}
