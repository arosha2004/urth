<?php
/**
 * URTH Admin Panel Database Helper
 * Handles SQLite PDO database connection and automatic table/seed initialization.
 */

$dataDir = __DIR__ . '/data';
if (!file_exists($dataDir)) {
    mkdir($dataDir, 0777, true);
}

$dbPath = $dataDir . '/urth_admin.sqlite';

try {
    $pdo = new PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Create Admin Users table
    $pdo->exec("CREATE TABLE IF NOT EXISTS admin_users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT UNIQUE NOT NULL,
        password TEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // Create Projects table
    $pdo->exec("CREATE TABLE IF NOT EXISTS projects (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        title TEXT NOT NULL,
        category TEXT NOT NULL,
        link_url TEXT NOT NULL,
        image_url TEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // Ensure default admin user exists
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM admin_users WHERE username = :username");
    $stmt->execute(['username' => 'urthadmin']);
    if ($stmt->fetchColumn() == 0) {
        $insertAdmin = $pdo->prepare("INSERT INTO admin_users (username, password) VALUES (:username, :password)");
        $insertAdmin->execute([
            'username' => 'urthadmin',
            'password' => password_hash('12345678', PASSWORD_DEFAULT)
        ]);
    }

    // Seed initial sample projects if empty
    $countProjects = $pdo->query("SELECT COUNT(*) FROM projects")->fetchColumn();
    if ($countProjects == 0) {
        $sampleProjects = [
            [
                'title' => 'Ideas Hub Showcase',
                'category' => 'Ideas Hub',
                'link_url' => 'project_detail.php?id=7',
                'image_url' => 'https://images.unsplash.com/photo-1600585154340-be6161a56a0c?auto=format&fit=crop&w=600&q=80'
            ],
            [
                'title' => 'Spatial Redesign Showcase',
                'category' => 'Spatial Redesign',
                'link_url' => 'project_detail.php?id=6',
                'image_url' => 'https://images.unsplash.com/photo-1600596542815-ffad4c1539a9?auto=format&fit=crop&w=600&q=80'
            ],
            [
                'title' => 'Custom Furnitures Showcase',
                'category' => 'Custom Furnitures',
                'link_url' => 'project_detail.php?id=5',
                'image_url' => 'https://images.unsplash.com/photo-1618221195710-dd6b41faaea6?auto=format&fit=crop&w=600&q=80'
            ],
            [
                'title' => 'Cultural Complex Centre',
                'category' => 'Landscape Design',
                'link_url' => 'cultural-complex-centre.html',
                'image_url' => 'https://images.unsplash.com/photo-1512917774080-9991f1c4c750?auto=format&fit=crop&w=600&q=80'
            ],
            [
                'title' => 'Amman Rotana Hotel',
                'category' => 'Architectural Design',
                'link_url' => 'amman-rotana-hotel.html',
                'image_url' => 'https://images.unsplash.com/photo-1542314831-068cd1dbfeeb?auto=format&fit=crop&w=600&q=80'
            ],
            [
                'title' => 'Poolscape Villa',
                'category' => 'Interior Architecture',
                'link_url' => 'poolscape-villa.html',
                'image_url' => 'https://images.unsplash.com/photo-1580587771525-78b9dba3b914?auto=format&fit=crop&w=600&q=80'
            ],
            [
                'title' => 'The Monolith House',
                'category' => 'Architectural Design',
                'link_url' => 'the-monolith-house.html',
                'image_url' => 'https://images.unsplash.com/photo-1600607687939-ce8a6c25118c?auto=format&fit=crop&w=600&q=80'
            ]
        ];

        $insertStmt = $pdo->prepare("INSERT INTO projects (title, category, link_url, image_url) VALUES (:title, :category, :link_url, :image_url)");
        foreach ($sampleProjects as $proj) {
            $insertStmt->execute($proj);
        }
    }

} catch (PDOException $e) {
    die("Database Connection Error: " . htmlspecialchars($e->getMessage()));
}
