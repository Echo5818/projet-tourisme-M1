<?php
/**
 * Modèle ContenuDynamique
 * Gestion des contenus dynamiques (À propos, Contact, Mentions légales, etc.)
 */

class ContenuDynamique extends Model {
    protected $table = 'contenu_dynamique';
    
    /**
     * Récupérer un contenu par sa clé
     */
    public function getByCle($cle) {
        return $this->findOne([
            'where' => 'cle = ?',
            'params' => [$cle]
        ]);
    }
    
    /**
     * Mettre à jour un contenu
     */
    public function updateContenu($cle, $titre, $contenu) {
        $existing = $this->getByCle($cle);
        
        if ($existing) {
            return $this->update($existing['id'], [
                'titre' => $titre,
                'contenu' => $contenu,
                'date_modification' => date('Y-m-d H:i:s')
            ]);
        } else {
            return $this->insert([
                'cle' => $cle,
                'titre' => $titre,
                'contenu' => $contenu
            ]);
        }
    }
    
    /**
     * Récupérer tous les contenus disponibles
     */
    public function getAllContenus() {
        return $this->findAll([
            'order' => 'cle ASC'
        ]);
    }
    
    /**
     * Récupérer les contenus pour affichage public
     */
    public function getPublicContenus() {
        $contenus = $this->getAllContenus();
        $result = [];
        
        foreach ($contenus as $contenu) {
            $result[$contenu['cle']] = $contenu;
        }
        
        return $result;
    }
    
    /**
     * Obtenir la liste des clés de contenu disponibles
     */
    public function getAvailableKeys() {
        return [
            'a_propos' => [
                'label' => 'À Propos',
                'description' => 'Page à propos de Ndere City et du tourisme à Ngaoundéré'
            ],
            'histoire' => [
                'label' => 'Histoire',
                'description' => 'Histoire de Ngaoundéré'
            ],
            'mission' => [
                'label' => 'Notre Mission',
                'description' => 'Mission et objectifs du site'
            ],
            'contact' => [
                'label' => 'Informations de Contact',
                'description' => 'Coordonnées et informations de contact'
            ],
            'mentions_legales' => [
                'label' => 'Mentions Légales',
                'description' => 'Mentions légales du site'
            ],
            'politique_confidentialite' => [
                'label' => 'Politique de Confidentialité',
                'description' => 'Politique de confidentialité et RGPD'
            ],
            'cgu' => [
                'label' => 'Conditions Générales d\'Utilisation',
                'description' => 'Conditions générales d\'utilisation du site'
            ],
            'faq' => [
                'label' => 'FAQ',
                'description' => 'Questions fréquemment posées'
            ],
            'equipe' => [
                'label' => 'Notre Équipe',
                'description' => 'Présentation de l\'équipe'
            ]
        ];
    }
    
    /**
     * Initialiser les contenus par défaut
     */
    public function initializeDefaults() {
        $defaults = [
            'a_propos' => [
                'titre' => 'À Propos de Ndere City',
                'contenu' => '<h2>Bienvenue à Ndere City</h2><p>Découvrez Ngaoundéré, une ville riche en histoire et en culture, située au cœur du Cameroun.</p>'
            ],
            'histoire' => [
                'titre' => 'Histoire de Ngaoundéré',
                'contenu' => '<h2>Histoire</h2><p>Ngaoundéré est une ville historique fondée au XIXe siècle...</p>'
            ],
            'mission' => [
                'titre' => 'Notre Mission',
                'contenu' => '<h2>Notre Mission</h2><p>Promouvoir le tourisme local et faciliter la découverte des merveilles de Ngaoundéré.</p>'
            ],
            'contact' => [
                'titre' => 'Contactez-nous',
                'contenu' => '<h2>Informations de Contact</h2><p><strong>Adresse :</strong> Ngaoundéré, Cameroun</p><p><strong>Email :</strong> contact@nderecity.cm</p><p><strong>Téléphone :</strong> +237 XXX XXX XXX</p>'
            ],
            'mentions_legales' => [
                'titre' => 'Mentions Légales',
                'contenu' => '<h2>Mentions Légales</h2><p>Éditeur du site : Ndere City Tourism</p>'
            ],
            'politique_confidentialite' => [
                'titre' => 'Politique de Confidentialité',
                'contenu' => '<h2>Politique de Confidentialité</h2><p>Nous respectons votre vie privée et protégeons vos données personnelles...</p>'
            ],
            'cgu' => [
                'titre' => 'Conditions Générales d\'Utilisation',
                'contenu' => '<h2>Conditions Générales d\'Utilisation</h2><p>En utilisant ce site, vous acceptez les conditions suivantes...</p>'
            ],
            'faq' => [
                'titre' => 'Questions Fréquentes',
                'contenu' => '<h2>FAQ</h2><h3>Comment réserver un lieu ?</h3><p>Pour réserver un lieu, vous devez d\'abord créer un compte...</p>'
            ]
        ];
        
        foreach ($defaults as $cle => $data) {
            $existing = $this->getByCle($cle);
            if (!$existing) {
                $this->insert([
                    'cle' => $cle,
                    'titre' => $data['titre'],
                    'contenu' => $data['contenu']
                ]);
            }
        }
        
        return true;
    }
}
