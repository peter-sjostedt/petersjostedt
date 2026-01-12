/**
 * Test Modal Integration - Formulärintegration för File Picker Modal
 */

// Bildgalleri (flera bilder)
let galleryImages = [];

// Vänta tills DOM är redo
document.addEventListener('DOMContentLoaded', function() {
    // Utvald bild (en bild)
    const selectFeaturedBtn = document.getElementById('select-featured');
    if (selectFeaturedBtn) {
        selectFeaturedBtn.addEventListener('click', function() {
            openFilePicker({
                type: 'image',
                onSelect: function(file) {
                    document.getElementById('featured-image-id').value = file.id;
                    document.getElementById('featured-image').src = file.url;
                    document.getElementById('featured-image').style.display = 'block';
                    document.getElementById('featured-preview').classList.add('has-image');
                }
            });
        });
    }

    const removeFeaturedBtn = document.getElementById('remove-featured');
    if (removeFeaturedBtn) {
        removeFeaturedBtn.addEventListener('click', function() {
            document.getElementById('featured-image-id').value = '';
            document.getElementById('featured-image').src = '';
            document.getElementById('featured-image').style.display = 'none';
            document.getElementById('featured-preview').classList.remove('has-image');
        });
    }

    // Bildgalleri (flera bilder)
    const selectGalleryBtn = document.getElementById('select-gallery');
    if (selectGalleryBtn) {
        selectGalleryBtn.addEventListener('click', function() {
            openFilePicker({
                type: 'image',
                multiple: true,
                onSelect: function(files) {
                    // Lägg till nya bilder (undvik dubbletter)
                    files.forEach(file => {
                        if (!galleryImages.find(img => img.id === file.id)) {
                            galleryImages.push(file);
                        }
                    });
                    updateGalleryPreview();
                }
            });
        });
    }

    // Dokument
    const selectDocumentBtn = document.getElementById('select-document');
    if (selectDocumentBtn) {
        selectDocumentBtn.addEventListener('click', function() {
            openFilePicker({
                type: 'document',
                onSelect: function(file) {
                    document.getElementById('document-id').value = file.id;
                    const info = document.getElementById('document-info');
                    info.innerHTML = `
                        <div><strong>${escapeHtml(file.original_name)}</strong></div>
                        <div style="color: #666; font-size: 0.9rem; margin-top: 0.25rem;">${formatFileSize(file.file_size)}</div>
                    `;
                    info.style.display = 'block';
                    document.getElementById('document-preview').classList.add('has-image');
                }
            });
        });
    }

    const removeDocumentBtn = document.getElementById('remove-document');
    if (removeDocumentBtn) {
        removeDocumentBtn.addEventListener('click', function() {
            document.getElementById('document-id').value = '';
            document.getElementById('document-info').style.display = 'none';
            document.getElementById('document-preview').classList.remove('has-image');
        });
    }
});

function removeGalleryImage(index) {
    galleryImages.splice(index, 1);
    updateGalleryPreview();
}

function updateGalleryPreview() {
    const preview = document.getElementById('gallery-preview');
    const idsInput = document.getElementById('gallery-ids');

    if (galleryImages.length === 0) {
        preview.className = 'gallery-placeholder';
        preview.innerHTML = 'Inga bilder i galleriet ännu';
        idsInput.value = '';
        return;
    }

    preview.className = 'gallery-preview';
    preview.innerHTML = '';

    galleryImages.forEach((file, index) => {
        const item = document.createElement('div');
        item.className = 'gallery-item';

        const img = document.createElement('img');
        img.src = file.url;
        img.alt = escapeHtml(file.original_name);

        const removeBtn = document.createElement('button');
        removeBtn.type = 'button';
        removeBtn.className = 'remove-btn';
        removeBtn.textContent = '×';
        removeBtn.addEventListener('click', function() {
            removeGalleryImage(index);
        });

        item.appendChild(img);
        item.appendChild(removeBtn);
        preview.appendChild(item);
    });

    // Uppdatera hidden field med alla ID:n
    idsInput.value = galleryImages.map(img => img.id).join(',');
}

// Hjälpfunktioner
function formatFileSize(bytes) {
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
    return (bytes / 1024 / 1024).toFixed(1) + ' MB';
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
