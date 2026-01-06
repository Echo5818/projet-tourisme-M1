<?php require_once BASE_PATH . '/views/layouts/admin_header.php'; ?>

<div class="admin-header">
    <h1 class="admin-header-title">
        <i class="fas fa-book"></i> Gestion des Guides PDF
    </h1>
    <a href="<?= APP_URL ?>/admin/guides/create" class="btn btn-primary">
        <i class="fas fa-plus"></i> Ajouter un Guide
    </a>
</div>

<?php if (isset($flash)): ?>
<div class="alert alert-<?= $flash['type'] ?>" data-dismiss="5000">
    <?= $flash['message'] ?>
</div>
<?php endif; ?>

<!-- Statistiques -->
<div class="stats-grid mb-4">
    <div class="stat-card">
        <div class="stat-icon primary">
            <i class="fas fa-file-pdf"></i>
        </div>
        <div class="stat-content">
            <h3><?= $stats['total'] ?? 0 ?></h3>
            <p>Guides Total</p>
            <small style="color: #6b7280;"><?= $stats['actifs'] ?? 0 ?> actifs</small>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon success">
            <i class="fas fa-download"></i>
        </div>
        <div class="stat-content">
            <h3><?= number_format($stats['total_telechargements'] ?? 0) ?></h3>
            <p>Téléchargements</p>
            <small style="color: #6b7280;">Moyenne: <?= number_format($stats['moyenne_telechargements'] ?? 0, 1) ?></small>
        </div>
    </div>
</div>

<!-- Liste des guides -->
<div class="table-container">
    <table class="table">
        <thead>
            <tr>
                <th>Titre</th>
                <th>Type</th>
                <th>Taille</th>
                <th>Téléchargements</th>
                <th>Statut</th>
                <th>Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($guides)): ?>
            <tr>
                <td colspan="7" style="text-align: center; padding: 3rem; color: #6b7280;">
                    <i class="fas fa-book" style="font-size: 3rem; margin-bottom: 1rem; display: block;"></i>
                    Aucun guide disponible
                </td>
            </tr>
            <?php else: ?>
            <?php foreach ($guides as $guide): ?>
            <tr>
                <td>
                    <strong><?= escape($guide['titre']) ?></strong><br>
                    <small style="color: #6b7280;"><?= strtoupper($guide['langue']) ?></small>
                </td>
                <td>
                    <span class="badge badge-primary" style="text-transform: uppercase;">
                        <?= $guide['type'] ?>
                    </span>
                </td>
                <td>
                    <?= $guide['taille_fichier'] ? round($guide['taille_fichier'] / 1024 / 1024, 1) . ' MB' : 'N/A' ?>
                </td>
                <td>
                    <strong><?= number_format($guide['nombre_telechargements']) ?></strong>
                </td>
                <td>
                    <span class="badge badge-<?= $guide['actif'] ? 'success' : 'danger' ?>">
                        <?= $guide['actif'] ? 'Actif' : 'Inactif' ?>
                    </span>
                </td>
                <td>
                    <?= date('d/m/Y', strtotime($guide['date_creation'])) ?>
                </td>
                <td>
                    <div class="table-actions">
                        <a href="<?= APP_URL ?>/guide/telecharger/<?= $guide['id'] ?>" 
                           class="btn btn-sm btn-outline" title="Télécharger">
                            <i class="fas fa-download"></i>
                        </a>
                        <button onclick="deleteGuide(<?= $guide['id'] ?>, '<?= escape($guide['titre']) ?>')" 
                                class="btn btn-sm btn-danger" title="Supprimer">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
async function deleteGuide(id, titre) {
    if (!confirm(`Supprimer le guide "${titre}" ?`)) return;
    
    try {
        const response = await fetch(`<?= APP_URL ?>/admin/guides/delete/${id}`, {
            method: 'POST'
        });
        const data = await response.json();
        
        if (data.success) {
            showToast('Guide supprimé', 'success');
            setTimeout(() => location.reload(), 1000);
        }
    } catch (error) {
        showToast('Erreur', 'error');
    }
}
</script>

<?php require_once BASE_PATH . '/views/layouts/admin_footer.php'; ?>
