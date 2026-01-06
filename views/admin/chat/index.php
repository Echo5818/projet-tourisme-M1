<?php require_once BASE_PATH . '/views/layouts/admin_header.php'; ?>

<div class="admin-header">
    <h1 class="admin-header-title">
        <i class="fas fa-comments"></i> Chat en Direct
    </h1>
    <div class="admin-user-info">
        <span id="onlineStatus" class="badge badge-success">
            <i class="fas fa-circle"></i> En ligne
        </span>
    </div>
</div>

<div style="display: grid; grid-template-columns: 350px 1fr; gap: 1.5rem; height: calc(100vh - 200px);">
    <!-- Liste des conversations -->
    <div class="card" style="height: 100%; display: flex; flex-direction: column;">
        <div class="card-header" style="flex-shrink: 0;">
            <h3 style="margin: 0;">Conversations</h3>
            <div style="margin-top: 1rem;">
                <div class="tabs-container" style="display: flex; gap: 0.5rem; border-bottom: 2px solid var(--border-color);">
                    <button class="tab-btn active" data-status="ouverte" onclick="filterConversations('ouverte')">
                        <i class="fas fa-inbox"></i> Nouvelles
                        <span class="badge-count" id="count-ouverte">0</span>
                    </button>
                    <button class="tab-btn" data-status="en_cours" onclick="filterConversations('en_cours')">
                        <i class="fas fa-comments"></i> En cours
                        <span class="badge-count" id="count-en_cours">0</span>
                    </button>
                    <button class="tab-btn" data-status="fermee" onclick="filterConversations('fermee')">
                        <i class="fas fa-check"></i> Fermées
                        <span class="badge-count" id="count-fermee">0</span>
                    </button>
                </div>
            </div>
        </div>
        
        <div style="flex: 1; overflow-y: auto; padding: 1rem;" id="conversationsList">
            <!-- Les conversations seront chargées ici via JavaScript -->
            <div style="text-align: center; padding: 2rem; color: #9ca3af;">
                <i class="fas fa-spinner fa-spin" style="font-size: 2rem;"></i>
                <p style="margin-top: 1rem;">Chargement des conversations...</p>
            </div>
        </div>
    </div>
    
    <!-- Zone de chat -->
    <div class="card" style="height: 100%; display: flex; flex-direction: column;">
        <div id="chatPlaceholder" style="flex: 1; display: flex; align-items: center; justify-content: center; color: #9ca3af;">
            <div style="text-align: center;">
                <i class="fas fa-comments" style="font-size: 4rem; margin-bottom: 1rem; opacity: 0.3;"></i>
                <h3 style="color: #6b7280;">Sélectionnez une conversation</h3>
                <p>Choisissez une conversation dans la liste pour commencer à chatter</p>
            </div>
        </div>
        
        <div id="chatContainer" style="height: 100%; display: none; flex-direction: column;">
            <!-- En-tête du chat -->
            <div class="card-header" style="flex-shrink: 0; display: flex; justify-content: space-between; align-items: center;">
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <div style="position: relative;">
                        <div style="width: 40px; height: 40px; border-radius: 50%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); display: flex; align-items: center; justify-content: center; color: white; font-weight: bold;" id="userAvatar">
                            U
                        </div>
                        <span class="online-indicator" style="position: absolute; bottom: 0; right: 0; width: 12px; height: 12px; background: #10b981; border: 2px solid white; border-radius: 50%;"></span>
                    </div>
                    <div>
                        <h3 style="margin: 0;" id="chatUserName">Nom de l'utilisateur</h3>
                        <small style="color: #6b7280;" id="chatUserEmail">email@example.com</small>
                    </div>
                </div>
                <div style="display: flex; gap: 0.5rem;">
                    <button class="btn btn-sm btn-secondary" onclick="toggleConversationStatus()" id="statusBtn">
                        <i class="fas fa-play"></i> Prendre en charge
                    </button>
                    <button class="btn btn-sm btn-danger" onclick="closeConversation()" id="closeBtn" style="display: none;">
                        <i class="fas fa-check"></i> Fermer
                    </button>
                </div>
            </div>
            
            <!-- Messages -->
            <div style="flex: 1; overflow-y: auto; padding: 1.5rem; background: #f9fafb;" id="messagesContainer">
                <!-- Les messages seront chargés ici -->
            </div>
            
            <!-- Zone de saisie -->
            <div style="flex-shrink: 0; padding: 1rem; border-top: 2px solid var(--border-color); background: white;">
                <form id="messageForm" onsubmit="sendMessage(event)" style="display: flex; gap: 0.5rem;">
                    <input 
                        type="text" 
                        id="messageInput" 
                        class="form-control" 
                        placeholder="Tapez votre message..."
                        style="flex: 1;"
                        autocomplete="off"
                    >
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane"></i> Envoyer
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
.tabs-container {
    padding-bottom: 0;
}

.tab-btn {
    background: transparent;
    border: none;
    padding: 0.75rem 1rem;
    cursor: pointer;
    font-size: 0.9rem;
    color: #6b7280;
    border-bottom: 2px solid transparent;
    transition: all 0.3s;
    position: relative;
}

.tab-btn:hover {
    color: var(--primary-color);
}

.tab-btn.active {
    color: var(--primary-color);
    border-bottom-color: var(--primary-color);
    font-weight: 600;
}

.badge-count {
    display: inline-block;
    background: var(--primary-color);
    color: white;
    font-size: 0.75rem;
    padding: 0.15rem 0.5rem;
    border-radius: 10px;
    margin-left: 0.5rem;
    font-weight: bold;
}

.conversation-item {
    padding: 1rem;
    border-radius: 8px;
    margin-bottom: 0.5rem;
    cursor: pointer;
    transition: all 0.2s;
    border: 2px solid transparent;
    background: white;
}

.conversation-item:hover {
    background: #f3f4f6;
    border-color: var(--primary-color);
}

.conversation-item.active {
    background: #eff6ff;
    border-color: var(--primary-color);
}

.conversation-item.unread {
    background: #fef3c7;
}

.message-bubble {
    max-width: 70%;
    margin-bottom: 1rem;
    animation: slideIn 0.3s ease-out;
}

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.message-bubble.user {
    margin-left: auto;
}

.message-bubble.admin {
    margin-right: auto;
}

.message-content {
    padding: 0.75rem 1rem;
    border-radius: 12px;
    word-wrap: break-word;
}

.message-bubble.user .message-content {
    background: #e5e7eb;
    color: #1f2937;
    border-bottom-right-radius: 4px;
}

.message-bubble.admin .message-content {
    background: var(--primary-color);
    color: white;
    border-bottom-left-radius: 4px;
}

.message-time {
    font-size: 0.75rem;
    color: #9ca3af;
    margin-top: 0.25rem;
}

.message-bubble.user .message-time {
    text-align: right;
}
</style>

<script src="/public/assets/js/admin-chat.js"></script>

<?php require_once BASE_PATH . '/views/layouts/admin_footer.php'; ?>
