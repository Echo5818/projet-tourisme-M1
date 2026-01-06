<?php require_once BASE_PATH . '/views/layouts/admin_header.php'; ?>

<div class="admin-header">
    <h1 class="admin-header-title">
        <i class="fas fa-chart-line"></i> Dashboard
    </h1>
    <div class="admin-user-info">
        <span>Bienvenue, <?= $_SESSION['user_prenom'] ?></span>
    </div>
</div>

<!-- Stats Cards -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon primary">
            <i class="fas fa-users"></i>
        </div>
        <div class="stat-content">
            <h3><?= $userStats['total'] ?? 0 ?></h3>
            <p>Utilisateurs</p>
            <small style="color: var(--success-color);">
                <i class="fas fa-arrow-up"></i> +<?= $userStats['aujourdhui'] ?? 0 ?> aujourd'hui
            </small>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon success">
            <i class="fas fa-map-marked-alt"></i>
        </div>
        <div class="stat-content">
            <h3><?= $lieuStats['actifs'] ?? 0 ?></h3>
            <p>Lieux Actifs</p>
            <small style="color: #6b7280;">Sur <?= $lieuStats['total'] ?? 0 ?> total</small>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon warning">
            <i class="fas fa-calendar-check"></i>
        </div>
        <div class="stat-content">
            <h3><?= $reservationStats['en_attente'] ?? 0 ?></h3>
            <p>En Attente</p>
            <small style="color: #6b7280;"><?= $reservationStats['total'] ?? 0 ?> réservations totales</small>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon info">
            <i class="fas fa-star"></i>
        </div>
        <div class="stat-content">
            <h3><?= number_format($evaluationStats['note_moyenne'] ?? 0, 1) ?></h3>
            <p>Note Moyenne</p>
            <small style="color: #6b7280;"><?= $evaluationStats['total'] ?? 0 ?> évaluations</small>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
            <i class="fas fa-book"></i>
        </div>
        <div class="stat-content">
            <h3><?= $guideStats['actifs'] ?? 0 ?></h3>
            <p>Guides PDF</p>
            <small style="color: #6b7280;">
                <i class="fas fa-download"></i> <?= number_format($guideStats['total_telechargements'] ?? 0) ?> téléchargements
            </small>
        </div>
    </div>
</div>

<!-- Graphiques -->
<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem; margin-bottom: 2rem;">
    <div class="chart-container">
        <div class="chart-header">
            <h3 class="chart-title">
                <i class="fas fa-chart-line"></i> Réservations Mensuelles
            </h3>
            <small style="color: #6b7280;">Évolution des réservations sur l'année</small>
        </div>
        <canvas id="reservationsChart"></canvas>
    </div>
    
    <div class="chart-container">
        <div class="chart-header">
            <h3 class="chart-title">
                <i class="fas fa-chart-pie"></i> Top Destinations
            </h3>
            <small style="color: #6b7280;">Les lieux les plus réservés</small>
        </div>
        <canvas id="destinationsChart"></canvas>
    </div>
</div>

<!-- Nouveaux graphiques -->
<div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 2rem; margin-bottom: 2rem;">
    <div class="chart-container">
        <div class="chart-header">
            <h3 class="chart-title">
                <i class="fas fa-users"></i> Croissance des Utilisateurs
            </h3>
            <small style="color: #6b7280;">Nouveaux inscrits par mois</small>
        </div>
        <canvas id="usersChart"></canvas>
    </div>
    
    <div class="chart-container">
        <div class="chart-header">
            <h3 class="chart-title">
                <i class="fas fa-star"></i> Distribution des Notes
            </h3>
            <small style="color: #6b7280;">Répartition des évaluations</small>
        </div>
        <canvas id="ratingsChart"></canvas>
    </div>
</div>

<!-- Statistiques de conversion -->
<div class="chart-container" style="margin-bottom: 2rem;">
    <div class="chart-header">
        <h3 class="chart-title">
            <i class="fas fa-funnel-dollar"></i> Entonnoir de Conversion
        </h3>
        <small style="color: #6b7280;">Taux de conversion visiteurs → inscrits → réservations</small>
    </div>
    <canvas id="funnelChart"></canvas>
</div>

<!-- Tables -->
<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
    <div class="table-container">
        <div style="padding: 1.5rem; border-bottom: 2px solid var(--border-color);">
            <h3>Réservations Récentes</h3>
        </div>
        <table class="table">
            <thead>
                <tr>
                    <th>Utilisateur</th>
                    <th>Lieu</th>
                    <th>Date</th>
                    <th>Statut</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recentReservations as $res): ?>
                <tr>
                    <td><?= escape($res['user_prenom']) ?></td>
                    <td><?= escape($res['lieu_nom']) ?></td>
                    <td><?= date('d/m/Y', strtotime($res['date_visite'])) ?></td>
                    <td><span class="badge badge-warning">En attente</span></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <div class="table-container">
        <div style="padding: 1.5rem; border-bottom: 2px solid var(--border-color);">
            <h3>Nouveaux Utilisateurs</h3>
        </div>
        <table class="table">
            <thead>
                <tr>
                    <th>Nom</th>
                    <th>Email</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recentUsers as $user): ?>
                <tr>
                    <td><?= escape($user['prenom'] . ' ' . $user['nom']) ?></td>
                    <td><?= escape($user['email']) ?></td>
                    <td><?= date('d/m/Y', strtotime($user['date_inscription'])) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
