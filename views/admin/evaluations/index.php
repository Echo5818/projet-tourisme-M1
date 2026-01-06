<?php require_once BASE_PATH . '/views/layouts/admin_header.php'; ?>

<div class="admin-header">
    <h1 class="admin-header-title">
        <i class="fas fa-star"></i> Gestion des Évaluations
    </h1>
</div>

<?php if (isset($flash)): ?>
<div class="alert alert-<?= $flash['type'] ?>" data-dismiss="5000">
    <?= $flash['message'] ?>
</div>
<?php endif; ?>

<!-- Filtres -->
<div class="card mb-4">
    <form method="GET" action="<?= APP_URL ?>/admin/evaluations" style="display: flex; gap: 1rem;">
        <div class="form-group" style="flex: 1; margin-bottom: 0;">
            <label class="form-label">Filtrer par statut</label>
            <select name="statut" class="form-control" onchange="this.form.submit()">
                <option value="">Tous</option>
                <option value="en_attente" <?= ($selectedStatut === 'en_attente') ? 'selected' : '' ?>>En attente</option>
                <option value="approuve" <?= ($selectedStatut === 'approuve') ? 'selected' : '' ?>>Approuvé</option>
                <option value="rejete" <?= ($selectedStatut === 'rejete') ? 'selected' : '' ?>>Rejeté</option>
            </select>
        </div>
    </form>
</div>

<!-- Liste des évaluations -->
<div class="table-container">
    <table class="table">
        <thead>
            <tr>
                <th>Utilisateur</th>
                <th>Lieu</th>
                <th>Note</th>
                <th>Commentaire</th>
                <th>Date</th>
                <th>Statut</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($evaluations)): ?>
            <tr>
                <td colspan="7" style="text-align: center; padding: 3rem; color: #6b7280;">
                    <i class="fas fa-comments" style="font-size: 3rem; margin-bottom: 1rem; display: block;"></i>
                    Aucune évaluation
                </td>
            </tr>
            <?php else: ?>
            <?php foreach ($evaluations as $eval): ?>
            <tr>
                <td><?= escape($eval['prenom'] . ' ' . $eval['nom']) ?></td>
                <td>
                    <a href="<?= APP_URL ?>/lieu/<?= $eval['lieu_slug'] ?>" target="_blank" style="color: var(--primary-color);">
                        <?= escape($eval['lieu_nom']) ?>
                    </a>
                </td>
                <td>
                    <div class="stars">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                        <i class="fas fa-star <?= $i <= $eval['note'] ? 'filled' : 'empty' ?>"></i>
                        <?php endfor; ?>
                    </div>
                </td>
                <td style="max-width: 300px;">
                    <?= escape(substr($eval['commentaire'], 0, 100)) ?>
                    <?= strlen($eval['commentaire']) > 100 ? '...' : '' ?>
                </td>
                <td><?= date('d/m/Y', strtotime($eval['date_evaluation'])) ?></td>
                <td>
                    <?php
                    $badgeClass = [
                        'en_attente' => 'warning',
                        'approuve' => 'success',
                        'rejete' => 'danger'
                    ];
                    ?>
                    <span class="badge badge-<?= $badgeClass[$eval['statut']] ?>">
                        <?= ucfirst(str_replace('_', ' ', $eval['statut'])) ?>
                    </span>
                </td>
                <td>
                    <div class="table-actions">
                        <?php if ($eval['statut'] === 'en_attente'): ?>
                        <button onclick="approveEvaluation(<?= $eval['id'] ?>)" 
                                class="btn btn-sm btn-success" title="Approuver">
                            <i class="fas fa-check"></i>
                        </button>
                        <button onclick="rejectEvaluation(<?= $eval['id'] ?>)" 
                                class="btn btn-sm btn-danger" title="Rejeter">
                            <i class="fas fa-times"></i>
                        </button>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
async function approveEvaluation(id) {
    if (!confirm('Approuver cette évaluation ?')) return;
    
    const formData = new URLSearchParams();
    formData.append('evaluation_id', id);
    
    try {
        const response = await fetch('<?= APP_URL ?>/admin/evaluation/approve', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();
        
        if (data.success) {
            showToast('Évaluation approuvée', 'success');
            setTimeout(() => location.reload(), 1000);
        }
    } catch (error) {
        showToast('Erreur', 'error');
    }
}

async function rejectEvaluation(id) {
    if (!confirm('Rejeter cette évaluation ?')) return;
    
    const formData = new URLSearchParams();
    formData.append('evaluation_id', id);
    
    try {
        const response = await fetch('<?= APP_URL ?>/admin/evaluation/reject', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();
        
        if (data.success) {
            showToast('Évaluation rejetée', 'success');
            setTimeout(() => location.reload(), 1000);
        }
    } catch (error) {
        showToast('Erreur', 'error');
    }
}
</script>

<?php require_once BASE_PATH . '/views/layouts/admin_footer.php'; ?>
