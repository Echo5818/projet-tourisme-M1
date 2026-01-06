<?php require_once BASE_PATH . '/views/layouts/admin_header.php'; ?>

<div class="admin-header">
    <h1 class="admin-header-title">
        <i class="fas fa-calendar-check"></i> Gestion des Réservations
    </h1>
</div>

<?php if (isset($flash)): ?>
<div class="alert alert-<?= $flash['type'] ?>" data-dismiss="5000">
    <?= $flash['message'] ?>
</div>
<?php endif; ?>

<!-- Filtres -->
<div class="card mb-4">
    <form method="GET" action="<?= APP_URL ?>/admin/reservations" style="display: flex; gap: 1rem; align-items: end;">
        <div class="form-group" style="flex: 1; margin-bottom: 0;">
            <label class="form-label">Filtrer par statut</label>
            <select name="statut" class="form-control" onchange="this.form.submit()">
                <option value="">Tous les statuts</option>
                <option value="en_attente" <?= ($selectedStatut === 'en_attente') ? 'selected' : '' ?>>En attente</option>
                <option value="validee" <?= ($selectedStatut === 'validee') ? 'selected' : '' ?>>Validée</option>
                <option value="refusee" <?= ($selectedStatut === 'refusee') ? 'selected' : '' ?>>Refusée</option>
                <option value="annulee" <?= ($selectedStatut === 'annulee') ? 'selected' : '' ?>>Annulée</option>
                <option value="terminee" <?= ($selectedStatut === 'terminee') ? 'selected' : '' ?>>Terminée</option>
            </select>
        </div>
    </form>
</div>

<!-- Liste des réservations -->
<div class="table-container">
    <table class="table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Utilisateur</th>
                <th>Lieu</th>
                <th>Date Visite</th>
                <th>Transport</th>
                <th>Coût</th>
                <th>Statut</th>
                <th>Date Réservation</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($reservations)): ?>
            <tr>
                <td colspan="9" style="text-align: center; padding: 3rem; color: #6b7280;">
                    <i class="fas fa-inbox" style="font-size: 3rem; margin-bottom: 1rem; display: block;"></i>
                    Aucune réservation trouvée
                </td>
            </tr>
            <?php else: ?>
            <?php foreach ($reservations as $res): ?>
            <tr>
                <td><strong>#<?= $res['id'] ?></strong></td>
                <td>
                    <?= escape($res['user_prenom'] . ' ' . $res['user_nom']) ?><br>
                    <small style="color: #6b7280;"><?= escape($res['user_email']) ?></small>
                </td>
                <td>
                    <a href="<?= APP_URL ?>/lieu/<?= $res['lieu_slug'] ?>" target="_blank" style="color: var(--primary-color); text-decoration: none;">
                        <?= escape($res['lieu_nom']) ?>
                    </a>
                </td>
                <td>
                    <strong><?= date('d/m/Y', strtotime($res['date_visite'])) ?></strong><br>
                    <small><?= date('H:i', strtotime($res['heure_depart'])) ?></small>
                </td>
                <td>
                    <i class="fas fa-<?= $res['moyen_transport'] === 'moto' ? 'motorcycle' : ($res['moyen_transport'] === 'taxi' ? 'taxi' : 'bus') ?>"></i>
                    <?= ucfirst($res['moyen_transport']) ?>
                </td>
                <td><strong><?= number_format($res['cout_estime']) ?> FCFA</strong></td>
                <td>
                    <?php
                    $badgeClass = [
                        'en_attente' => 'warning',
                        'validee' => 'success',
                        'refusee' => 'danger',
                        'annulee' => 'danger',
                        'terminee' => 'info'
                    ];
                    ?>
                    <span class="badge badge-<?= $badgeClass[$res['statut']] ?>">
                        <?= ucfirst(str_replace('_', ' ', $res['statut'])) ?>
                    </span>
                </td>
                <td><?= date('d/m/Y H:i', strtotime($res['date_reservation'])) ?></td>
                <td>
                    <div class="table-actions">
                        <?php if ($res['statut'] === 'en_attente'): ?>
                        <button onclick="approveReservation(<?= $res['id'] ?>)" class="btn btn-sm btn-success" title="Approuver">
                            <i class="fas fa-check"></i>
                        </button>
                        <button onclick="openRejectModal(<?= $res['id'] ?>)" class="btn btn-sm btn-danger" title="Refuser">
                            <i class="fas fa-times"></i>
                        </button>
                        <?php endif; ?>
                        <button onclick="viewDetails(<?= $res['id'] ?>)" class="btn btn-sm btn-outline" title="Détails">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Modal Refus -->
<div id="rejectModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Refuser la réservation</h3>
            <button class="modal-close" onclick="closeModal('rejectModal')">&times;</button>
        </div>
        <form id="rejectForm">
            <input type="hidden" id="rejectReservationId" name="reservation_id">
            <div class="form-group">
                <label class="form-label">Motif du refus *</label>
                <textarea name="motif" class="form-control" rows="4" required placeholder="Expliquez la raison du refus..."></textarea>
            </div>
            <div style="display: flex; gap: 1rem;">
                <button type="button" onclick="closeModal('rejectModal')" class="btn btn-outline" style="flex: 1;">
                    Annuler
                </button>
                <button type="submit" class="btn btn-danger" style="flex: 1;">
                    <i class="fas fa-times"></i> Confirmer le Refus
                </button>
            </div>
        </form>
    </div>
</div>

<script>
async function approveReservation(id) {
    if (!confirm('Voulez-vous vraiment approuver cette réservation ?')) return;
    
    try {
        const response = await fetch('<?= APP_URL ?>/admin/reservation/approve/' + id, {
            method: 'POST'
        });
        const data = await response.json();
        
        if (data.success) {
            showToast('Réservation approuvée', 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast('Erreur', 'error');
        }
    } catch (error) {
        showToast('Erreur', 'error');
    }
}

function openRejectModal(id) {
    document.getElementById('rejectReservationId').value = id;
    openModal('rejectModal');
}

document.getElementById('rejectForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    
    try {
        const response = await fetch('<?= APP_URL ?>/admin/reservation/reject', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();
        
        if (data.success) {
            showToast('Réservation refusée', 'success');
            closeModal('rejectModal');
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast(data.message || 'Erreur', 'error');
        }
    } catch (error) {
        showToast('Erreur', 'error');
    }
});

function viewDetails(id) {
    // Ouvrir modal avec détails ou rediriger
    alert('Détails de la réservation #' + id);
}
</script>

<?php require_once BASE_PATH . '/views/layouts/admin_footer.php'; ?>
