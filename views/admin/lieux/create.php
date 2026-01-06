<?php require_once BASE_PATH . '/views/layouts/admin_header.php'; ?>

<div class="admin-header">
    <h1 class="admin-header-title">
        <i class="fas fa-plus-circle"></i> Ajouter un Lieu Touristique
    </h1>
    <a href="<?= APP_URL ?>/admin/lieux" class="btn btn-outline">
        <i class="fas fa-arrow-left"></i> Retour
    </a>
</div>

<?php if (isset($flash)): ?>
<div class="alert alert-<?= $flash['type'] ?>" data-dismiss="5000">
    <?= $flash['message'] ?>
</div>
<?php endif; ?>

<div class="card">
    <form action="<?= APP_URL ?>/admin/lieu/store" method="POST" enctype="multipart/form-data">
        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem;">
            <!-- Colonne principale -->
            <div>
                <h3 style="margin-bottom: 1.5rem;">Informations Principales</h3>
                
                <div class="form-group">
                    <label class="form-label">Nom du lieu *</label>
                    <input type="text" name="nom" class="form-control" required placeholder="Ex: Lac Tison">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Description *</label>
                    <textarea name="description" class="form-control" rows="6" required placeholder="Description détaillée du lieu..."></textarea>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Catégorie</label>
                    <select name="categorie_id" class="form-control">
                        <option value="">-- Choisir une catégorie --</option>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>">
                            <?= escape($cat['nom']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <h3 style="margin: 2rem 0 1.5rem;">Localisation</h3>
                
                <div class="form-group">
                    <label class="form-label">Adresse</label>
                    <input type="text" name="adresse" class="form-control" placeholder="Adresse complète">
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label class="form-label">Latitude *</label>
                        <input type="number" name="latitude" id="latitude" class="form-control" step="0.000001" required placeholder="7.326667">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Longitude *</label>
                        <input type="number" name="longitude" id="longitude" class="form-control" step="0.000001" required placeholder="13.585000">
                    </div>
                </div>
                
                <div style="height: 300px; border-radius: 8px; overflow: hidden; margin-bottom: 1.5rem;">
                    <div id="mapPicker" style="height: 100%;"></div>
                </div>
                <small style="color: #6b7280;">Cliquez sur la carte pour définir la position</small>
                
                <h3 style="margin: 2rem 0 1.5rem;">Images *</h3>
                
                <div class="form-group">
                    <label class="form-label">Ajouter des images (minimum 1, maximum 10)</label>
                    <input type="file" name="images[]" class="form-control" multiple accept="image/*" required onchange="previewImages(this)">
                    <small style="color: #6b7280;">Formats acceptés: JPG, PNG, WebP (max 5MB par image)</small>
                </div>
                
                <div id="imagePreview" style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; margin-top: 1rem;"></div>
            </div>
            
            <!-- Sidebar -->
            <div>
                <h3 style="margin-bottom: 1.5rem;">Informations Complémentaires</h3>
                
                <div class="form-group">
                    <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                        <input type="checkbox" name="est_gratuit" id="estGratuit" onchange="toggleTarif()">
                        <span class="form-label" style="margin: 0;">Accès gratuit</span>
                    </label>
                </div>
                
                <div class="form-group" id="tarifGroup">
                    <label class="form-label">Tarif (FCFA)</label>
                    <input type="number" name="tarif" class="form-control" min="0" placeholder="0">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Horaires d'ouverture</label>
                    <textarea name="horaires_ouverture" class="form-control" rows="3" placeholder="Lun-Ven: 8h-17h&#10;Sam-Dim: 9h-18h"></textarea>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Téléphone</label>
                    <input type="tel" name="contact_telephone" class="form-control" placeholder="+237 690 000 000">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" name="contact_email" class="form-control" placeholder="contact@lieu.com">
                </div>
                
                <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 2rem;">
                    <i class="fas fa-save"></i> Enregistrer le Lieu
                </button>
            </div>
        </div>
    </form>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
// Initialiser la carte
const map = L.map('mapPicker').setView([7.326667, 13.585000], 13);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);

let marker;

map.on('click', function(e) {
    const { lat, lng } = e.latlng;
    
    document.getElementById('latitude').value = lat.toFixed(6);
    document.getElementById('longitude').value = lng.toFixed(6);
    
    if (marker) {
        marker.setLatLng([lat, lng]);
    } else {
        marker = L.marker([lat, lng]).addTo(map);
    }
});

function toggleTarif() {
    const gratuit = document.getElementById('estGratuit').checked;
    const tarifGroup = document.getElementById('tarifGroup');
    tarifGroup.style.display = gratuit ? 'none' : 'block';
}

function previewImages(input) {
    const preview = document.getElementById('imagePreview');
    preview.innerHTML = '';
    
    if (input.files) {
        Array.from(input.files).slice(0, 10).forEach((file, index) => {
            const reader = new FileReader();
            
            reader.onload = function(e) {
                const div = document.createElement('div');
                div.innerHTML = `
                    <img src="${e.target.result}" style="width: 100%; height: 150px; object-fit: cover; border-radius: 8px; border: 2px solid var(--border-color);">
                    <small style="display: block; text-align: center; margin-top: 0.5rem; color: #6b7280;">Image ${index + 1}</small>
                `;
                preview.appendChild(div);
            };
            
            reader.readAsDataURL(file);
        });
    }
}
</script>

<?php require_once BASE_PATH . '/views/layouts/admin_footer.php'; ?>
