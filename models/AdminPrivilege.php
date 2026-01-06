<?php
/**
 * Modèle AdminPrivilege
 * Gestion des privilèges des administrateurs
 */

class AdminPrivilege extends Model {
    protected $table = 'admins_privileges';
    
    /**
     * Récupérer tous les privilèges d'un admin
     */
    public function getAdminPrivileges($adminId) {
        return $this->findAll([
            'where' => 'admin_id = ?',
            'params' => [$adminId]
        ]);
    }
    
    /**
     * Vérifier si un admin a un privilège spécifique sur un module
     */
    public function hasPrivilege($adminId, $module, $privilege) {
        $result = $this->findOne([
            'where' => 'admin_id = ? AND module = ? AND privilege = ?',
            'params' => [$adminId, $module, $privilege]
        ]);
        
        return $result !== null;
    }
    
    /**
     * Ajouter un privilège à un admin
     */
    public function addPrivilege($adminId, $module, $privilege) {
        // Vérifier si le privilège existe déjà
        $existing = $this->findOne([
            'where' => 'admin_id = ? AND module = ? AND privilege = ?',
            'params' => [$adminId, $module, $privilege]
        ]);
        
        if ($existing) {
            return true; // Déjà existant
        }
        
        return $this->insert([
            'admin_id' => $adminId,
            'module' => $module,
            'privilege' => $privilege
        ]);
    }
    
    /**
     * Supprimer un privilège d'un admin
     */
    public function removePrivilege($adminId, $module, $privilege) {
        $sql = "DELETE FROM {$this->table} 
                WHERE admin_id = ? AND module = ? AND privilege = ?";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$adminId, $module, $privilege]);
    }
    
    /**
     * Supprimer tous les privilèges d'un admin
     */
    public function removeAllPrivileges($adminId) {
        $sql = "DELETE FROM {$this->table} WHERE admin_id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$adminId]);
    }
    
    /**
     * Définir les privilèges d'un admin (remplace tous les anciens)
     */
    public function setPrivileges($adminId, $privileges) {
        // Supprimer tous les anciens privilèges
        $this->removeAllPrivileges($adminId);
        
        // Ajouter les nouveaux privilèges
        foreach ($privileges as $privilege) {
            $this->insert([
                'admin_id' => $adminId,
                'module' => $privilege['module'],
                'privilege' => $privilege['privilege']
            ]);
        }
        
        return true;
    }
    
    /**
     * Obtenir les privilèges organisés par module
     */
    public function getPrivilegesByModule($adminId) {
        $privileges = $this->getAdminPrivileges($adminId);
        $organized = [];
        
        foreach ($privileges as $priv) {
            if (!isset($organized[$priv['module']])) {
                $organized[$priv['module']] = [];
            }
            $organized[$priv['module']][] = $priv['privilege'];
        }
        
        return $organized;
    }
    
    /**
     * Vérifier si l'admin a au moins un privilège de lecture sur un module
     */
    public function canRead($adminId, $module) {
        return $this->hasPrivilege($adminId, $module, 'lecture') ||
               $this->hasPrivilege($adminId, $module, 'ecriture') ||
               $this->hasPrivilege($adminId, $module, 'suppression');
    }
    
    /**
     * Vérifier si l'admin peut écrire sur un module
     */
    public function canWrite($adminId, $module) {
        return $this->hasPrivilege($adminId, $module, 'ecriture') ||
               $this->hasPrivilege($adminId, $module, 'suppression');
    }
    
    /**
     * Vérifier si l'admin peut supprimer sur un module
     */
    public function canDelete($adminId, $module) {
        return $this->hasPrivilege($adminId, $module, 'suppression');
    }
    
    /**
     * Obtenir tous les modules disponibles
     */
    public function getAvailableModules() {
        return [
            'lieux' => 'Lieux Touristiques',
            'reservations' => 'Réservations',
            'utilisateurs' => 'Utilisateurs',
            'evaluations' => 'Évaluations',
            'messages' => 'Messages',
            'guides' => 'Guides PDF',
            'sliders' => 'Sliders',
            'chat' => 'Chat',
            'contenu' => 'Contenu Dynamique',
            'statistiques' => 'Statistiques'
        ];
    }
}
