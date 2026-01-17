<?php
/**
 * Partner Portal - Export av artiklar till CSV
 */

require_once __DIR__ . '/../includes/config.php';

Session::start();

if (!Session::isLoggedIn() || !Session::isOrgAdmin()) {
    header('Location: login.php');
    exit;
}

$organizationId = Session::getOrganizationId();

if (!$organizationId) {
    Session::flash('error', t('error.unauthorized'));
    header('Location: index.php');
    exit;
}

$type = $_GET['type'] ?? '';
$tab = $_GET['tab'] ?? 'outgoing';

if (!in_array($type, ['articles', 'templates', 'shipments', 'relations'])) {
    Session::flash('error', 'Ogiltig exporttyp');
    header('Location: index.php');
    exit;
}

// Hämta organisation med article_schema
$orgModel = new Organization();
$organization = $orgModel->findById($organizationId);

$articleSchema = [];
if (!empty($organization['article_schema'])) {
    if (is_string($organization['article_schema'])) {
        $articleSchema = json_decode($organization['article_schema'], true) ?: [];
    } else {
        $articleSchema = $organization['article_schema'];
    }
    usort($articleSchema, fn($a, $b) => ((int)($a['sort_order'] ?? 0)) - ((int)($b['sort_order'] ?? 0)));
}

function fieldKey(string $label): string
{
    $key = mb_strtolower($label);
    $key = str_replace(['å', 'ä', 'ö'], ['a', 'a', 'o'], $key);
    $key = preg_replace('/[^a-z0-9]/', '_', $key);
    $key = preg_replace('/_+/', '_', $key);
    return trim($key, '_');
}

$db = Database::getInstance()->getPdo();
$safeOrgName = preg_replace('/[^a-zA-Z0-9]/', '_', $organization['name'] ?? 'export');

