-- Base de données pour Ndere City Tourism
-- Création et initialisation complète

CREATE DATABASE IF NOT EXISTS ndere_city_tourism CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE ndere_city_tourism;

-- Table des utilisateurs (users, admins, superadmin)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    mot_de_passe VARCHAR(255) NOT NULL,
    telephone VARCHAR(20),
    role ENUM('user', 'admin', 'superadmin') DEFAULT 'user',
    statut ENUM('actif', 'inactif', 'suspendu') DEFAULT 'actif',
    photo_profil VARCHAR(255),
    date_inscription DATETIME DEFAULT CURRENT_TIMESTAMP,
    date_derniere_connexion DATETIME,
    token_reset VARCHAR(255),
    token_reset_expiration DATETIME,
    email_verifie BOOLEAN DEFAULT FALSE,
    INDEX idx_email (email),
    INDEX idx_role (role),
    INDEX idx_statut (statut)
) ENGINE=InnoDB;

-- Table des privilèges des administrateurs
CREATE TABLE admins_privileges (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL,
    module VARCHAR(50) NOT NULL,
    privilege ENUM('lecture', 'ecriture', 'suppression', 'complet') DEFAULT 'lecture',
    FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_admin_module (admin_id, module)
) ENGINE=InnoDB;

-- Table des catégories de lieux
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    slug VARCHAR(100) UNIQUE NOT NULL,
    description TEXT,
    icone VARCHAR(50),
    ordre INT DEFAULT 0
) ENGINE=InnoDB;

-- Table des lieux touristiques
CREATE TABLE lieux_touristiques (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    description TEXT NOT NULL,
    categorie_id INT,
    latitude DECIMAL(10, 8) NOT NULL,
    longitude DECIMAL(11, 8) NOT NULL,
    adresse TEXT,
    tarif DECIMAL(10, 2) DEFAULT 0,
    est_gratuit BOOLEAN DEFAULT TRUE,
    horaires_ouverture TEXT,
    contact_telephone VARCHAR(20),
    contact_email VARCHAR(255),
    statut ENUM('actif', 'inactif', 'archive') DEFAULT 'actif',
    popularite INT DEFAULT 0,
    nombre_visites INT DEFAULT 0,
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
    date_modification DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    cree_par INT,
    FOREIGN KEY (categorie_id) REFERENCES categories(id) ON DELETE SET NULL,
    FOREIGN KEY (cree_par) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_statut (statut),
    INDEX idx_categorie (categorie_id),
    INDEX idx_popularite (popularite),
    FULLTEXT idx_recherche (nom, description)
) ENGINE=InnoDB;

-- Table des images des lieux
CREATE TABLE images_lieux (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lieu_id INT NOT NULL,
    chemin_image VARCHAR(255) NOT NULL,
    ordre INT DEFAULT 0,
    est_principale BOOLEAN DEFAULT FALSE,
    legende VARCHAR(255),
    date_ajout DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (lieu_id) REFERENCES lieux_touristiques(id) ON DELETE CASCADE,
    INDEX idx_lieu (lieu_id),
    INDEX idx_principale (est_principale)
) ENGINE=InnoDB;

-- Table des évaluations et commentaires
CREATE TABLE evaluations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lieu_id INT NOT NULL,
    user_id INT NOT NULL,
    note INT NOT NULL CHECK (note BETWEEN 1 AND 5),
    commentaire TEXT,
    statut ENUM('en_attente', 'approuve', 'rejete') DEFAULT 'en_attente',
    reponse_admin TEXT,
    date_evaluation DATETIME DEFAULT CURRENT_TIMESTAMP,
    date_modification DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (lieu_id) REFERENCES lieux_touristiques(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_lieu (user_id, lieu_id),
    INDEX idx_lieu (lieu_id),
    INDEX idx_user (user_id),
    INDEX idx_statut (statut),
    INDEX idx_note (note)
) ENGINE=InnoDB;

-- Table des favoris
CREATE TABLE favoris (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    lieu_id INT NOT NULL,
    date_ajout DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (lieu_id) REFERENCES lieux_touristiques(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_lieu_favoris (user_id, lieu_id),
    INDEX idx_user (user_id),
    INDEX idx_lieu (lieu_id)
) ENGINE=InnoDB;

-- Table des réservations
CREATE TABLE reservations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lieu_id INT NOT NULL,
    user_id INT NOT NULL,
    date_visite DATE NOT NULL,
    heure_depart TIME,
    moyen_transport ENUM('moto', 'taxi', 'voiture', 'bus', 'autre') DEFAULT 'taxi',
    point_depart_lat DECIMAL(10, 8),
    point_depart_lng DECIMAL(11, 8),
    point_depart_adresse TEXT,
    distance DECIMAL(10, 2),
    duree_trajet INT,
    cout_estime DECIMAL(10, 2),
    nombre_personnes INT DEFAULT 1,
    statut ENUM('en_attente', 'validee', 'refusee', 'annulee', 'terminee') DEFAULT 'en_attente',
    motif_refus TEXT,
    notes_utilisateur TEXT,
    date_reservation DATETIME DEFAULT CURRENT_TIMESTAMP,
    date_traitement DATETIME,
    traite_par INT,
    FOREIGN KEY (lieu_id) REFERENCES lieux_touristiques(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (traite_par) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_lieu (lieu_id),
    INDEX idx_user (user_id),
    INDEX idx_statut (statut),
    INDEX idx_date_visite (date_visite)
) ENGINE=InnoDB;

-- Table des messages/contact
CREATE TABLE messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    nom VARCHAR(100),
    email VARCHAR(255),
    telephone VARCHAR(20),
    objet VARCHAR(255) NOT NULL,
    contenu TEXT NOT NULL,
    reponse TEXT,
    statut ENUM('non_lu', 'lu', 'traite', 'archive') DEFAULT 'non_lu',
    date_envoi DATETIME DEFAULT CURRENT_TIMESTAMP,
    date_reponse DATETIME,
    repondu_par INT,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (repondu_par) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_statut (statut),
    INDEX idx_user (user_id)
) ENGINE=InnoDB;

