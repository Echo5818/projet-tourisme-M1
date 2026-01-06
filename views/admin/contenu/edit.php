<?php require_once BASE_PATH . '/views/layouts/admin_header.php'; ?>

<div class="admin-header">
    <h1 class="admin-header-title">
        <i class="fas fa-edit"></i> Éditer : <?= escape($contenu['titre']) ?>
    </h1>
    <a href="/admin/contenu" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> Retour
    </a>
</div>

<?php if (isset($_SESSION['error'])): ?>
<div class="alert alert-error">
    <i class="fas fa-exclamation-circle"></i> <?= $_SESSION['error'] ?>
</div>
<?php unset($_SESSION['error']); endif; ?>

<div class="card">
    <div class="card-header">
        <h3><?= $keyInfo ? escape($keyInfo['label']) : escape($contenu['titre']) ?></h3>
        <?php if ($keyInfo): ?>
        <p style="color: #6b7280; margin-top: 0.5rem;">
            <?= escape($keyInfo['description']) ?>
        </p>
        <?php endif; ?>
    </div>
    
    <form method="POST" action="/admin/contenu/save" style="padding: 1.5rem;">
        <input type="hidden" name="cle" value="<?= escape($contenu['cle']) ?>">
        
        <div class="form-group">
            <label for="titre" class="form-label">Titre de la Page *</label>
            <input 
                type="text" 
                id="titre" 
                name="titre" 
                class="form-control" 
                value="<?= escape($contenu['titre']) ?>"
                required
                placeholder="Titre visible sur la page"
            >
        </div>
        
        <div class="form-group">
            <label for="contenu" class="form-label">Contenu *</label>
            <div style="margin-bottom: 0.5rem; display: flex; gap: 0.5rem; flex-wrap: wrap; background: #f9fafb; padding: 0.75rem; border-radius: 6px;">
                <button type="button" class="editor-btn" onclick="insertFormat('bold')">
                    <i class="fas fa-bold"></i> Gras
                </button>
                <button type="button" class="editor-btn" onclick="insertFormat('italic')">
                    <i class="fas fa-italic"></i> Italique
                </button>
                <button type="button" class="editor-btn" onclick="insertFormat('h2')">
                    <i class="fas fa-heading"></i> Titre H2
                </button>
                <button type="button" class="editor-btn" onclick="insertFormat('h3')">
                    <i class="fas fa-heading"></i> Titre H3
                </button>
                <button type="button" class="editor-btn" onclick="insertFormat('p')">
                    <i class="fas fa-paragraph"></i> Paragraphe
                </button>
                <button type="button" class="editor-btn" onclick="insertFormat('ul')">
                    <i class="fas fa-list-ul"></i> Liste
                </button>
                <button type="button" class="editor-btn" onclick="insertFormat('link')">
                    <i class="fas fa-link"></i> Lien
                </button>
            </div>
            <textarea 
                id="contenu" 
                name="contenu" 
                class="form-control" 
                rows="20"
                placeholder="Contenu de la page en HTML..."
                style="font-family: 'Courier New', monospace;"
            ><?= escape($contenu['contenu']) ?></textarea>
            <small class="form-help">
                <i class="fas fa-info-circle"></i> 
                Vous pouvez utiliser du HTML pour formater votre contenu.
                Utilisez les boutons ci-dessus pour faciliter la saisie.
            </small>
        </div>
        
        <div style="background: #f0f9ff; border-left: 4px solid #0284c7; padding: 1rem; margin-bottom: 1.5rem; border-radius: 4px;">
            <h4 style="margin: 0 0 0.5rem 0; color: #0c4a6e;">
                <i class="fas fa-eye"></i> Aperçu du Contenu
            </h4>
            <div id="preview" style="background: white; padding: 1rem; border-radius: 4px; min-height: 100px;">
                <?= $contenu['contenu'] ?>
            </div>
        </div>
        
        <div style="display: flex; gap: 1rem;">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Enregistrer
            </button>
            <button type="button" class="btn btn-secondary" onclick="updatePreview()">
                <i class="fas fa-eye"></i> Actualiser l'aperçu
            </button>
            <a href="/admin/contenu" class="btn btn-secondary">
                <i class="fas fa-times"></i> Annuler
            </a>
        </div>
    </form>
</div>

<style>
.editor-btn {
    background: white;
    border: 1px solid #d1d5db;
    padding: 0.5rem 0.75rem;
    border-radius: 4px;
    cursor: pointer;
    font-size: 0.875rem;
    transition: all 0.2s;
}

.editor-btn:hover {
    background: #f3f4f6;
    border-color: var(--primary-color);
}

.editor-btn i {
    margin-right: 0.25rem;
}
</style>

<script>
const textarea = document.getElementById('contenu');
const preview = document.getElementById('preview');

// Fonction pour insérer du formatage HTML
function insertFormat(type) {
    const start = textarea.selectionStart;
    const end = textarea.selectionEnd;
    const selectedText = textarea.value.substring(start, end);
    let replacement = '';
    
    switch(type) {
        case 'bold':
            replacement = `<strong>${selectedText || 'texte en gras'}</strong>`;
            break;
        case 'italic':
            replacement = `<em>${selectedText || 'texte en italique'}</em>`;
            break;
        case 'h2':
            replacement = `<h2>${selectedText || 'Titre de niveau 2'}</h2>`;
            break;
        case 'h3':
            replacement = `<h3>${selectedText || 'Titre de niveau 3'}</h3>`;
            break;
        case 'p':
            replacement = `<p>${selectedText || 'Paragraphe de texte'}</p>`;
            break;
        case 'ul':
            replacement = `<ul>\n  <li>${selectedText || 'Élément 1'}</li>\n  <li>Élément 2</li>\n  <li>Élément 3</li>\n</ul>`;
            break;
        case 'link':
            const url = prompt('URL du lien:', 'https://');
            if (url) {
                replacement = `<a href="${url}">${selectedText || 'texte du lien'}</a>`;
            }
            break;
    }
    
    if (replacement) {
        textarea.value = textarea.value.substring(0, start) + replacement + textarea.value.substring(end);
        textarea.focus();
        textarea.setSelectionRange(start, start + replacement.length);
    }
}

// Fonction pour actualiser l'aperçu
function updatePreview() {
    preview.innerHTML = textarea.value || '<p style="color: #9ca3af;">L\'aperçu s\'affichera ici...</p>';
}

// Actualiser l'aperçu en temps réel (avec un délai)
let previewTimeout;
textarea.addEventListener('input', function() {
    clearTimeout(previewTimeout);
    previewTimeout = setTimeout(updatePreview, 500);
});
</script>

<?php require_once BASE_PATH . '/views/layouts/admin_footer.php'; ?>
