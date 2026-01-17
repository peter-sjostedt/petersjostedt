/**
 * QR Modal - Generell funktion för att visa QR-kod i modal
 *
 * Användning:
 *   QR.show({
 *       data: { type: 'sku', org_id: 'X', sku: '123' },
 *       title: 'SKU: 123',
 *       subtitle: 'Org: X\nProdukt: ABC',
 *       filename: 'SKU_123'
 *   });
 */

const QR = (function() {
    'use strict';

    let qrLibLoaded = false;

    function loadQRLibrary() {
        return new Promise((resolve, reject) => {
            if (qrLibLoaded || typeof QRCode !== 'undefined') {
                qrLibLoaded = true;
                resolve();
                return;
            }

            const script = document.createElement('script');
            // Använd lokal kopia för att undvika CSP-problem
            const basePath = document.querySelector('script[src*="qr.js"]')?.src.replace(/qr\.js.*$/, '') || '/assets/js/';
            script.src = basePath + 'qrcode.min.js';
            script.onload = () => {
                qrLibLoaded = true;
                resolve();
            };
            script.onerror = reject;
            document.head.appendChild(script);
        });
    }

    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function show(options) {
        const { data, title, subtitle, filename } = options;

        loadQRLibrary().then(() => {
            const subtitleHtml = subtitle
                ? `<p class="text-muted" style="white-space: pre-line;">${escapeHtml(subtitle)}</p>`
                : '';

            const content = `
                <div style="text-align: center;">
                    <div id="qr-code-container" style="display: inline-block; padding: 20px; background: white;"></div>
                    <p style="margin-top: 1rem; font-size: 1.1rem;">
                        <strong>${escapeHtml(title)}</strong>
                    </p>
                    ${subtitleHtml}
                </div>
            `;

            Modal.custom('info', 'QR-kod', content, {
                html: true,
                hideClose: false,
                centerTitle: true,
                width: '400px',
                buttons: [
                    { text: 'Stäng', class: 'cancel', value: false },
                    { text: 'Ladda ner', class: 'primary', value: 'download' }
                ]
            }).then((result) => {
                if (result === 'download') {
                    download(title, subtitle, filename);
                }
            });

            // Skapa QR-kod efter att modal visats
            setTimeout(() => {
                const container = document.getElementById('qr-code-container');
                if (container) {
                    new QRCode(container, {
                        text: JSON.stringify(data),
                        width: 200,
                        height: 200,
                        colorDark: '#000000',
                        colorLight: '#ffffff',
                        correctLevel: QRCode.CorrectLevel.M
                    });
                }
            }, 100);
        });
    }

    function download(title, subtitle, filename) {
        const container = document.getElementById('qr-code-container');
        if (!container) return;

        const canvas = container.querySelector('canvas');
        if (!canvas) return;

        const padding = 20;
        const subtitleLines = subtitle ? subtitle.split('\n') : [];
        const textHeight = 40 + (subtitleLines.length * 16);

        const newCanvas = document.createElement('canvas');
        newCanvas.width = canvas.width + padding * 2;
        newCanvas.height = canvas.height + padding * 2 + textHeight;

        const ctx = newCanvas.getContext('2d');

        ctx.fillStyle = '#ffffff';
        ctx.fillRect(0, 0, newCanvas.width, newCanvas.height);

        ctx.drawImage(canvas, padding, padding);

        ctx.fillStyle = '#000000';
        ctx.font = 'bold 16px Arial';
        ctx.textAlign = 'center';
        ctx.fillText(title, newCanvas.width / 2, canvas.height + padding + 25);

        if (subtitle) {
            ctx.font = '12px Arial';
            ctx.fillStyle = '#666666';
            subtitleLines.forEach((line, i) => {
                ctx.fillText(line, newCanvas.width / 2, canvas.height + padding + 45 + (i * 16));
            });
        }

        const link = document.createElement('a');
        link.download = filename.replace(/[^a-zA-Z0-9-_]/g, '_') + '.png';
        link.href = newCanvas.toDataURL('image/png');
        link.click();
    }

    return {
        show: show
    };
})();