-- Table du contenu dynamique
CREATE TABLE contenu_dynamique (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cle VARCHAR(100) UNIQUE NOT NULL,
    titre VARCHAR(255),
    contenu LONGTEXT,
    meta_description TEXT,
    date_modification DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    modifie_par INT,
    FOREIGN KEY (modifie_par) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Table des sliders (carrousel page d'accueil)
CREATE TABLE sliders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lieu_id INT,
    titre VARCHAR(255) NOT NULL,
    sous_titre VARCHAR(255),
    description TEXT,
    image VARCHAR(255) NOT NULL,
    lien VARCHAR(255),
    ordre INT DEFAULT 0,
    actif BOOLEAN DEFAULT TRUE,
    date_debut DATE,
    date_fin DATE,
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (lieu_id) REFERENCES lieux_touristiques(id) ON DELETE SET NULL,
    INDEX idx_ordre (ordre),
    INDEX idx_actif (actif)
) ENGINE=InnoDB;

-- Table des notifications
CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type VARCHAR(50) NOT NULL,
    titre VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    lien VARCHAR(255),
    lu BOOLEAN DEFAULT FALSE,
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_lu (lu)
) ENGINE=InnoDB;

-- Table des logs d'activité (pour l'audit)
CREATE TABLE logs_activite (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(100) NOT NULL,
    module VARCHAR(50) NOT NULL,
    details TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    date_action DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user (user_id),
    INDEX idx_date (date_action),
    INDEX idx_action (action)
) ENGINE=InnoDB;

-- Table des paramètres de l'application
CREATE TABLE parametres (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cle VARCHAR(100) UNIQUE NOT NULL,
    valeur TEXT,
    description TEXT,
    type ENUM('texte', 'nombre', 'boolean', 'json') DEFAULT 'texte',
    date_modification DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Insertion des catégories par défaut
INSERT INTO categories (nom, slug, description, icone, ordre) VALUES
('Monuments Historiques', 'monuments-historiques', 'Sites et monuments historiques de Ngaoundéré', 'landmark', 1),
('Parcs et Jardins', 'parcs-jardins', 'Espaces verts et parcs naturels', 'tree', 2),
('Musées', 'musees', 'Musées et centres culturels', 'museum', 3),
('Sites Naturels', 'sites-naturels', 'Cascades, lacs et merveilles naturelles', 'mountain', 4),
('Marchés', 'marches', 'Marchés traditionnels et artisanaux', 'shopping-bag', 5),
('Lieux de Culte', 'lieux-culte', 'Mosquées, églises et lieux spirituels', 'church', 6),
('Restaurants', 'restaurants', 'Restaurants et lieux de restauration', 'utensils', 7),
('Hébergements', 'hebergements', 'Hôtels et lieux d\'hébergement', 'hotel', 8);

-- Insertion du SuperAdmin par défaut
-- Mot de passe : admin123 (à changer en production)
INSERT INTO users (nom, prenom, email, mot_de_passe, role, statut, email_verifie) VALUES
('Admin', 'Super', 'superadmin@nderecity.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'superadmin', 'actif', TRUE);

-- Insertion du contenu dynamique par défaut
INSERT INTO contenu_dynamique (cle, titre, contenu) VALUES
('a_propos', 'À Propos de Ndere City', '<h2>Bienvenue à Ngaoundéré</h2><p>Découvrez la beauté et la richesse culturelle de Ngaoundéré, la capitale de la région de l\'Adamaoua au Cameroun.</p>'),
('contact', 'Contactez-nous', '<p>Pour toute question ou demande d\'information, n\'hésitez pas à nous contacter.</p>'),
('mentions_legales', 'Mentions Légales', '<p>Mentions légales du site Ndere City Tourism.</p>'),
('politique_confidentialite', 'Politique de Confidentialité', '<p>Politique de confidentialité et protection des données.</p>'),
('cgu', 'Conditions Générales d\'Utilisation', '<p>Conditions générales d\'utilisation du site.</p>');

-- Insertion des paramètres par défaut
INSERT INTO parametres (cle, valeur, description, type) VALUES
('site_nom', 'Ndere City Tourism', 'Nom du site', 'texte'),
('site_description', 'Plateforme de tourisme pour Ngaoundéré', 'Description du site', 'texte'),
('site_email', 'contact@nderecity.com', 'Email de contact', 'texte'),
('site_telephone', '+237 690 000 000', 'Téléphone de contact', 'texte'),
('site_adresse', 'Ngaoundéré, Cameroun', 'Adresse physique', 'texte'),
('tarif_moto_km', '500', 'Tarif moto par km (FCFA)', 'nombre'),
('tarif_taxi_km', '1000', 'Tarif taxi par km (FCFA)', 'nombre'),
('vitesse_moto', '40', 'Vitesse moyenne moto (km/h)', 'nombre'),
('vitesse_taxi', '50', 'Vitesse moyenne taxi (km/h)', 'nombre'),
('reservation_auto_validation', 'false', 'Validation automatique des réservations', 'boolean'),
('commentaires_moderation', 'true', 'Modération des commentaires', 'boolean'),
('max_reservations_jour', '3', 'Nombre max de réservations par jour par utilisateur', 'nombre');
