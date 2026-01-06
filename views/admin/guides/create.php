<?php require_once BASE_PATH . '/views/layouts/admin_header.php'; ?>

<div class="admin-header">
    <h1 class="admin-header-title">
        <i class="fas fa-plus-circle"></i> Ajouter un Guide PDF
    </h1>
    <a href="<?= APP_URL ?>/admin/guides" class="btn btn-outline">
        <i class="fas fa-arrow-left"></i> Retour
    </a>
</div>

<?php if (isset($flash)): ?>
<div class="alert alert-<?= $flash['type'] ?>" data-dismiss="5000">
    <?= $flash['message'] ?>
</div>
<?php endif; ?>

<div class="card">
    <form action="<?= APP_URL ?>/admin/guides/store" method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label class="form-label">Titre *</label>
            <input type="text" name="titre" class="form-control" required placeholder="Ex: Guide Complet de Ngaoundéré">
        </div>
        
        <div class="form-group">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control" rows="4" placeholder="Description du guide..."></textarea>
        </div>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
            <div class="form-group">
                <label class="form-label">Type de guide *</label>
                <select name="type" class="form-control" required>
                    <option value="general">Guide Général</option>
                    <option value="lieu">Guide par Lieu</option>
                    <option value="categorie">Guide par Catégorie</option>
                    <option value="itineraire">Itinéraire</option>
                </select>
            </div>
            
            <div class="form-group">
                <label class="form-label">Langue</label>
                <select name="langue" class="form-control">
                    <option value="fr">Français</option>
                    <option value="en">English</option>
                    <option value="ff">Fulfulde</option>
                </select>
            </div>
        </div>
        
        <div class="form-group">
            <label class="form-label">Fichier PDF * (max 10 MB)</label>
            <input type="file" name="fichier" class="form-control" required accept=".pdf">
        </div>
        
        <div class="form-group">
            <label class="form-label">Image de couverture (optionnel)</label>
            <input type="file" name="couverture" class="form-control" accept="image/*">
        </div>
        
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-save"></i> Enregistrer
        </button>
    </form>
</div>

<?php require_once BASE_PATH . '/views/layouts/admin_footer.php'; ?>
