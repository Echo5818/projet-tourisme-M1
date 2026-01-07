<?php
/**
 * Contrôleur Chat
 * Gestion du chat en direct
 */

require_once BASE_PATH . '/config/config.php';
require_once BASE_PATH . '/config/database.php';

class ChatController extends Controller {
    
    private $conversationModel;
    private $messageModel;
    
    public function __construct() {
        if (!isLoggedIn()) {
            $this->json(['success' => false, 'message' => 'Non autorisé'], 401);
            exit;
        }
        
        $this->conversationModel = new ChatConversation();
        $this->messageModel = new ChatMessage();
    }
    
    // Ouvrir le widget chat (utilisateur)
    public function index() {
        $userId = $_SESSION['user_id'];
        $conversations = $this->conversationModel->getByUserId($userId);
        
        $this->view('chat/widget', [
            'conversations' => $conversations
        ]);
    }
    
    // Démarrer une nouvelle conversation
    public function startConversation() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false], 405);
            return;
        }
        
        $userId = $_SESSION['user_id'];
        $sujet = $_POST['sujet'] ?? 'Demande d\'information';
        
        // Créer ou récupérer conversation
        $conversationId = $this->conversationModel->getOrCreateConversation($userId);
        
        // Envoyer message de bienvenue (système)
        $messageBienvenue = "Bonjour ! Comment puis-je vous aider ?";
        $this->messageModel->insert([
            'conversation_id' => $conversationId,
            'expediteur_id' => 1, // Admin système
            'message' => $messageBienvenue,
            'type' => 'systeme'
        ]);
        
        $this->json([
            'success' => true,
            'conversation_id' => $conversationId
        ]);
    }
    
    // Récupérer les messages d'une conversation
    public function getMessages() {
        $conversationId = $_GET['conversation_id'] ?? 0;
        
        // Vérifier que l'utilisateur a accès à cette conversation
        $conversation = $this->conversationModel->findById($conversationId);
        
        if (!$conversation || 
            ($conversation['user_id'] != $_SESSION['user_id'] && !isAdmin())) {
            $this->json(['success' => false, 'message' => 'Accès refusé'], 403);
            return;
        }
        
        $messages = $this->messageModel->getByConversationId($conversationId);
        
        // Marquer comme lu
        $this->messageModel->marquerCommeLu($conversationId, $_SESSION['user_id']);
        
        $this->json([
            'success' => true,
            'messages' => $messages
        ]);
    }
    
    // Envoyer un message
    public function sendMessage() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false], 405);
            return;
        }
        
        $conversationId = $_POST['conversation_id'] ?? 0;
        $message = trim($_POST['message'] ?? '');
        
        if (empty($message)) {
            $this->json(['success' => false, 'message' => 'Message vide'], 400);
            return;
        }
        
        // Vérifier l'accès
        $conversation = $this->conversationModel->findById($conversationId);
        
        if (!$conversation || 
            ($conversation['user_id'] != $_SESSION['user_id'] && !isAdmin())) {
            $this->json(['success' => false, 'message' => 'Accès refusé'], 403);
            return;
        }
        
        // Envoyer le message
        $messageId = $this->messageModel->envoyerMessage(
            $conversationId,
            $_SESSION['user_id'],
            $message
        );
        
        if ($messageId) {
            // Créer une notification pour le destinataire
            $destinataireId = $conversation['user_id'] == $_SESSION['user_id'] 
                ? $conversation['admin_id'] 
                : $conversation['user_id'];
            
            if ($destinataireId) {
                $notificationModel = $this->model('Notification');
                $notificationModel->insert([
                    'user_id' => $destinataireId,
                    'type' => 'chat_message',
                    'titre' => 'Nouveau message',
                    'message' => substr($message, 0, 50) . '...',
                    'lien' => '/chat?conversation=' . $conversationId,
                    'icone' => 'fa-comment',
                    'priority' => 'normale'
                ]);
            }
            
            $this->json(['success' => true, 'message_id' => $messageId]);
        } else {
            $this->json(['success' => false, 'message' => 'Erreur'], 500);
        }
    }
    
    // Polling pour nouveaux messages
    public function poll() {
        $conversationId = $_GET['conversation_id'] ?? 0;
        $dernierMessageId = $_GET['last_message_id'] ?? 0;
        
        // Vérifier l'accès
        $conversation = $this->conversationModel->findById($conversationId);
        
        if (!$conversation || 
            ($conversation['user_id'] != $_SESSION['user_id'] && !isAdmin())) {
            $this->json(['success' => false], 403);
            return;
        }
        
        $nouveauxMessages = $this->messageModel->getNouveauxMessages(
            $conversationId,
            $dernierMessageId
        );
        
        $this->json([
            'success' => true,
            'messages' => $nouveauxMessages,
            'count' => count($nouveauxMessages)
        ]);
    }
    
    // ADMIN : Vue d'ensemble des conversations
    public function adminIndex() {
        if (!isAdmin()) {
            $this->redirect('/');
            return;
        }
        
        $statut = $_GET['statut'] ?? null;
        $conversations = $this->conversationModel->getAllConversationsAdmin($statut);
        $stats = $this->conversationModel->getStats();
        
        $this->view('admin/chat/index', [
            'title' => 'Gestion du Chat',
            'conversations' => $conversations,
            'stats' => $stats,
            'selectedStatut' => $statut,
            'flash' => $this->getFlashMessage()
        ]);
    }
    
    // ADMIN : Prendre en charge une conversation
    public function assignToMe() {
        if (!isAdmin() || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false], 403);
            return;
        }
        
        $conversationId = $_POST['conversation_id'] ?? 0;
        
        $result = $this->conversationModel->assignerAdmin(
            $conversationId,
            $_SESSION['user_id']
        );
        
        $this->json(['success' => $result]);
    }
    
    // ADMIN : Fermer une conversation
    public function closeConversation() {
        if (!isAdmin() || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false], 403);
            return;
        }
        
        $conversationId = $_POST['conversation_id'] ?? 0;
        
        // Envoyer message de fermeture
        $this->messageModel->envoyerMessage(
            $conversationId,
            $_SESSION['user_id'],
            'Cette conversation a été fermée. Merci de nous avoir contactés !',
            'systeme'
        );
        
        $result = $this->conversationModel->fermerConversation($conversationId);
        
        $this->json(['success' => $result]);
    }
    
    // Nombre de messages non lus
    public function getUnreadCount() {
        $userId = $_SESSION['user_id'];
        
        if (isAdmin()) {
            // Pour admin : compter tous les messages non lus des utilisateurs
            $count = $this->messageModel->query(
                "SELECT COUNT(*) as total 
                 FROM chat_messages m
                 INNER JOIN chat_conversations c ON m.conversation_id = c.id
                 WHERE m.lu = 0 AND c.statut != 'fermee' AND m.expediteur_id = c.user_id"
            )[0]['total'];
        } else {
            // Pour utilisateur : ses propres messages non lus
            $count = $this->messageModel->getMessagesNonLus($userId);
        }
        
        $this->json([
            'success' => true,
            'count' => $count
        ]);
    }
    
    /**
     * API: Récupérer les conversations (pour admin)
     */
    public function apiConversations() {
        if (!isAdmin()) {
            $this->json(['success' => false, 'message' => 'Accès non autorisé'], 403);
            return;
        }
        
        $statut = $_GET['statut'] ?? 'ouverte';
        $conversations = $this->conversationModel->getAdminConversations($statut);
        $counts = $this->conversationModel->getConversationCounts();
        
        $this->json([
            'success' => true,
            'conversations' => $conversations,
            'counts' => $counts
        ]);
    }
    
    /**
     * API: Récupérer une conversation spécifique
     */
    public function apiConversation($conversationId) {
        if (!isAdmin()) {
            $this->json(['success' => false, 'message' => 'Accès non autorisé'], 403);
            return;
        }
        
        $conversation = $this->conversationModel->getConversationWithUser($conversationId);
        
        if (!$conversation) {
            $this->json(['success' => false, 'message' => 'Conversation non trouvée'], 404);
            return;
        }
        
        $this->json([
            'success' => true,
            'conversation' => $conversation
        ]);
    }
    
    /**
     * API: Récupérer les messages d'une conversation
     */
    public function apiMessages($conversationId) {
        if (!isAdmin()) {
            $this->json(['success' => false, 'message' => 'Accès non autorisé'], 403);
            return;
        }
        
        $messages = $this->messageModel->getMessagesWithUser($conversationId);
        
        // Marquer les messages comme lus
        $this->messageModel->marquerCommeLu($conversationId, $_SESSION['user_id']);
        
        $this->json([
            'success' => true,
            'messages' => $messages
        ]);
    }
    
    /**
     * API: Prendre en charge une conversation
     */
    public function apiTakeCharge() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isAdmin()) {
            $this->json(['success' => false], 403);
            return;
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        $conversationId = $data['conversation_id'] ?? null;
        
        if (!$conversationId) {
            $this->json(['success' => false], 400);
            return;
        }
        
        $success = $this->conversationModel->update($conversationId, [
            'statut' => 'en_cours',
            'admin_id' => $_SESSION['user_id']
        ]);
        
        $this->json(['success' => $success]);
    }
    
    /**
     * API: Fermer une conversation
     */
    public function apiClose() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isAdmin()) {
            $this->json(['success' => false], 403);
            return;
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        $conversationId = $data['conversation_id'] ?? null;
        
        if (!$conversationId) {
            $this->json(['success' => false], 400);
            return;
        }
        
        $success = $this->conversationModel->update($conversationId, [
            'statut' => 'fermee'
        ]);
        
        $this->json(['success' => $success]);
    }
    
    /**
     * API: Envoyer un message (format JSON)
     */
    public function apiSend() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isAdmin()) {
            $this->json(['success' => false], 403);
            return;
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        $conversationId = $data['conversation_id'] ?? null;
        $message = $data['message'] ?? '';
        
        if (!$conversationId || empty($message)) {
            $this->json(['success' => false], 400);
            return;
        }
        
        $messageId = $this->messageModel->envoyerMessage(
            $conversationId,
            $_SESSION['user_id'],
            $message
        );
        
        if ($messageId) {
            $this->json(['success' => true, 'message_id' => $messageId]);
        } else {
            $this->json(['success' => false], 500);
        }
    }
}
