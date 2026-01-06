<?php require_once BASE_PATH . '/views/layouts/admin_header.php'; ?>

<div class="admin-header">
    <h1 class="admin-header-title">
        <i class="fas fa-user-shield"></i> Gestion des Administrateurs
    </h1>
</div>

<div class="table-container">
    <table class="table">
        <thead>
            <tr>
                <th>Nom</th>
                <th>Email</th>
                <th>Rôle</th>
                <th>Statut</th>
                <th>Dernière Connexion</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($admins as $admin): ?>
            <tr>
                <td><?= escape($admin['prenom'] . ' ' . $admin['nom']) ?></td>
                <td><?= escape($admin['email']) ?></td>
                <td>
                    <span class="badge badge-<?= $admin['role'] === 'superadmin' ? 'danger' : 'primary' ?>">
                        <?= ucfirst($admin['role']) ?>
                    </span>
                </td>
                <td>
                    <span class="badge badge-<?= $admin['statut'] === 'actif' ? 'success' : 'danger' ?>">
                        <?= ucfirst($admin['statut']) ?>
                    </span>
                </td>
                <td>
                    <?= $admin['date_derniere_connexion'] ? date('d/m/Y H:i', strtotime($admin['date_derniere_connexion'])) : 'Jamais' ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once BASE_PATH . '/views/layouts/admin_footer.php'; ?>
