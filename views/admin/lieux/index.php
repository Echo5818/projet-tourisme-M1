<?php require_once BASE_PATH . '/views/layouts/admin_header.php'; ?>

<div class="admin-header">
    <h1 class="admin-header-title">
        <i class="fas fa-map-marked-alt"></i> Gestion des Lieux Touristiques
    </h1>
    <a href="<?= APP_URL ?>/admin/lieu/create" class="btn btn-primary">
        <i class="fas fa-plus"></i> Ajouter un Lieu
    </a>
</div>

<?php if (isset($flash)): ?>
<div class="alert alert-<?= $flash['type'] ?>" data-dismiss="5000">
    <?= $flash['message'] ?>
</div>
<?php endif; ?>

<!-- Filtres -->
<div class="card mb-4">
    <form method="GET" action="<?= APP_URL ?>/admin/lieux" style="display: flex; gap: 1rem; align-items: end;">
        <div class="form-group" style="flex: 2; margin-bottom: 0;">
            <label class="form-label">Rechercher</label>
            <input type="text" name="search" class="form-control" placeholder="Nom du lieu..." value="<?= $_GET['search'] ?? '' ?>">
        </div>
        
        <div class="form-group" style="flex: 1; margin-bottom: 0;">
            <label class="form-label">Statut</label>
            <select name="statut" class="form-control">
                <option value="">Tous les statuts</option>
                <option value="actif" <?= ($_GET['statut'] ?? '') === 'actif' ? 'selected' : '' ?>>Actif</option>
                <option value="inactif" <?= ($_GET['statut'] ?? '') === 'inactif' ? 'selected' : '' ?>>Inactif</option>
                <option value="archive" <?= ($_GET['statut'] ?? '') === 'archive' ? 'selected' : '' ?>>Archivé</option>
            </select>
        </div>
        
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-search"></i> Rechercher
        </button>
    </form>
</div>

<!-- Liste des lieux -->
<div class="table-container">
    <table class="table">
        <thead>
            <tr>
                <th>Image</th>
                <th>Nom</th>
                <th>Catégorie</th>
                <th>Tarif</th>
                <th>Note</th>
                <th>Visites</th>
                <th>Statut</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($lieux)): ?>
            <tr>
                <td colspan="8" style="text-align: center; padding: 3rem; color: #6b7280;">
                    <i class="fas fa-map-marked-alt" style="font-size: 3rem; margin-bottom: 1rem; display: block;"></i>
                    Aucun lieu trouvé
                </td>
            </tr>
            <?php else: ?>
            <?php foreach ($lieux as $lieu): ?>
            <tr>
                <td>
                    <?php if ($lieu['image_principale']): ?>
                    <img src="<?= APP_URL ?>/<?= $lieu['image_principale'] ?>" 
                         alt="<?= escape($lieu['nom']) ?>"
                         style="width: 80px; height: 60px; object-fit: cover; border-radius: 4px;">
                    <?php else: ?>
                    <div style="width: 80px; height: 60px; background: #e5e7eb; border-radius: 4px; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-image" style="color: #9ca3af;"></i>
                    </div>
                    <?php endif; ?>
                </td>
                <td>
                    <strong><?= escape($lieu['nom']) ?></strong><br>
                    <small style="color: #6b7280;"><?= $lieu['nombre_images'] ?> image(s)</small>
                </td>
                <td>
                    <?php if ($lieu['categorie_nom']): ?>
                    <i class="fas fa-<?= $lieu['categorie_icone'] ?>"></i> 
                    <?= escape($lieu['categorie_nom']) ?>
                    <?php else: ?>
                    <span style="color: #9ca3af;">-</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($lieu['est_gratuit']): ?>
                    <span class="badge badge-success">Gratuit</span>
                    <?php else: ?>
                    <?= number_format($lieu['tarif']) ?> FCFA
                    <?php endif; ?>
                </td>
                <td>
                    <div class="stars" style="font-size: 0.9rem;">
                        <?php 
                        $note = round($lieu['note_moyenne'] ?? 0);
                        for ($i = 1; $i <= 5; $i++): 
                        ?>
                        <i class="fas fa-star <?= $i <= $note ? 'filled' : 'empty' ?>"></i>
                        <?php endfor; ?>
                    </div>
                    <small style="color: #6b7280;"><?= number_format($lieu['note_moyenne'] ?? 0, 1) ?> (<?= $lieu['nombre_avis'] ?>)</small>
                </td>
                <td>
                    <strong><?= number_format($lieu['nombre_visites']) ?></strong>
                </td>
                <td>
                    <?php
                    $badgeClass = [
                        'actif' => 'success',
                        'inactif' => 'warning',
                        'archive' => 'danger'
                    ];
                    ?>
                    <span class="badge badge-<?= $badgeClass[$lieu['statut']] ?? 'info' ?>">
                        <?= ucfirst($lieu['statut']) ?>
                    </span>
                </td>
                <td>
                    <div class="table-actions">
                        <a href="<?= APP_URL ?>/lieu/<?= $lieu['slug'] ?>" 
                           target="_blank" 
                           class="btn btn-sm btn-outline" 
                           title="Voir">
                            <i class="fas fa-eye"></i>
                        </a>
                        <a href="<?= APP_URL ?>/admin/lieu/edit/<?= $lieu['id'] ?>" 
                           class="btn btn-sm btn-primary" 
                           title="Modifier">
                            <i class="fas fa-edit"></i>
                        </a>
                        <button onclick="deleteLieu(<?= $lieu['id'] ?>, '<?= escape($lieu['nom']) ?>')" 
                                class="btn btn-sm btn-danger" 
                                title="Supprimer">
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
async function deleteLieu(id, nom) {
    if (!confirm(`Êtes-vous sûr de vouloir supprimer "${nom}" ?\n\nCette action est irréversible et supprimera également toutes les images associées.`)) {
        return;
    }
    
    try {
        const response = await fetch(`<?= APP_URL ?>/admin/lieu/delete/${id}`, {
            method: 'POST'
        });
        
        const data = await response.json();
        
        if (data.success) {
            showToast('Lieu supprimé avec succès', 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast(data.message || 'Erreur lors de la suppression', 'error');
        }
    } catch (error) {
        showToast('Erreur lors de la suppression', 'error');
    }
}
</script>

<?php require_once BASE_PATH . '/views/layouts/admin_footer.php'; ?>
