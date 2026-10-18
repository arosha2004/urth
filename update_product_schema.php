<?php

require_once __DIR__ . '/config.php';

echo "Running product schema migration...\n";

// Array of new columns to ensure exist
$columnsToAdd = [
    'product_type' => "VARCHAR(50) DEFAULT 'furniture_object'",
    'what_included' => "TEXT DEFAULT NULL",
    'options_label' => "VARCHAR(100) DEFAULT 'Option'",
    'options_list' => "TEXT DEFAULT NULL",
    'delivery_time' => "VARCHAR(255) DEFAULT NULL",
    'delivery_info' => "TEXT DEFAULT NULL",
    'cancellation_refund' => "TEXT DEFAULT NULL",
    'gallery_images' => "TEXT DEFAULT NULL",
    'file_formats' => "VARCHAR(255) DEFAULT NULL",
    'software_compatibility' => "VARCHAR(255) DEFAULT NULL",
    'permitted_use' => "TEXT DEFAULT NULL",
    'preview_images' => "TEXT DEFAULT NULL",
    'book_format' => "VARCHAR(255) DEFAULT NULL",
    'sample_pages' => "TEXT DEFAULT NULL",
    'book_dimensions' => "VARCHAR(255) DEFAULT NULL",
    'dimensions' => "VARCHAR(255) DEFAULT NULL",
    'materials' => "VARCHAR(255) DEFAULT NULL",
    'finishes' => "VARCHAR(255) DEFAULT NULL",
    'customisation' => "TEXT DEFAULT NULL",
    'production_time' => "VARCHAR(255) DEFAULT NULL",
    'delivery_area' => "VARCHAR(255) DEFAULT NULL",
    'course_topics' => "TEXT DEFAULT NULL",
    'course_format' => "VARCHAR(255) DEFAULT NULL",
    'course_duration' => "VARCHAR(255) DEFAULT NULL",
    'access_period' => "VARCHAR(255) DEFAULT NULL",
    'live_dates' => "VARCHAR(255) DEFAULT NULL",
    'session_scope' => "TEXT DEFAULT NULL",
    'session_duration' => "VARCHAR(255) DEFAULT NULL",
    'customer_info_needed' => "TEXT DEFAULT NULL",
    'deliverables' => "TEXT DEFAULT NULL",
    'included_revisions' => "VARCHAR(255) DEFAULT NULL",
    'type_details' => "LONGTEXT DEFAULT NULL"
];

// Fetch existing columns in products table
$existingCols = [];
$res = $conn->query("SHOW COLUMNS FROM products");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $existingCols[] = $row['Field'];
    }
}

foreach ($columnsToAdd as $col => $definition) {
    if (!in_array($col, $existingCols, true)) {
        $alterSql = "ALTER TABLE products ADD COLUMN {$col} {$definition}";
        if ($conn->query($alterSql) === TRUE) {
            echo "Added column: {$col}\n";
        } else {
            echo "Error adding {$col}: " . $conn->error . "\n";
        }
    } else {
        echo "Column {$col} already exists.\n";
    }
}

