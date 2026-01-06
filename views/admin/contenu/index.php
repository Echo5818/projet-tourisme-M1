<?php require_once BASE_PATH . '/views/layouts/admin_header.php'; ?>

<div class="admin-header">
    <h1 class="admin-header-title">
        <i class="fas fa-file-alt"></i> Gestion du Contenu Dynamique
    </h1>
    <?php if ($_SESSION['user_role'] === 'superadmin'): ?>
    <a href="/admin/contenu/initialize" class="btn btn-secondary" onclick="return confirm('Voulez-vous initialiser les contenus par défaut ? Cela ne remplacera pas les contenus existants.')">
        <i class="fas fa-database"></i> Initialiser les Contenus
    </a>
    <?php endif; ?>
</div>

<?php if (isset($_SESSION['success'])): ?>
<div class="alert alert-success">
    <i class="fas fa-check-circle"></i> <?= $_SESSION['success'] ?>
</div>
<?php unset($_SESSION['success']); endif; ?>

<?php if (isset($_SESSION['error'])): ?>
<div class="alert alert-error">
    <i class="fas fa-exclamation-circle"></i> <?= $_SESSION['error'] ?>
</div>
<?php unset($_SESSION['error']); endif; ?>

<div class="card">
    <div class="card-header">
        <h3>Pages et Contenus</h3>
        <p style="color: #6b7280; font-size: 0.9rem; margin-top: 0.5rem;">
            Gérez les contenus des pages statiques de votre site (À propos, Contact, Mentions légales, etc.)
        </p>
    </div>
    
    <div style="padding: 1.5rem;">
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1.5rem;">
            <?php 
            // Créer un tableau associatif des contenus existants
            $existingContenus = [];
            foreach ($contenus as $contenu) {
                $existingContenus[$contenu['cle']] = $contenu;
            }
            
            // Afficher tous les contenus disponibles
            foreach ($availableKeys as $cle => $info): 
                $contenu = $existingContenus[$cle] ?? null;
                $exists = $contenu !== null;
            ?>
            <div class="contenu-card" style="
                border: 2px solid var(--border-color);
                border-radius: 8px;
                padding: 1.5rem;
                transition: all 0.3s;
                <?= $exists ? 'border-left: 4px solid var(--success-color);' : 'border-left: 4px solid #cbd5e1;' ?>
            ">
                <div style="display: flex; justify-content: between; align-items: start; margin-bottom: 1rem;">
                    <div style="flex: 1;">
                        <h4 style="margin: 0 0 0.5rem 0; color: var(--text-dark);">
                            <i class="fas fa-file-alt" style="color: var(--primary-color); margin-right: 0.5rem;"></i>
                            <?= escape($info['label']) ?>
                        </h4>
                        <p style="margin: 0; color: #6b7280; font-size: 0.85rem;">
                            <?= escape($info['description']) ?>
                        </p>
                    </div>
                </div>
                
                <?php if ($exists): ?>
                <div style="background: #f0fdf4; padding: 0.75rem; border-radius: 6px; margin-bottom: 1rem; font-size: 0.85rem;">
                    <i class="fas fa-check-circle" style="color: var(--success-color);"></i>
                    <span style="color: #15803d;">Contenu existant</span>
                    <br>
                    <small style="color: #6b7280;">
                        Dernière modification : <?= date('d/m/Y à H:i', strtotime($contenu['date_modification'])) ?>
                    </small>
                </div>
                <?php else: ?>
                <div style="background: #fef3c7; padding: 0.75rem; border-radius: 6px; margin-bottom: 1rem; font-size: 0.85rem;">
                    <i class="fas fa-exclamation-triangle" style="color: #d97706;"></i>
                    <span style="color: #92400e;">Contenu non configuré</span>
                </div>
                <?php endif; ?>
                
                <a href="/admin/contenu/edit/<?= $cle ?>" class="btn btn-primary" style="width: 100%;">
                    <i class="fas fa-edit"></i> <?= $exists ? 'Modifier' : 'Créer' ?>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<style>
.contenu-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    transform: translateY(-2px);
}
</style>

<?php require_once BASE_PATH . '/views/layouts/admin_footer.php'; ?>
