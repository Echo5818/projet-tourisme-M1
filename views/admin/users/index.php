<?php require_once BASE_PATH . '/views/layouts/admin_header.php'; ?>

<div class="admin-header">
    <h1 class="admin-header-title">
        <i class="fas fa-users"></i> Gestion des Utilisateurs
    </h1>
</div>

<div class="table-container">
    <table class="table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nom</th>
                <th>Email</th>
                <th>Téléphone</th>
                <th>Date Inscription</th>
                <th>Statut</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $user): ?>
            <tr>
                <td><?= $user['id'] ?></td>
                <td><?= escape($user['prenom'] . ' ' . $user['nom']) ?></td>
                <td><?= escape($user['email']) ?></td>
                <td><?= escape($user['telephone'] ?? '-') ?></td>
                <td><?= date('d/m/Y', strtotime($user['date_inscription'])) ?></td>
                <td>
                    <span class="badge badge-<?= $user['statut'] === 'actif' ? 'success' : 'danger' ?>">
                        <?= ucfirst($user['statut']) ?>
                    </span>
                </td>
                <td>
                    <button class="btn btn-sm btn-outline" title="Voir détails">
                        <i class="fas fa-eye"></i>
                    </button>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once BASE_PATH . '/views/layouts/admin_footer.php'; ?>
