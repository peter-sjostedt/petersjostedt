<?php
/**
 * Standard Event Types för RFID-systemet
 *
 * Dessa händelsetyper används i events.event_type kolumnen.
 * Metadata-strukturen beskriver vilken JSON-data som förväntas.
 */

return [
    // === RFID Lifecycle ===
    'rfid_registered' => [
        'label_sv' => 'RFID registrerad',
        'label_en' => 'RFID registered',
        'description' => 'En ny RFID-tagg registreras i systemet',
        'metadata' => [
            'rfid' => 'string (EPC)',
            'organization_id' => 'string',
            'created_by' => ['user_id' => 'int', 'unit_id' => 'int|null']
        ]
    ],

    'rfid_sku_assigned' => [
        'label_sv' => 'RFID kopplad till artikel',
        'label_en' => 'RFID assigned to article',
        'description' => 'RFID-tagg kopplas till en artikel (SKU)',
        'metadata' => [
            'rfid' => 'string (EPC)',
            'organization_id' => 'string',
            'sku' => 'string',
            'article_id' => 'int',
            'created_by' => ['user_id' => 'int', 'unit_id' => 'int|null']
        ]
    ],

    'rfid_sku_changed' => [
        'label_sv' => 'RFID ändrad artikel',
        'label_en' => 'RFID article changed',
        'description' => 'RFID-tagg byter artikel (t.ex. vid korrigering)',
        'metadata' => [
            'rfid' => 'string (EPC)',
            'organization_id' => 'string',
            'old_sku' => 'string',
            'new_sku' => 'string',
            'old_article_id' => 'int',
            'new_article_id' => 'int',
            'reason' => 'string|null',
            'created_by' => ['user_id' => 'int', 'unit_id' => 'int|null']
        ]
    ],

    'rfid_deactivated' => [
        'label_sv' => 'RFID inaktiverad',
        'label_en' => 'RFID deactivated',
        'description' => 'RFID-tagg tas ur bruk (förlorad, skrotad)',
        'metadata' => [
            'rfid' => 'string (EPC)',
            'organization_id' => 'string',
            'reason' => 'string (lost|scrapped|damaged)',
            'created_by' => ['user_id' => 'int', 'unit_id' => 'int|null']
        ]
    ],

    // === Ownership & Location ===
    'rfid_ownership_transferred' => [
        'label_sv' => 'Ägarbyte',
        'label_en' => 'Ownership transferred',
        'description' => 'RFID-tagg byter ägare mellan organisationer',
        'metadata' => [
            'rfid' => 'string (EPC)',
            'from_organization_id' => 'string',
            'to_organization_id' => 'string',
            'created_by' => ['user_id' => 'int', 'unit_id' => 'int|null']
        ]
    ],

    'rfid_location_changed' => [
        'label_sv' => 'Plats ändrad',
        'label_en' => 'Location changed',
        'description' => 'RFID-tagg registreras på ny plats/enhet',
        'metadata' => [
            'rfid' => 'string (EPC)',
            'organization_id' => 'string',
            'from_unit_id' => 'int|null',
            'to_unit_id' => 'int',
            'created_by' => ['user_id' => 'int', 'unit_id' => 'int|null']
        ]
    ],

    // === Shipment Events ===
    'shipment_created' => [
        'label_sv' => 'Försändelse skapad',
        'label_en' => 'Shipment created',
        'description' => 'Ny försändelse registreras',
        'metadata' => [
            'shipment_id' => 'string',
            'from_organization_id' => 'string',
            'to_organization_id' => 'string',
            'rfid_count' => 'int',
            'created_by' => ['user_id' => 'int', 'unit_id' => 'int|null']
        ]
    ],

    'shipment_sent' => [
        'label_sv' => 'Försändelse skickad',
        'label_en' => 'Shipment sent',
        'description' => 'Försändelse lämnar avsändaren',
        'metadata' => [
            'shipment_id' => 'string',
            'from_organization_id' => 'string',
            'to_organization_id' => 'string',
            'rfid_count' => 'int',
            'created_by' => ['user_id' => 'int', 'unit_id' => 'int|null']
        ]
    ],

    'shipment_received' => [
        'label_sv' => 'Försändelse mottagen',
        'label_en' => 'Shipment received',
        'description' => 'Försändelse anländer till mottagaren',
        'metadata' => [
            'shipment_id' => 'string',
            'from_organization_id' => 'string',
            'to_organization_id' => 'string',
            'rfid_count' => 'int',
            'expected_count' => 'int|null',
            'created_by' => ['user_id' => 'int', 'unit_id' => 'int|null']
        ]
    ],

    // === Scan Events ===
    'scan_performed' => [
        'label_sv' => 'Skanning utförd',
        'label_en' => 'Scan performed',
        'description' => 'En eller flera RFID-taggar skannas',
        'metadata' => [
            'organization_id' => 'string',
            'unit_id' => 'int',
            'scan_type' => 'string (inventory|receiving|shipping|spot_check)',
            'rfid_count' => 'int',
            'created_by' => ['user_id' => 'int', 'unit_id' => 'int|null']
        ]
    ],

    'inventory_completed' => [
        'label_sv' => 'Inventering slutförd',
        'label_en' => 'Inventory completed',
        'description' => 'Fullständig inventering av en enhet',
        'metadata' => [
            'organization_id' => 'string',
            'unit_id' => 'int',
            'rfid_count' => 'int',
            'expected_count' => 'int|null',
            'discrepancy' => 'int|null',
            'created_by' => ['user_id' => 'int', 'unit_id' => 'int|null']
        ]
    ],

    // === Article Events ===
    'article_created' => [
        'label_sv' => 'Artikel skapad',
        'label_en' => 'Article created',
        'description' => 'Ny artikel registreras',
        'metadata' => [
            'organization_id' => 'string',
            'article_id' => 'int',
            'sku' => 'string',
            'name' => 'string',
            'created_by' => ['user_id' => 'int', 'unit_id' => 'int|null']
        ]
    ],

    'article_mapping_created' => [
        'label_sv' => 'Artikelmappning skapad',
        'label_en' => 'Article mapping created',
        'description' => 'Koppling mellan artiklar från olika organisationer',
        'metadata' => [
            'owner_org_id' => 'string',
            'sender_org_id' => 'string',
            'sender_article_id' => 'int',
            'sender_sku' => 'string',
            'my_article_id' => 'int',
            'my_sku' => 'string',
            'created_by' => ['user_id' => 'int', 'unit_id' => 'int|null']
        ]
    ],
];
