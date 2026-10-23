<?php

use App\Mock\MockData as M;

// Fictional businesses. Phones use the 555-01xx range, domains use the reserved .example TLD.
$rows = [
    // id, name, slug, industry, street, city, created(days ago), contacts [name, designation]
    ['C-001', 'Brightside Dental Group', 'brightsidedental', 'Dental practice', '210 Maple Avenue', 'Springfield', 60, [['Dr. Helen Brooks', 'Practice Owner'], ['Mark Ellis', 'Office Manager']]],
    ['C-002', 'Harbor & Pine Outfitters', 'harborpine', 'Outdoor retail', '14 Quay Street', 'Portside', 45, [['Gareth Lowe', 'Owner'], ['Imani Cole', 'E-commerce Lead']]],
    ['C-003', 'Northwind Plumbing & Heating', 'northwindplumbing', 'Home services', '88 Foundry Road', 'Northbridge', 30, [['Tom Alder', 'Managing Director']]],
    ['C-004', 'Lumen Yoga Studio', 'lumenyoga', 'Fitness & wellness', '5 Lantern Court', 'Riverside', 28, [['Aisha Verma', 'Founder'], ['Beth Calloway', 'Studio Manager']]],
    ['C-005', 'Cedar Ridge Realty', 'cedarridgerealty', 'Real estate', '402 Ridge Boulevard', 'Cedar Falls', 27, [['Paula Dunn', 'Broker in Charge'], ['Victor Haas', 'Marketing Coordinator'], ['Neil Ortiz', 'Sales Associate']]],
    ['C-006', 'Pixel Forge Print Co.', 'pixelforgeprint', 'Printing & signage', '19 Press Lane', 'Eastfield', 24, [['Ruth Mendel', 'Owner']]],
    ['C-007', 'Greenleaf Organic Market', 'greenleafmarket', 'Grocery', '71 Orchard Way', 'Greenhill', 21, [['Samir Joshi', 'General Manager']]],
    ['C-008', 'Summit Auto Care', 'summitautocare', 'Automotive', '260 Highway 9', 'Summitville', 18, [['Colin Reeve', 'Owner'], ['Dana Whitlock', 'Service Manager']]],
    ['C-009', 'Bluebird Accounting Partners', 'bluebirdaccounting', 'Accounting', '33 Finch Street', 'Lakeside', 16, [['Elena Marsh', 'Managing Partner']]],
    ['C-010', 'Maple & Co Bakery', 'maplecobakery', 'Food & beverage', '9 Baker\'s Row', 'Springfield', 2, [['Jon Tate', 'Owner']]],
    ['C-011', 'Ironclad Fitness', 'ironcladfitness', 'Fitness & wellness', '150 Industrial Park', 'Eastfield', 1, [['Marta Kowal', 'Gym Manager']]],
    ['C-012', 'Riverbend Veterinary Clinic', 'riverbendvet', 'Veterinary', '62 Riverbend Drive', 'Riverside', 1, [['Dr. Owen Pryce', 'Lead Veterinarian'], ['Hannah Boyd', 'Practice Administrator']]],
    ['C-013', 'Atlas Logistics Ltd', 'atlaslogistics', 'Logistics', '1 Dockside Estate', 'Portside', 12, [['Piotr Nowak', 'Operations Director']]],
    ['C-014', 'Sunrise Childcare Centre', 'sunrisechildcare', 'Childcare', '27 Daisy Close', 'Greenhill', 0, [['Gloria Sims', 'Centre Director']]],
    ['C-015', 'Willow Creek Pediatrics', 'willowcreekpeds', 'Healthcare', '5 Willow Creek Road', 'Cedar Falls', 20, [['Dr. Anita Rao', 'Partner']]],
    ['C-016', 'Copperline Roofing', 'copperlineroofing', 'Home services', '300 Slate Street', 'Northbridge', 15, [['Brett Collins', 'Owner'], ['Yasmin Fahey', 'Office Administrator']]],
    ['C-017', 'Orchard Lane Florist', 'orchardlaneflorist', 'Retail', '8 Orchard Lane', 'Springfield', 34, [['Mei Tanaka', 'Owner']]],
    ['C-018', 'Delta Freight Brokers', 'deltafreight', 'Logistics', '90 Terminal Road', 'Portside', 26, [['Luis Ferreira', 'Sales Director']]],
    ['C-019', 'Tidewater Marine Supply', 'tidewatermarine', 'Marine retail', '2 Harbour Pier', 'Portside', 3, [['Cora Lindqvist', 'Owner']]],
    ['C-020', 'Fernhill Garden Centre', 'fernhillgarden', 'Garden centre', '77 Fernhill Lane', 'Greenhill', 120, [['Walter Abbott', 'Owner'], ['Lucy Park', 'Accounts']]],
    ['C-021', 'Polar Peak Cycles', 'polarpeakcycles', 'Bicycle retail', '41 Alpine Way', 'Summitville', 52, [['Greta Holm', 'Founder'], ['Kofi Mensah', 'Store Manager']]],
    ['C-022', 'Quill & Ink Legal', 'quillinklegal', 'Legal services', '12 Chancery Place', 'Lakeside', 1, [['Adrian Voss', 'Principal Solicitor']]],
    ['C-024', 'Ashford Optometry', 'ashfordoptometry', 'Healthcare', '3 Clearview Road', 'Cedar Falls', 0, [['Dr. Nadia Karim', 'Optometrist & Owner']]],
    ['C-023', 'Mosaic Dental Studio', 'mosaicdentalstudio', 'Dental practice', '18 Tile Street', 'Eastfield', 38, [['Dr. Priyanka Nair', 'Owner'], ['Sean Doyle', 'Practice Manager']]],
];

return array_map(function ($r, $i) {
    [$id, $name, $slug, $industry, $street, $city, $age, $contacts] = $r;
    $n = $i + 10;
    $cs = [];
    foreach ($contacts as $k => [$cname, $role]) {
        $first = strtolower(preg_replace('/[^a-z]/i', '', explode(' ', str_replace(['Dr. '], '', $cname))[0]));
        $cs[] = [
            'id' => $id.'-K'.($k + 1),
            'name' => $cname,
            'designation' => $role,
            'email' => "$first@$slug.example",
            'phone' => '+1 555 01'.str_pad((string) (($n * 3 + $k * 7) % 100), 2, '0', STR_PAD_LEFT),
            'primary' => $k === 0,
            'notes' => $k === 0 ? 'Decision maker' : null,
        ];
    }

    return [
        'id' => $id,
        'business_name' => $name,
        'business_email' => "info@$slug.example",
        'business_phone' => '+1 555 01'.str_pad((string) (($n * 3 + 50) % 100), 2, '0', STR_PAD_LEFT),
        'business_address' => "$street, $city",
        'website' => "https://www.$slug.example",
        'domain' => "$slug.example",
        'industry' => $industry,
        'city' => $city,
        'contacts' => $cs,
        'created_at' => M::dt(-$age, '10:00'),
        'status' => 'active',
    ];
}, $rows, array_keys($rows));