// Now seed sample rich metadata for existing products so they look extraordinary immediately
$updates = [
    // Bertt Side table (Furniture & Object)
    1 => [
        'product_type' => 'furniture_object',
        'options_label' => 'Finish & Timber',
        'options_list' => "Natural White Oak, Smoked Oiled Walnut, Ebonized Black Ash",
        'what_included' => "• 1x Bertt Solid Oak Side Table\n• Solid brass protective accent foot levellers\n• URTH Studio certificate of authenticity and numbered brass badge\n• Natural beeswax care treatment kit & microfiber buffing cloth",
        'delivery_time' => "Estimated dispatch: 3–5 working days (Stocked) / Made to order: 3–4 weeks",
        'delivery_info' => "White-glove UK courier delivery included. Packaged in reinforced, recyclable wooden crate with corner dampening foam. Worldwide freight delivery arranged with trackable sea or air dispatch.",
        'cancellation_refund' => "Free cancellation within 24 hours of placing order. 14-day hassle-free return policy for standard finishes in original packaging. Bespoke dimensions subject to 20% restocking fee.",
        'dimensions' => "Diameter: 420 mm | Height: 520 mm | Weight: 9.8 kg",
        'materials' => "Solid European White Oak, Brushed Spun Brass, Eco-friendly Bio-Wax",
        'finishes' => "Natural Matte Oak, Brushed Brass, Ebonized Ash",
        'customisation' => "Custom heights (up to 700mm) and timber species (American Walnut, English Ash) available on studio request.",
        'production_time' => "In stock: 3–5 days. Bespoke orders: 3–4 weeks handcrafted in our workshop.",
        'delivery_area' => "UK Nationwide & International crate shipping to 48 countries.",
        'gallery_images' => json_encode(['img/shop/prod-1.jpg', 'img/shop/prod-3.jpg', 'img/shop/prod-5.jpg'])
    ],
    // Coffee Table (Furniture & Object)
    2 => [
        'product_type' => 'furniture_object',
        'options_label' => 'Wood Finish',
        'options_list' => "Smoked Walnut, Raw Oak, Bleached Maple",
        'what_included' => "• 1x Sculptural Coffee Table with integrated book pocket\n• Felt floor protectors and leveling shims\n• Studio authentication plaque\n• 5-year structural warranty documentation",
        'delivery_time' => "Dispatched within 5–7 business days",
        'delivery_info' => "Hand-delivered by specialized furniture logistics partners. Assembly not required — arrives fully assembled and leveled.",
        'cancellation_refund' => "Cancellations permitted before order dispatch. 30-day return policy for standard finishes. Customer covers return shipping in original crating.",
        'dimensions' => "Length: 1100 mm | Width: 600 mm | Height: 380 mm | Weight: 18.5 kg",
        'materials' => "Molded Curved American Walnut Veneer with solid timber internal core",
        'finishes' => "Matte protective hardwax oil finish, water and stain repellent",
        'customisation' => "Custom lengths up to 1500mm available with customized storage apertures.",
        'production_time' => "Hand-finished batch production: 1–2 weeks.",
        'delivery_area' => "UK Nationwide & Mainland Europe delivery.",
        'gallery_images' => json_encode(['img/shop/prod-2.jpg', 'img/shop/prod-9.jpg', 'img/shop/prod-8.jpg'])
    ],
    // Modern End Table 2 (Furniture & Object)
    3 => [
        'product_type' => 'furniture_object',
        'options_label' => 'Base & Shade Finish',
        'options_list' => "Turned Birch & Warm Grey, Smoked Oak & Linen White, Black Ash & Charcoal",
        'what_included' => "• 1x Modern End Accent Table with integrated ambient lighting fixture\n• Dimmable braided fabric cable (2.5m) with inline rotary brass switch\n• Warm 2700K E27 architectural LED bulb included\n• Setup and care manual",
        'delivery_time' => "Preparation: 2 business days. UK delivery: 48 hours.",
        'delivery_info' => "Shipped in double-walled shock-absorbent corrugated packaging via DPD Tracked.",
        'cancellation_refund' => "Full refund within 14 days of delivery if in pristine condition with original box.",
        'dimensions' => "Base Diameter: 350 mm | Total Height: 680 mm | Tabletop: 320 mm",
        'materials' => "Turned Natural Ash, Spun Powder-Coated Aluminum, Belgian Natural Linen",
        'finishes' => "Satin Lacquer, Brushed Brass Spindle, Textured Grey Linen",
        'customisation' => "Choice of cable colors (Brass woven, Black twisted cord, Neutral flax).",
        'production_time' => "In stock & ready to dispatch within 48 hours.",
        'delivery_area' => "UK Nationwide & Worldwide air shipping.",
        'gallery_images' => json_encode(['img/shop/prod-3.jpg', 'img/shop/prod-1.jpg', 'img/shop/prod-5.jpg'])
    ],
    // Modern End Table 1 / Molded Chair (Furniture & Object)
    4 => [
        'product_type' => 'furniture_object',
        'options_label' => 'Shell & Leg Option',
        'options_list' => "Matte Black & Solid Beech, Chalk White & Walnut Legs, Olive Green & Oak Legs",
        'what_included' => "• 1x Molded Ergonomic Armchair\n• Solid turned timber dowel legs with steel Eiffel brace\n• Non-scratch nylon glides pre-installed\n• Hardware assembly kit with hex tool",
        'delivery_time' => "Ready for dispatch: 2–4 business days.",
        'delivery_info' => "Flat-packed in high-density protective recyclable carton with illustrated instructions.",
        'cancellation_refund' => "14-day return window. Contact studio support for prepaid return shipping label.",
        'dimensions' => "Width: 620 mm | Depth: 600 mm | Height: 810 mm | Seat Height: 450 mm",
        'materials' => "Recyclable Polypropylene Shell, Solid European Beech Wood, Black Powder-Coated Steel Wire",
        'finishes' => "Matte textured shell, satin clear timber lacquer",
        'customisation' => "Optional removable bouclé seat pad in 6 designer fabrics.",
        'production_time' => "Immediate dispatch from London studio stock.",
        'delivery_area' => "UK Nationwide, EU and North America.",
        'gallery_images' => json_encode(['img/shop/prod-4.jpg', 'img/shop/prod-7.jpg', 'img/shop/prod-6.jpg'])
    ]
];

