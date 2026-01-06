-- Nouvelles Fonctionnalités : Guides PDF, Chat, Notifications
-- À exécuter après schema.sql

USE ndere_city_tourism;

-- ========================================
-- 1. SYSTÈME DE GUIDES PDF
-- ========================================

-- Table des guides PDF
CREATE TABLE IF NOT EXISTS guides_pdf (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titre VARCHAR(255) NOT NULL,
    description TEXT,
    fichier VARCHAR(255) NOT NULL,
    taille_fichier INT,
    nombre_telechargements INT DEFAULT 0,
    lieu_id INT,
    categorie_id INT,
    type ENUM('general', 'lieu', 'categorie', 'itineraire') DEFAULT 'general',
    langue VARCHAR(10) DEFAULT 'fr',
    couverture VARCHAR(255),
    actif BOOLEAN DEFAULT TRUE,
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
    cree_par INT,
    FOREIGN KEY (lieu_id) REFERENCES lieux_touristiques(id) ON DELETE CASCADE,
    FOREIGN KEY (categorie_id) REFERENCES categories(id) ON DELETE CASCADE,
    FOREIGN KEY (cree_par) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_type (type),
    INDEX idx_actif (actif)
) ENGINE=InnoDB;

-- Historique des téléchargements de guides
CREATE TABLE IF NOT EXISTS telechargements_guides (
    id INT AUTO_INCREMENT PRIMARY KEY,
    guide_id INT NOT NULL,
    user_id INT,
    ip_address VARCHAR(45),
    date_telechargement DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (guide_id) REFERENCES guides_pdf(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_guide (guide_id),
    INDEX idx_user (user_id),
    INDEX idx_date (date_telechargement)
) ENGINE=InnoDB;

-- ========================================
-- 2. SYSTÈME DE CHAT EN DIRECT
-- ========================================

-- Table des conversations
CREATE TABLE IF NOT EXISTS chat_conversations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    admin_id INT,
    statut ENUM('ouverte', 'en_cours', 'fermee') DEFAULT 'ouverte',
    sujet VARCHAR(255),
    derniere_activite DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user (user_id),
    INDEX idx_admin (admin_id),
    INDEX idx_statut (statut)
) ENGINE=InnoDB;

-- Table des messages du chat
CREATE TABLE IF NOT EXISTS chat_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    conversation_id INT NOT NULL,
    expediteur_id INT NOT NULL,
    message TEXT NOT NULL,
    type ENUM('texte', 'fichier', 'image', 'systeme') DEFAULT 'texte',
    fichier VARCHAR(255),
    lu BOOLEAN DEFAULT FALSE,
    date_envoi DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (conversation_id) REFERENCES chat_conversations(id) ON DELETE CASCADE,
    FOREIGN KEY (expediteur_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_conversation (conversation_id),
    INDEX idx_expediteur (expediteur_id),
    INDEX idx_lu (lu),
    INDEX idx_date (date_envoi)
) ENGINE=InnoDB;

-- Table des participants au chat (pour chat de groupe futur)
CREATE TABLE IF NOT EXISTS chat_participants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    conversation_id INT NOT NULL,
    user_id INT NOT NULL,
    date_ajout DATETIME DEFAULT CURRENT_TIMESTAMP,
    dernier_message_lu INT DEFAULT 0,
    FOREIGN KEY (conversation_id) REFERENCES chat_conversations(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_conversation_user (conversation_id, user_id),
    INDEX idx_user (user_id)
) ENGINE=InnoDB;

-- ========================================
-- 3. SYSTÈME DE NOTIFICATIONS AMÉLIORÉ
-- ========================================

-- Amélioration de la table notifications existante
ALTER TABLE notifications 
ADD COLUMN priority ENUM('basse', 'normale', 'haute', 'urgente') DEFAULT 'normale' AFTER message,
ADD COLUMN icone VARCHAR(50) AFTER priority,
ADD COLUMN couleur VARCHAR(20) AFTER icone,
ADD COLUMN action_url VARCHAR(255) AFTER lien,
ADD COLUMN action_texte VARCHAR(100) AFTER action_url,
ADD COLUMN expire_le DATETIME AFTER action_texte,
ADD COLUMN lu_le DATETIME AFTER lu,
ADD INDEX idx_priority (priority),
ADD INDEX idx_expire (expire_le);

-- Table des préférences de notifications
CREATE TABLE IF NOT EXISTS notifications_preferences (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type_notification VARCHAR(50) NOT NULL,
    actif BOOLEAN DEFAULT TRUE,
    canal ENUM('site', 'email', 'push', 'tous') DEFAULT 'tous',
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_type (user_id, type_notification),
    INDEX idx_user (user_id)
) ENGINE=InnoDB;

-- Table pour les notifications push (web push)
CREATE TABLE IF NOT EXISTS push_subscriptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    endpoint TEXT NOT NULL,
    auth_key VARCHAR(255),
    p256dh_key VARCHAR(255),
    user_agent TEXT,
    actif BOOLEAN DEFAULT TRUE,
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_actif (actif)
) ENGINE=InnoDB;

-- ========================================
-- DONNÉES PAR DÉFAUT
-- ========================================

-- Guides PDF par défaut
INSERT INTO guides_pdf (titre, description, fichier, type, langue, actif, cree_par) VALUES
('Guide Général de Ngaoundéré', 'Découvrez tous les sites touristiques de Ngaoundéré', 'guides/guide-general-ngaoundere.pdf', 'general', 'fr', TRUE, 1),
('Guide des Monuments Historiques', 'Histoire et patrimoine de la ville', 'guides/guide-monuments-historiques.pdf', 'categorie', 'fr', TRUE, 1),
('Carte Interactive de la Ville', 'Plan détaillé avec tous les lieux', 'guides/carte-interactive.pdf', 'general', 'fr', TRUE, 1);

-- Préférences de notifications par défaut pour les types
INSERT INTO parametres (cle, valeur, description, type) VALUES
('notifications_reservation_validee', 'true', 'Notifier lors de la validation d\'une réservation', 'boolean'),
('notifications_reservation_refusee', 'true', 'Notifier lors du refus d\'une réservation', 'boolean'),
('notifications_evaluation_approuvee', 'true', 'Notifier lors de l\'approbation d\'une évaluation', 'boolean'),
('notifications_nouveau_message', 'true', 'Notifier lors d\'un nouveau message', 'boolean'),
('notifications_chat_nouveau', 'true', 'Notifier lors d\'un nouveau message chat', 'boolean');

-- Mise à jour de la table parametres pour le chat
INSERT INTO parametres (cle, valeur, description, type) VALUES
('chat_actif', 'true', 'Activer le système de chat', 'boolean'),
('chat_heures_ouverture', '08:00-18:00', 'Heures d\'ouverture du chat', 'texte'),
('chat_message_bienvenue', 'Bonjour ! Comment puis-je vous aider ?', 'Message de bienvenue du chat', 'texte'),
('chat_delai_reponse', '5', 'Délai de réponse estimé (minutes)', 'nombre');
