/**
 * Modal Usage - Exempel på användning av File Picker Modal
 */

// Vänta tills DOM är redo
document.addEventListener('DOMContentLoaded', function() {
    // Exempel 1: Välj valfri fil
    const btn1 = document.getElementById('btn-example1');
    if (btn1) {
        btn1.addEventListener('click', function() {
            openFilePicker({
                onSelect: function(file) {
                    showResult('result1', file);
                }
            });
        });
    }

    // Exempel 2: Välj endast bilder
    const btn2 = document.getElementById('btn-example2');
    if (btn2) {
        btn2.addEventListener('click', function() {
            openFilePicker({
                type: 'image',
                onSelect: function(file) {
                    showResult('result2', file, true);
                }
            });
        });
    }

    // Exempel 3: Välj flera filer
    const btn3 = document.getElementById('btn-example3');
    if (btn3) {
        btn3.addEventListener('click', function() {
            openFilePicker({
                multiple: true,
                onSelect: function(files) {
                    const resultDiv = document.getElementById('result3');
                    let html = '<div class="selected-file">';
                    html += '<h3>✓ Valda filer (' + files.length + '):</h3>';
                    html += '<ul>';
                    files.forEach(file => {
                        html += '<li><strong>' + escapeHtml(file.original_name) + '</strong> - ' + formatFileSize(file.file_size) + '</li>';
                    });
                    html += '</ul>';
                    html += '</div>';
                    resultDiv.innerHTML = html;
                }
            });
        });
    }

    // Exempel 4: Välj från specifik mapp
    const folderButtons = document.querySelectorAll('[data-folder]');
    folderButtons.forEach(function(btn) {
        btn.addEventListener('click', function() {
            const folder = this.getAttribute('data-folder');
            openFilePicker({
                folder: folder,
                onSelect: function(file) {
                    showResult('result4', file, file.mime_type.startsWith('image/'));
                }
            });
        });
    });

    // Exempel 5: Välj mapp
    const btn5 = document.getElementById('btn-example5');
    if (btn5) {
        btn5.addEventListener('click', function() {
            openFilePicker({
                mode: 'folder',
                onSelect: function(folder) {
                    const resultDiv = document.getElementById('result5');
                    let html = '<div class="selected-file">';
                    html += '<h3>✓ Vald mapp:</h3>';
                    html += '<p><strong>Mappnamn:</strong> ' + escapeHtml(folder) + '</p>';
                    html += '<p><strong>Sökväg:</strong> /uploads/' + escapeHtml(folder) + '</p>';
                    html += '</div>';
                    resultDiv.innerHTML = html;
                }
            });
        });
    }
});

// Hjälpfunktion för att visa resultat
function showResult(elementId, file, showImage) {
    showImage = showImage || false;
    const resultDiv = document.getElementById(elementId);
    const isImage = file.mime_type.startsWith('image/');

    let html = '<div class="selected-file">';
    html += '<h3>✓ Vald fil:</h3>';
    html += '<p><strong>Namn:</strong> ' + escapeHtml(file.original_name) + '</p>';
    html += '<p><strong>ID:</strong> ' + file.id + '</p>';
    html += '<p><strong>Typ:</strong> <code>' + escapeHtml(file.mime_type) + '</code></p>';
    html += '<p><strong>Storlek:</strong> ' + formatFileSize(file.file_size) + '</p>';
    html += '<p><strong>Sökväg:</strong> ' + escapeHtml(file.file_path) + '</p>';

    if (showImage && isImage) {
        html += '<img src="' + file.url + '" alt="' + escapeHtml(file.original_name) + '">';
    }

    html += '</div>';
    resultDiv.innerHTML = html;
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