foreach ($updates as $pid => $data) {
    $setParts = [];
    $types = '';
    $vals = [];
    foreach ($data as $col => $val) {
        $setParts[] = "{$col} = ?";
        $types .= 's';
        $vals[] = $val;
    }
    $setSql = implode(', ', $setParts);
    $stmt = $conn->prepare("UPDATE products SET {$setSql} WHERE id = ?");
    if ($stmt) {
        $types .= 'i';
        $vals[] = $pid;
        $stmt->bind_param($types, ...$vals);
        $stmt->execute();
        $stmt->close();
        echo "Updated product ID {$pid} with rich metadata.\n";
    }
}

// Ensure products 5-9 also have rich furniture metadata
$conn->query("UPDATE products SET 
    product_type = 'furniture_object',
    options_label = 'Finish',
    options_list = 'Natural Solid Oak, Smoked Oiled Walnut, Ebonized Ash',
    what_included = '• 1x Handcrafted architectural piece\n• Floor protection pads\n• URTH Studio certificate of authenticity\n• Care and maintenance handbook',
    delivery_time = 'Dispatched within 3–5 business days',
    delivery_info = 'White-glove UK courier delivery included. Packaged in recyclable reinforced crate.',
    cancellation_refund = '14-day hassle-free returns on standard models in original packaging.',
    dimensions = 'Custom architectural dimensions (contact studio for dimensional CAD drawing)',
    materials = 'Solid European White Oak, Natural Beeswax Oil, Brushed Metal Fixtures',
    finishes = 'Natural Matte Oak, Smoked Walnut, Satin Black Lacquer',
    customisation = 'Bespoke dimensions and custom timber finishes available upon request.',
    production_time = 'Stocked items: 3–5 days. Bespoke crafted: 3–4 weeks.',
    delivery_area = 'UK Nationwide & Worldwide freight crate shipping.',
    gallery_images = JSON_ARRAY(image)
WHERE dimensions IS NULL OR dimensions = ''");

// Add a Digital Asset, a Book & Planner, a Workshop, and a Design Session if needed so all 5 types are exemplified
$checkDigital = $conn->query("SELECT id FROM products WHERE product_type = 'digital_asset' LIMIT 1");
if (!$checkDigital || $checkDigital->num_rows === 0) {
    echo "Adding sample Digital Asset product...\n";
    $dStmt = $conn->prepare("INSERT INTO products (name, slug, sku, price, sale_price, currency, category, description, image, product_label, availability, product_type, options_label, options_list, what_included, delivery_time, delivery_info, cancellation_refund, file_formats, software_compatibility, permitted_use, preview_images, gallery_images) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    if ($dStmt) {
        $name = 'Architectural Procreate & CAD Asset Bundle';
        $slug = 'architectural-procreate-cad-asset-bundle';
        $sku = 'DG-PRO-01';
        $price = 39.00;
        $sale_price = 29.00;
        $curr = '£';
        $cat = 'Digital Tools';
        $desc = 'A complete architectural visualization toolkit crafted by URTH Studio architects. Features over 180 vector CAD blocks, seamless PBR material textures, and pressure-sensitive Procreate sketch brushes designed specifically for architectural ideation, elevation details, and presentation drawings.';
        $img = 'img/shop/prod-1.jpg';
        $label = 'Digital';
        $avail = 'available';
        $type = 'digital_asset';
        $optLabel = 'License Type';
        $optList = 'Personal License (Single Seat), Studio Commercial License (Up to 10 Seats), Extended Enterprise License';
        $whatInc = "• 180+ Handcrafted CAD blocks in .DWG and .DXF format\n• 45 Pressure-sensitive Procreate brushes (.brushset)\n• 60 High-resolution 4K seamless material textures (Albedo, Normal, Roughness)\n• 12 Pre-configured Revit detail families (.RFA)\n• Quick-start PDF installation guide with video tutorial links";
        $delTime = "Instant download immediately upon purchase";
        $delInfo = "Digital delivery. You will receive an encrypted download link via email immediately after checkout. Files can be re-downloaded at any time from your account or receipt.";
        $cancelRef = "Due to the digital nature of instant download assets, refunds are generally non-refundable once files are downloaded. If you encounter any technical issues or corrupted files, our studio team will replace them within 24 hours.";
        $formats = ".BRUSHSET, .DWG, .DXF, .RFA, .PNG (4K), .PAT, .PDF";
        $compat = "Procreate 5.2+, AutoCAD 2018+, Revit 2021+, Adobe Photoshop CC, Rhino 7+";
        $permit = "Commercial & Personal architectural project visualizations, client presentations, and competition entries. Redistribution or reselling of source assets strictly prohibited.";
        $previews = "Included in zip: 12 high-resolution elevation and render previews showing brushes applied.";
        $gal = json_encode(['img/shop/prod-1.jpg', 'img/shop/prod-2.jpg']);

        $dStmt->bind_param("sssddssssssssssssssssss", $name, $slug, $sku, $price, $sale_price, $curr, $cat, $desc, $img, $label, $avail, $type, $optLabel, $optList, $whatInc, $delTime, $delInfo, $cancelRef, $formats, $compat, $permit, $previews, $gal);
        $dStmt->execute();
        $dStmt->close();
    }
}

$checkBook = $conn->query("SELECT id FROM products WHERE product_type = 'book_planner' LIMIT 1");
if (!$checkBook || $checkBook->num_rows === 0) {
    echo "Adding sample Book & Planner product...\n";
    $bStmt = $conn->prepare("INSERT INTO products (name, slug, sku, price, sale_price, currency, category, description, image, product_label, availability, product_type, options_label, options_list, what_included, delivery_time, delivery_info, cancellation_refund, book_format, sample_pages, book_dimensions, gallery_images) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    if ($bStmt) {
        $name = 'The Modern Residence: Architectural Planning Workbook';
        $slug = 'modern-residence-architectural-planning-workbook';
        $sku = 'BK-PLN-02';
        $price = 45.00;
        $sale_price = null;
        $curr = '£';
        $cat = 'Books & Planners';
        $desc = 'An indispensable 210-page tactile workbook and planning handbook for homeowners, interior designers, and architects. Guides you through the complete residential design journey: from spatial programming and budget estimation to lighting plans, joinery specifications, and contractor tender checklists.';
        $img = 'img/shop/prod-8.jpg';
        $label = 'Printed';
        $avail = 'available';
        $type = 'book_planner';
        $optLabel = 'Format & Edition';
        $optList = 'Hardcover Foil-Stamped Edition, Digital Interactive PDF Edition, Collector Bundle (Hardcover + Digital + CAD Templates)';
        $whatInc = "• 210-Page Casebound Hardcover Workbook with gold foil debossing\n• 2x Ribbon page markers & elastic closure band\n• Expandable rear inner pocket containing 4x fold-out A3 drafting grid sheets\n• Download code for printable digital budget and schedule spreadsheets\n• URTH architectural scale ruler bookmark";
        $delTime = "Dispatched within 24 hours. UK Royal Mail Tracked 24.";
        $delInfo = "Shipped in reinforced cardboard book wrap. Tracked delivery across UK (1–2 days), Europe (3–5 days), and Rest of World (7–10 days).";
        $cancelRef = "30-day return policy for unused, shrink-wrapped physical editions. Digital bundle portion is non-refundable upon access key activation.";
        $bFormat = "Linen Hardcover Casebound & High-Res Clickable PDF with hyperlinked index";
        $sPages = "42 sample pages available for immediate preview in table of contents";
        $bDim = "240 x 170 mm (Standard Architectural Royal), 210 pages, 140gsm uncoated FSC Italian paper";
        $gal = json_encode(['img/shop/prod-8.jpg', 'img/shop/prod-2.jpg', 'img/shop/prod-3.jpg']);

        $bStmt->bind_param("sssddsssssssssssssssss", $name, $slug, $sku, $price, $sale_price, $curr, $cat, $desc, $img, $label, $avail, $type, $optLabel, $optList, $whatInc, $delTime, $delInfo, $cancelRef, $bFormat, $sPages, $bDim, $gal);
        $bStmt->execute();
        $bStmt->close();
    }
}

$checkWorkshop = $conn->query("SELECT id FROM products WHERE product_type = 'course_workshop' LIMIT 1");
if (!$checkWorkshop || $checkWorkshop->num_rows === 0) {
    echo "Adding sample Course / Workshop product...\n";
    $wStmt = $conn->prepare("INSERT INTO products (name, slug, sku, price, sale_price, currency, category, description, image, product_label, availability, product_type, options_label, options_list, what_included, delivery_time, delivery_info, cancellation_refund, course_topics, course_format, course_duration, access_period, live_dates, gallery_images) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    if ($wStmt) {
        $name = 'Mastering Minimalist Architecture & Spatial Flow';
        $slug = 'mastering-minimalist-architecture-spatial-flow';
        $sku = 'WS-MIN-03';
        $price = 149.00;
        $sale_price = 119.00;
        $curr = '£';
        $cat = 'Learn';
        $desc = 'An intensive, live masterclass series led by URTH Studio founder and lead architects. Discover how to transform everyday spaces into serene, light-filled sanctuaries using proportion, intentional sightlines, hidden storage joinery, and honest material palettes.';
        $img = 'img/shop/prod-6.jpg';
        $label = 'Online course';
        $avail = 'available';
        $type = 'course_workshop';
        $optLabel = 'Cohort / Pass Type';
        $optList = 'Standard Live Pass (Live Sessions + Recordings), VIP Pass (Live Sessions + 1-on-1 Studio Critique), Studio Team Pass (Up to 5 Attendees)';
        $whatInc = "• 4 Live interactive 90-minute studio workshops with Q&A\n• Full HD recorded replays available 2 hours post-session\n• Downloadable presentation slide decks, drawing templates, and reading lists\n• Private Discord community access for peer feedback and project critiques\n• Official URTH Studio Certificate of Completion";
        $delTime = "Next cohort begins Saturday, Nov 15, 2026";
        $delInfo = "Online live event via Zoom. Calendar invites, access links, and orientation materials dispatched via email immediately upon registration.";
        $cancelRef = "100% money-back guarantee up to 7 days before the first live session. Recorded access pass non-refundable once live cohort initiates.";
        $topics = "1. Proportions & Spatial Flow\n2. Light Choreography & Natural Shading\n3. Material Authenticity (Timber, Stone, Microcement)\n4. Hidden Joinery & Minimalist Details\n5. Live Floor Plan Critiques";
        $cFormat = "Live Zoom Webinar + 4K On-demand recorded replay portal";
        $cDuration = "4 Weeks (4x 90-min live modules + weekly studio assignments)";
        $access = "Lifetime access to session recordings and downloadable resources";
        $liveDates = "Every Saturday 14:00 – 15:30 GMT (London Time) with interactive Q&A";
        $gal = json_encode(['img/shop/prod-6.jpg', 'img/shop/prod-7.jpg']);

        // 24 params: 3 's', 2 'd', 19 's'
        $wTypes = "sssdd" . str_repeat("s", 19);
        $wStmt->bind_param($wTypes, $name, $slug, $sku, $price, $sale_price, $curr, $cat, $desc, $img, $label, $avail, $type, $optLabel, $optList, $whatInc, $delTime, $delInfo, $cancelRef, $topics, $cFormat, $cDuration, $access, $liveDates, $gal);
        $wStmt->execute();
        $wStmt->close();
    }
}

$checkSession = $conn->query("SELECT id FROM products WHERE product_type = 'design_session' LIMIT 1");
if (!$checkSession || $checkSession->num_rows === 0) {
    echo "Adding sample Design Session product...\n";
    $sStmt = $conn->prepare("INSERT INTO products (name, slug, sku, price, sale_price, currency, category, description, image, product_label, availability, product_type, options_label, options_list, what_included, delivery_time, delivery_info, cancellation_refund, session_scope, session_duration, customer_info_needed, deliverables, included_revisions, gallery_images) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    if ($sStmt) {
        $name = '1-on-1 Architectural Space Review & Concept Session';
        $slug = 'one-on-one-architectural-space-review-concept-session';
        $sku = 'DS-REV-04';
        $price = 280.00;
        $sale_price = null;
        $curr = '£';
        $cat = 'Design Sessions';
        $desc = 'Collaborate directly with a senior URTH architect to evaluate your floor plan, renovation concept, or interior living space. We analyze flow, daylighting, furniture layouts, and structural potential to unlock your home\'s maximum architectural value.';
        $img = 'img/shop/prod-7.jpg';
        $label = 'Consultation';
        $avail = 'available';
        $type = 'design_session';
        $optLabel = 'Session Focus Area';
        $optList = 'Single Room Optimization (Living / Kitchen / Bed), Full Apartment Floor Plan Review, Whole House Architectural Renovation Feasibility';
        $whatInc = "• Pre-session architectural analysis of your submitted plans and photos\n• 90-Minute live private video consultation via Google Meet\n• Live digital sketching and screen-share layout exploration\n• Comprehensive bespoke PDF Design Summary Dossier delivered within 5 days\n• Curated supplier, timber finish, and fixture specification recommendations\n• 14 days of follow-up email support for design clarifications";
        $delTime = "Booking slots available within 3–7 business days";
        $delInfo = "Conducted virtually via high-definition video call. Calendar scheduling link provided immediately upon checkout to pick your preferred date and time.";
        $cancelRef = "Rescheduling permitted at no charge up to 48 hours prior to scheduled session. Cancellations within 48 hours subject to 50% reservation fee.";
        $scope = "Full review of spatial layouts, partition removals, daylighting, joinery placement, and furniture planning.";
        $duration = "90 minutes live consultation + 3 hours of architect pre/post review work";
        $custInfo = "Existing room dimensions/floor plan (sketches accepted), 4-6 daylight photos of the space, wishlist & target budget.";
        $deliv = "Annotated 2D space plan (PDF), Material & color scheme palette, Clickable shopping list, Recorded call transcript.";
        $revs = "1 iterative layout refinement included within 14 days following receipt of the Design Summary Dossier.";
        $gal = json_encode(['img/shop/prod-7.jpg', 'img/shop/prod-3.jpg', 'img/shop/prod-4.jpg']);

        // 24 params: 3 's', 2 'd', 19 's'
        $sTypes = "sssdd" . str_repeat("s", 19);
        $sStmt->bind_param($sTypes, $name, $slug, $sku, $price, $sale_price, $curr, $cat, $desc, $img, $label, $avail, $type, $optLabel, $optList, $whatInc, $delTime, $delInfo, $cancelRef, $scope, $duration, $custInfo, $deliv, $revs, $gal);
        $sStmt->execute();
        $sStmt->close();
    }
}

echo "Schema migration and sample data seeding completed successfully!\n";
