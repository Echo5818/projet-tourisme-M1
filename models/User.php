<?php
/**
 * Modèle User
 * Gestion des utilisateurs (users, admins, superadmin)
 */

class User extends Model {
    protected $table = 'users';
    
    public function findByEmail($email) {
        return $this->findOne([
            'where' => 'email = ?',
            'params' => [$email]
        ]);
    }
    
    public function createUser($data) {
        // Hash du mot de passe
        if (isset($data['mot_de_passe'])) {
            $data['mot_de_passe'] = password_hash($data['mot_de_passe'], PASSWORD_DEFAULT);
        }
        
        return $this->insert($data);
    }
    
    public function verifyPassword($email, $password) {
        $user = $this->findByEmail($email);
        
        if ($user && password_verify($password, $user['mot_de_passe'])) {
            return $user;
        }
        
        return false;
    }
    
    public function updateLastLogin($userId) {
        return $this->update($userId, [
            'date_derniere_connexion' => date('Y-m-d H:i:s')
        ]);
    }
    
    public function getAllAdmins() {
        return $this->findAll([
            'where' => "role IN ('admin', 'superadmin')",
            'order' => 'date_inscription DESC'
        ]);
    }
    
    public function getUserStats() {
        $sql = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN role = 'user' THEN 1 ELSE 0 END) as users,
                    SUM(CASE WHEN role = 'admin' THEN 1 ELSE 0 END) as admins,
                    SUM(CASE WHEN statut = 'actif' THEN 1 ELSE 0 END) as actifs,
                    SUM(CASE WHEN DATE(date_inscription) = CURDATE() THEN 1 ELSE 0 END) as aujourdhui
                FROM {$this->table}";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetch();
    }
    
    public function getRecentUsers($limit = 10) {
        return $this->findAll([
            'order' => 'date_inscription DESC',
            'limit' => $limit
        ]);
    }
    
    public function changePassword($userId, $newPassword) {
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        return $this->update($userId, [
            'mot_de_passe' => $hashedPassword
        ]);
    }
    
    public function setResetToken($email, $token) {
        $user = $this->findByEmail($email);
        if ($user) {
            return $this->update($user['id'], [
                'token_reset' => $token,
                'token_reset_expiration' => date('Y-m-d H:i:s', strtotime('+1 hour'))
            ]);
        }
        return false;
    }
    
    public function verifyResetToken($token) {
        return $this->findOne([
            'where' => 'token_reset = ? AND token_reset_expiration > NOW()',
            'params' => [$token]
        ]);
    }
    
    public function getMonthlyGrowth($year = null) {
        if (!$year) {
            $year = date('Y');
        }
        
        $sql = "SELECT MONTH(date_inscription) as mois, COUNT(*) as total
                FROM {$this->table}
                WHERE YEAR(date_inscription) = ? AND role = 'user'
                GROUP BY MONTH(date_inscription)
                ORDER BY mois";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$year]);
        return $stmt->fetchAll();
    }
}