if ($type === 'articles') {
    // === EXPORT ARTIKLAR ===
    $stmt = $db->prepare("SELECT * FROM articles WHERE organization_id = ? ORDER BY sku");
    $stmt->execute([$organizationId]);
    $articles = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $filename = $safeOrgName . '_artiklar_' . date('Y-m-d') . '.csv';

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $output = fopen('php://output', 'w');
    fwrite($output, "\xEF\xBB\xBF");

    // Rubrikrad
    $headers = ['SKU'];
    foreach ($articleSchema as $field) {
        $headers[] = $field['label'];
    }
    fputcsv($output, $headers, ';');

    // Artikelrader
    foreach ($articles as $article) {
        $data = json_decode($article['data'] ?? '{}', true) ?: [];

        $row = [$article['sku']];
        foreach ($articleSchema as $field) {
            $key = fieldKey($field['label']);
            $row[] = $data[$key] ?? '';
        }

        fputcsv($output, $row, ';');
    }

    fclose($output);
    exit;

} elseif ($type === 'templates') {
    // === EXPORT HÄNDELSEMALLAR ===

    // Hämta enheter för namn-lookup
    $stmt = $db->prepare("SELECT id, name FROM units WHERE organization_id = ?");
    $stmt->execute([$organizationId]);
    $units = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $u) {
        $units[$u['id']] = $u['name'];
    }

    // Hämta händelsetyper
    $eventTypeModel = new EventType();
    $eventTypes = [];
    $lang = Language::getInstance()->getLanguage();
    foreach ($eventTypeModel->getAll() as $et) {
        $eventTypes[$et['id']] = [
            'code' => $et['code'],
            'name' => $et["name_{$lang}"] ?? $et['name_sv']
        ];
    }

    // Hämta mallar
    $templateModel = new EventTemplate();
    $templates = $templateModel->findByOrganization($organizationId);

    $filename = $safeOrgName . '_mallar_' . date('Y-m-d') . '.csv';

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $output = fopen('php://output', 'w');
    fwrite($output, "\xEF\xBB\xBF");

    // Rubrikrad
    $headers = [
        t('partner.templates.table.label'),
        t('partner.templates.table.event_type'),
        t('partner.templates.table.unit'),
        t('partner.templates.table.target_unit'),
        t('partner.templates.table.reusable'),
        t('partner.templates.form.notes'),
        t('partner.templates.table.created')
    ];
    fputcsv($output, $headers, ';');

    // Mallrader
    foreach ($templates as $template) {
        $eventType = $eventTypes[$template['event_type_id']] ?? ['code' => '', 'name' => ''];
        $unitName = $template['unit_id'] ? ($units[$template['unit_id']] ?? '') : '';
        $targetUnitName = $template['target_unit_id'] ? ($units[$template['target_unit_id']] ?? '') : '';
        $isReusable = $template['is_reusable'] ? t('common.yes') : t('common.no');

        $row = [
            $template['label'],
            $eventType['code'],
            $unitName,
            $targetUnitName,
            $isReusable,
            $template['notes'] ?? '',
            date('Y-m-d H:i', strtotime($template['created_at']))
        ];

        fputcsv($output, $row, ';');
    }

    fclose($output);
    exit;

} elseif ($type === 'shipments') {
    // === EXPORT FÖRSÄNDELSER ===

    $shipmentModel = new Shipment();

    // Hämta försändelser baserat på flik (filnamn språkhanterat)
    if ($tab === 'incoming') {
        $shipments = $shipmentModel->findIncoming($organizationId);
        $filenameType = t('partner.shipments.export.filename_incoming');
    } else {
        $shipments = $shipmentModel->findOutgoing($organizationId);
        $filenameType = t('partner.shipments.export.filename_outgoing');
    }
    $filename = $safeOrgName . '_' . $filenameType . '_' . date('Y-m-d') . '.csv';

    // Hämta organisationsnamn för lookup
    $stmt = $db->prepare("SELECT id, name FROM organizations");
    $stmt->execute();
    $orgs = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $o) {
        $orgs[$o['id']] = $o['name'];
    }

    // Hämta enheter för lookup
    $stmt = $db->prepare("SELECT id, name FROM units WHERE organization_id = ?");
    $stmt->execute([$organizationId]);
    $unitNames = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $u) {
        $unitNames[$u['id']] = $u['name'];
    }

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $output = fopen('php://output', 'w');
    fwrite($output, "\xEF\xBB\xBF");

    // Rubrikrad - alla ID:n för support och spårbarhet (typ framgår av filnamnet)
    if ($tab === 'outgoing') {
        $headers = [
            t('partner.shipments.table.qr_code'),
            'from_org_id',
            t('partner.shipments.table.customer'),
            'to_org_id',
            t('partner.shipments.form.from_unit'),
            'from_unit_id',
            t('partner.shipments.form.sales_order_id'),
            t('partner.shipments.form.purchase_order_id'),
            t('partner.shipments.table.status'),
            t('partner.shipments.form.notes'),
            t('partner.shipments.table.created')
        ];
    } else {
        $headers = [
            t('partner.shipments.table.qr_code'),
            t('partner.shipments.table.supplier'),
            'from_org_id',
            'to_org_id',
            t('partner.shipments.form.to_unit'),
            'to_unit_id',
            t('partner.shipments.form.sales_order_id'),
            t('partner.shipments.form.purchase_order_id'),
            t('partner.shipments.table.status'),
            t('partner.shipments.form.notes'),
            t('partner.shipments.table.created')
        ];
    }
    fputcsv($output, $headers, ';');

    // Försändelserader
    foreach ($shipments as $shipment) {
        $statusLabel = t('partner.shipments.status.' . $shipment['status']);

        if ($tab === 'outgoing') {
            $partnerOrgId = $shipment['to_org_id'];
            $partnerName = $orgs[$partnerOrgId] ?? $partnerOrgId;
            $unitId = $shipment['from_unit_id'];
            $unitName = $unitId ? ($unitNames[$unitId] ?? '') : '';

            $row = [
                $shipment['qr_code'],
                $shipment['from_org_id'],
                $partnerName,
                $partnerOrgId,
                $unitName,
                $unitId ?? '',
                $shipment['sales_order_id'] ?? '',
                $shipment['purchase_order_id'] ?? '',
                $statusLabel,
                $shipment['notes'] ?? '',
                date('Y-m-d H:i', strtotime($shipment['created_at']))
            ];
        } else {
            $partnerOrgId = $shipment['from_org_id'];
            $partnerName = $orgs[$partnerOrgId] ?? $partnerOrgId;
            $unitId = $shipment['to_unit_id'];
            $unitName = $unitId ? ($unitNames[$unitId] ?? '') : '';

            $row = [
                $shipment['qr_code'],
                $partnerName,
                $partnerOrgId,
                $shipment['to_org_id'],
                $unitName,
                $unitId ?? '',
                $shipment['sales_order_id'] ?? '',
                $shipment['purchase_order_id'] ?? '',
                $statusLabel,
                $shipment['notes'] ?? '',
                date('Y-m-d H:i', strtotime($shipment['created_at']))
            ];
        }

        fputcsv($output, $row, ';');
    }

    fclose($output);
    exit;

} elseif ($type === 'relations') {
    // === EXPORT RELATIONER ===

    // Bestäm relationstyp baserat på flik
    $relationType = ($tab === 'suppliers') ? 'supplier' : 'customer';

    // Hämta relationer
    $stmt = $db->prepare("
        SELECT r.id, r.partner_org_id, r.relation_type, r.is_active, r.created_at,
               o.name as partner_name
        FROM organization_relations r
        JOIN organizations o ON r.partner_org_id = o.id
        WHERE r.organization_id = ? AND r.relation_type = ?
        ORDER BY o.name
    ");
    $stmt->execute([$organizationId, $relationType]);
    $relations = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Filnamn
    $filenameType = ($tab === 'suppliers')
        ? t('partner.relations.export.filename_suppliers')
        : t('partner.relations.export.filename_customers');
    $filename = $safeOrgName . '_' . $filenameType . '_' . date('Y-m-d') . '.csv';

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $output = fopen('php://output', 'w');
    fwrite($output, "\xEF\xBB\xBF");

    // Rubrikrad
    $headers = [
        t('partner.relations.table.name'),
        'org_id',
        t('partner.relations.table.status'),
        t('partner.relations.table.added')
    ];
    fputcsv($output, $headers, ';');

    // Relationsrader
    foreach ($relations as $relation) {
        $statusLabel = $relation['is_active']
            ? t('partner.relations.status.active')
            : t('partner.relations.status.inactive');

        $row = [
            $relation['partner_name'],
            $relation['partner_org_id'],
            $statusLabel,
            date('Y-m-d', strtotime($relation['created_at']))
        ];

        fputcsv($output, $row, ';');
    }

    fclose($output);
    exit;
}
