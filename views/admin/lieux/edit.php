<?php require_once BASE_PATH . '/views/layouts/admin_header.php'; ?>

<div class="admin-header">
    <h1 class="admin-header-title">
        <i class="fas fa-edit"></i> Modifier le Lieu
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
    <form action="<?= APP_URL ?>/admin/lieu/update/<?= $lieu['id'] ?>" method="POST" enctype="multipart/form-data">
        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem;">
            <!-- Colonne principale -->
            <div>
                <h3 style="margin-bottom: 1.5rem;">Informations Principales</h3>
                
                <div class="form-group">
                    <label class="form-label">Nom du lieu *</label>
                    <input type="text" name="nom" class="form-control" required 
                           value="<?= escape($lieu['nom']) ?>" placeholder="Ex: Lac Tison">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Description *</label>
                    <textarea name="description" class="form-control" rows="6" required 
                              placeholder="Description détaillée du lieu..."><?= escape($lieu['description']) ?></textarea>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Catégorie</label>
                    <select name="categorie_id" class="form-control">
                        <option value="">-- Choisir une catégorie --</option>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= $lieu['categorie_id'] == $cat['id'] ? 'selected' : '' ?>>
                            <?= escape($cat['nom']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <h3 style="margin: 2rem 0 1.5rem;">Localisation</h3>
                
                <div class="form-group">
                    <label class="form-label">Adresse</label>
                    <input type="text" name="adresse" class="form-control" 
                           value="<?= escape($lieu['adresse'] ?? '') ?>" placeholder="Adresse complète">
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label class="form-label">Latitude *</label>
                        <input type="number" name="latitude" id="latitude" class="form-control" 
                               step="0.000001" required value="<?= $lieu['latitude'] ?>" placeholder="7.326667">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Longitude *</label>
                        <input type="number" name="longitude" id="longitude" class="form-control" 
                               step="0.000001" required value="<?= $lieu['longitude'] ?>" placeholder="13.585000">
                    </div>
                </div>
                
                <div style="height: 300px; border-radius: 8px; overflow: hidden; margin-bottom: 1.5rem;">
                    <div id="mapPicker" style="height: 100%;"></div>
                </div>
                <small style="color: #6b7280;">Cliquez sur la carte pour mettre à jour la position</small>
                
                <h3 style="margin: 2rem 0 1.5rem;">Images</h3>
                
                <!-- Images existantes -->
                <?php if (!empty($images)): ?>
                <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; margin-bottom: 1rem;">
                    <?php foreach ($images as $img): ?>
                    <div style="position: relative; border: 2px solid <?= $img['est_principale'] ? 'var(--primary-color)' : 'var(--border-color)' ?>; border-radius: 8px; overflow: hidden;">
                        <img src="<?= APP_URL ?>/<?= $img['chemin_image'] ?>" 
                             style="width: 100%; height: 150px; object-fit: cover;">
                        <?php if ($img['est_principale']): ?>
                        <span style="position: absolute; top: 5px; left: 5px; background: var(--primary-color); color: white; padding: 2px 8px; border-radius: 4px; font-size: 0.75rem;">
                            Principale
                        </span>
                        <?php endif; ?>
                        <div style="position: absolute; top: 5px; right: 5px; display: flex; gap: 5px;">
                            <?php if (!$img['est_principale']): ?>
                            <button type="button" onclick="setPrincipale(<?= $img['id'] ?>, <?= $lieu['id'] ?>)" 
                                    class="btn btn-sm btn-success" title="Définir comme principale">
                                <i class="fas fa-star"></i>
                            </button>
                            <?php endif; ?>
                            <button type="button" onclick="deleteImage(<?= $img['id'] ?>)" 
                                    class="btn btn-sm btn-danger" title="Supprimer">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                
                <div class="form-group">
                    <label class="form-label">Ajouter de nouvelles images</label>
                    <input type="file" name="images[]" class="form-control" multiple accept="image/*" onchange="previewImages(this)">
                    <small style="color: #6b7280;">Formats acceptés: JPG, PNG, WebP (max 5MB par image)</small>
                </div>
                
                <div id="imagePreview" style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; margin-top: 1rem;"></div>
            </div>
            
            <!-- Sidebar -->
            <div>
                <h3 style="margin-bottom: 1.5rem;">Paramètres</h3>
                
                <div class="form-group">
                    <label class="form-label">Statut</label>
                    <select name="statut" class="form-control">
                        <option value="actif" <?= $lieu['statut'] === 'actif' ? 'selected' : '' ?>>Actif</option>
                        <option value="inactif" <?= $lieu['statut'] === 'inactif' ? 'selected' : '' ?>>Inactif</option>
                        <option value="archive" <?= $lieu['statut'] === 'archive' ? 'selected' : '' ?>>Archivé</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                        <input type="checkbox" name="est_gratuit" id="estGratuit" 
                               <?= $lieu['est_gratuit'] ? 'checked' : '' ?> onchange="toggleTarif()">
                        <span class="form-label" style="margin: 0;">Accès gratuit</span>
                    </label>
                </div>
                
                <div class="form-group" id="tarifGroup" style="<?= $lieu['est_gratuit'] ? 'display:none;' : '' ?>">
                    <label class="form-label">Tarif (FCFA)</label>
                    <input type="number" name="tarif" class="form-control" min="0" 
                           value="<?= $lieu['tarif'] ?>" placeholder="0">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Horaires d'ouverture</label>
                    <textarea name="horaires_ouverture" class="form-control" rows="3" 
                              placeholder="Lun-Ven: 8h-17h&#10;Sam-Dim: 9h-18h"><?= escape($lieu['horaires_ouverture'] ?? '') ?></textarea>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Téléphone</label>
                    <input type="tel" name="contact_telephone" class="form-control" 
                           value="<?= escape($lieu['contact_telephone'] ?? '') ?>" placeholder="+237 690 000 000">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" name="contact_email" class="form-control" 
                           value="<?= escape($lieu['contact_email'] ?? '') ?>" placeholder="contact@lieu.com">
                </div>
                
                <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 2rem;">
                    <i class="fas fa-save"></i> Enregistrer les Modifications
                </button>
                
                <a href="<?= APP_URL ?>/lieu/<?= $lieu['slug'] ?>" 
                   target="_blank" class="btn btn-outline" style="width: 100%; margin-top: 1rem;">
                    <i class="fas fa-eye"></i> Voir le Lieu
                </a>
            </div>
        </div>
    </form>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
// Initialiser la carte
const map = L.map('mapPicker').setView([<?= $lieu['latitude'] ?>, <?= $lieu['longitude'] ?>], 13);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);

