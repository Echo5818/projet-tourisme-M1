<?php
/**
 * Modèle ImageLieu
 * Gestion des images des lieux touristiques
 */

class ImageLieu extends Model {
    protected $table = 'images_lieux';
    
    public function getByLieuId($lieuId) {
        return $this->findAll([
            'where' => 'lieu_id = ?',
            'params' => [$lieuId],
            'order' => 'ordre ASC, est_principale DESC'
        ]);
    }
    
    public function getPrincipaleByLieuId($lieuId) {
        return $this->findOne([
            'where' => 'lieu_id = ? AND est_principale = 1',
            'params' => [$lieuId]
        ]);
    }
    
    public function setPrincipale($imageId, $lieuId) {
        // Retirer le statut principale de toutes les images du lieu
        $sql = "UPDATE {$this->table} SET est_principale = 0 WHERE lieu_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$lieuId]);
        
        // Définir la nouvelle image principale
        return $this->update($imageId, ['est_principale' => 1]);
    }
    
    public function reorderImages($lieuId, $ordres) {
        foreach ($ordres as $imageId => $ordre) {
            $this->update($imageId, ['ordre' => $ordre]);
        }
        return true;
    }
    
    public function deleteByLieuId($lieuId) {
        $images = $this->getByLieuId($lieuId);
        
        foreach ($images as $image) {
            // Supprimer le fichier physique
            $filePath = BASE_PATH . '/public/uploads/lieux/' . basename($image['chemin_image']);
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }
        
        $sql = "DELETE FROM {$this->table} WHERE lieu_id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$lieuId]);
    }
}
