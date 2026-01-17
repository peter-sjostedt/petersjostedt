/**
 * Partner Portal - Events JavaScript
 */

document.addEventListener('DOMContentLoaded', function() {
    // View buttons
    document.querySelectorAll('[data-event-view]').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const eventData = this.getAttribute('data-event-view');
            const event = JSON.parse(eventData);
            openViewEventModal(event);
        });
    });
});

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function openViewEventModal(event) {
    const labels = JSON.parse(document.querySelector('meta[name="event-labels"]').getAttribute('content'));

    const eventAt = event.event_at ? new Date(event.event_at).toLocaleString('sv-SE') : '-';
    const typeLabel = labels[event.event_type] || event.event_type;

    let rows = '';

    // Visa olika fält beroende på händelsetyp
    switch (event.event_type) {
        case 'rfid_link':
            rows += `
                <tr>
                    <td style="padding: 0.5rem; width: 40%; color: #666;">${escapeHtml(labels.article)}</td>
                    <td style="padding: 0.5rem;"><strong>${escapeHtml(event.sku || event.article_id || '-')}</strong></td>
                </tr>
                <tr>
                    <td style="padding: 0.5rem; color: #666;">${escapeHtml(labels.rfid)}</td>
                    <td style="padding: 0.5rem;"><code>${escapeHtml(event.rfid || '-')}</code></td>
                </tr>
            `;
            break;

        case 'rfid_register':
            rows += `
                <tr>
                    <td style="padding: 0.5rem; width: 40%; color: #666;">${escapeHtml(labels.rfid)}</td>
                    <td style="padding: 0.5rem;"><code>${escapeHtml(event.rfid || '-')}</code></td>
                </tr>
            `;
            break;

        case 'inventory':
            rows += `
                <tr>
                    <td style="padding: 0.5rem; width: 40%; color: #666;">Antal</td>
                    <td style="padding: 0.5rem;"><strong>${escapeHtml(String(event.count || 0))}</strong></td>
                </tr>
            `;
            if (event.items && Array.isArray(event.items)) {
                rows += `
                    <tr>
                        <td style="padding: 0.5rem; color: #666;">Artiklar</td>
                        <td style="padding: 0.5rem;">${event.items.length} st</td>
                    </tr>
                `;
            }
            break;

        case 'receive':
            rows += `
                <tr>
                    <td style="padding: 0.5rem; width: 40%; color: #666;">Inleverans-ID</td>
                    <td style="padding: 0.5rem;"><strong>${escapeHtml(event.delivery_id || '-')}</strong></td>
                </tr>
            `;
            if (event.rfid) {
                rows += `
                    <tr>
                        <td style="padding: 0.5rem; color: #666;">${escapeHtml(labels.rfid)}</td>
                        <td style="padding: 0.5rem;"><code>${escapeHtml(event.rfid)}</code></td>
                    </tr>
                `;
            }
            break;

        default:
            // Visa all metadata som JSON
            rows += `
                <tr>
                    <td style="padding: 0.5rem; color: #666;" colspan="2">
                        <pre style="margin: 0; white-space: pre-wrap; font-size: 0.85em;">${escapeHtml(JSON.stringify(event, null, 2))}</pre>
                    </td>
                </tr>
            `;
    }

    // Gemensamma fält
    if (event.unit_id) {
        rows += `
            <tr>
                <td style="padding: 0.5rem; color: #666;">${escapeHtml(labels.unit)}</td>
                <td style="padding: 0.5rem;">${escapeHtml(event.unit_id)}</td>
            </tr>
        `;
    }

    rows += `
        <tr>
            <td style="padding: 0.5rem; color: #666;">${escapeHtml(labels.timestamp)}</td>
            <td style="padding: 0.5rem;">${escapeHtml(eventAt)}</td>
        </tr>
    `;

    const content = `
        <table style="width: 100%; border-collapse: collapse;">
            ${rows}
        </table>
    `;

    Modal.custom('info', typeLabel, content, {
        html: true,
        hideClose: false,
        width: '450px',
        buttons: [
            { text: labels.close || 'Stäng', class: 'cancel', value: false }
        ]
    });
}