let marker = L.marker([<?= $lieu['latitude'] ?>, <?= $lieu['longitude'] ?>]).addTo(map);

map.on('click', function(e) {
    const { lat, lng } = e.latlng;
    document.getElementById('latitude').value = lat.toFixed(6);
    document.getElementById('longitude').value = lng.toFixed(6);
    marker.setLatLng([lat, lng]);
});

function toggleTarif() {
    const gratuit = document.getElementById('estGratuit').checked;
    document.getElementById('tarifGroup').style.display = gratuit ? 'none' : 'block';
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
                    <small style="display: block; text-align: center; margin-top: 0.5rem; color: #6b7280;">Nouvelle ${index + 1}</small>
                `;
                preview.appendChild(div);
            };
            reader.readAsDataURL(file);
        });
    }
}

async function deleteImage(imageId) {
    if (!confirm('Supprimer cette image ?')) return;
    
    try {
        const response = await fetch('<?= APP_URL ?>/admin/lieu/delete-image/' + imageId, {
            method: 'POST'
        });
        const data = await response.json();
        
        if (data.success) {
            showToast('Image supprimée', 'success');
            setTimeout(() => location.reload(), 1000);
        }
    } catch (error) {
        showToast('Erreur', 'error');
    }
}

async function setPrincipale(imageId, lieuId) {
    try {
        const formData = new URLSearchParams();
        formData.append('image_id', imageId);
        formData.append('lieu_id', lieuId);
        
        const response = await fetch('<?= APP_URL ?>/admin/lieu/set-principale', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();
        
        if (data.success) {
            showToast('Image principale définie', 'success');
            setTimeout(() => location.reload(), 1000);
        }
    } catch (error) {
        showToast('Erreur', 'error');
    }
}
</script>

<?php require_once BASE_PATH . '/views/layouts/admin_footer.php'; ?>