// Graphique des réservations mensuelles
const monthlyData = <?= json_encode($monthlyReservations) ?>;
const months = ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin', 'Juil', 'Août', 'Sep', 'Oct', 'Nov', 'Déc'];
const reservationCounts = new Array(12).fill(0);

monthlyData.forEach(item => {
    reservationCounts[item.mois - 1] = item.total;
});

new Chart(document.getElementById('reservationsChart'), {
    type: 'line',
    data: {
        labels: months,
        datasets: [{
            label: 'Réservations',
            data: reservationCounts,
            borderColor: '#E8991C',
            backgroundColor: 'rgba(232, 153, 28, 0.1)',
            tension: 0.4,
            fill: true
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: { display: false }
        }
    }
});

// Graphique des destinations populaires
const destinationsData = <?= json_encode($popularDestinations) ?>;
new Chart(document.getElementById('destinationsChart'), {
    type: 'doughnut',
    data: {
        labels: destinationsData.map(d => d.nom),
        datasets: [{
            data: destinationsData.map(d => d.total_reservations),
            backgroundColor: ['#E8991C', '#1E5A8E', '#3B9C8F', '#10b981', '#f59e0b', '#ef4444']
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    }
});

// Graphique de croissance des utilisateurs
const usersGrowthData = <?= json_encode($userGrowth ?? []) ?>;
const userMonths = usersGrowthData.map(d => months[d.mois - 1]);
const userCounts = usersGrowthData.map(d => d.total);

new Chart(document.getElementById('usersChart'), {
    type: 'bar',
    data: {
        labels: userMonths,
        datasets: [{
            label: 'Nouveaux utilisateurs',
            data: userCounts,
            backgroundColor: '#1E5A8E',
            borderColor: '#1E5A8E',
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: { display: false }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    stepSize: 1
                }
            }
        }
    }
});

// Graphique de distribution des notes
const ratingsData = <?= json_encode($ratingsDistribution ?? []) ?>;
new Chart(document.getElementById('ratingsChart'), {
    type: 'bar',
    data: {
        labels: ['1★', '2★', '3★', '4★', '5★'],
        datasets: [{
            label: 'Nombre d\'évaluations',
            data: [
                ratingsData['1'] || 0,
                ratingsData['2'] || 0,
                ratingsData['3'] || 0,
                ratingsData['4'] || 0,
                ratingsData['5'] || 0
            ],
            backgroundColor: ['#ef4444', '#f59e0b', '#eab308', '#84cc16', '#10b981'],
            borderRadius: 6
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: { display: false }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    stepSize: 1
                }
            }
        }
    }
});

// Graphique entonnoir de conversion
const conversionData = <?= json_encode($conversionStats ?? ['visiteurs' => 0, 'inscrits' => 0, 'reservations' => 0]) ?>;
new Chart(document.getElementById('funnelChart'), {
    type: 'bar',
    data: {
        labels: ['Visiteurs', 'Utilisateurs Inscrits', 'Réservations'],
        datasets: [{
            label: 'Nombre',
            data: [
                conversionData.visiteurs || 1000,
                conversionData.inscrits || <?= $userStats['total'] ?? 0 ?>,
                conversionData.reservations || <?= $reservationStats['total'] ?? 0 ?>
            ],
            backgroundColor: [
                'rgba(30, 90, 142, 0.8)',
                'rgba(232, 153, 28, 0.8)',
                'rgba(16, 185, 129, 0.8)'
            ],
            borderColor: [
                '#1E5A8E',
                '#E8991C',
                '#10b981'
            ],
            borderWidth: 2
        }]
    },
    options: {
        indexAxis: 'y',
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: { display: false },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        let label = context.parsed.x;
                        let percentage = 0;
                        if (context.dataIndex > 0) {
                            let previous = context.chart.data.datasets[0].data[context.dataIndex - 1];
                            percentage = ((context.parsed.x / previous) * 100).toFixed(1);
                            return label + ' (' + percentage + '% du précédent)';
                        }
                        return label;
                    }
                }
            }
        },
        scales: {
            x: {
                beginAtZero: true
            }
        }
    }
});
</script>

<?php require_once BASE_PATH . '/views/layouts/admin_footer.php'; ?>
