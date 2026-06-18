<?php
require 'compiler.php';

// Simple Router
$route = $_GET['page'] ?? 'dashboard';

if ($route === 'profile') {
    // === CONTROLLER: PROFILE ===
    
    // 1. Data Simulasi (Biasanya dari Database)
    $data = [
        'pageTitle' => 'Profil: Alex Subroto',
        'user' => [
            'name' => 'Alex Subroto',
            'role' => 'Senior Developer',
            'avatar_url' => 'https://ui-avatars.com/api/?name=Alex+Subroto&background=4a90e2&color=fff&size=150',
            // Contoh data RAW HTML dari sistem WYSIWYG
            'bio_html' => '<p><strong>Halo!</strong> Saya adalah seorang pengembang <em>web</em> dari Indonesia.</p> <p>Saat ini sedang membangun sistem berbasis <span style="color:red; font-weight:bold;">PHP Prosedural Murni</span> tanpa *framework* gemuk.</p>',
            'socials' => [
                ['platform' => 'GitHub', 'url' => 'https://github.com/alex-subroto'],
                ['platform' => 'LinkedIn', 'url' => 'https://linkedin.com/in/alex-subroto'],
                ['platform' => 'Twitter / X', 'url' => 'https://x.com/alex_codes']
            ]
        ]
    ];

    // 2. Ruleset (DSL)
    $rules = [
        "title" => '$pageTitle',
        ".profile-card" => [
            ".avatar" => [
                "[src]" => '$user["avatar_url"]',
                "[alt]" => '$user["name"]'
            ],
            ".user-fullname" => '$user["name"]',
            ".user-role"     => '$user["role"]',
            
            // Nested Loop untuk Social Media
            ".social-links"  => [
                "li.social-item" => [
                    "@foreach" => '$user["socials"] as $soc',
                    "a" => [
                        "[href]" => '$soc["url"]',
                        "@text"  => '$soc["platform"]'
                    ]
                ]
            ],
            
            // RAW HTML Injection
            ".bio-content" => [
                "@html" => '$user["bio_html"]'
            ]
        ]
    ];

    // 3. Render
    render_template(__DIR__ . '/profile.html', $data, $rules);

} else {
    // === CONTROLLER: DASHBOARD ===
    
    $data = [
        'title' => 'Admin Panel Dashboard',
        'greeting' => 'Hai, Alex! Berikut adalah laporan sistem hari ini.',
        'stats' => [
            ['label' => 'Total Pengguna', 'val' => '1,250'],
            ['label' => 'Pendapatan Bulan Ini', 'val' => 'Rp 50.450.000'],
            ['label' => 'Server Load', 'val' => '14%'],
            ['label' => 'Tiket Bantuan Aktif', 'val' => '12']
        ],
        'users' => [
            ['id' => 101, 'name' => 'Budi Santoso', 'email' => 'budi@mail.com', 'status' => 'active', 'badgeClass' => 'badge active'],
            ['id' => 102, 'name' => 'Siti Aminah', 'email' => 'siti@mail.com', 'status' => 'inactive', 'badgeClass' => 'badge inactive'],
            ['id' => 103, 'name' => 'Joko Widodo', 'email' => 'joko@mail.com', 'status' => 'active', 'badgeClass' => 'badge active'],
            ['id' => 104, 'name' => 'Ahmad Dahlan', 'email' => 'ahmad@mail.com', 'status' => 'inactive', 'badgeClass' => 'badge inactive'],
            ['id' => 105, 'name' => 'Kartini', 'email' => 'kartini@mail.com', 'status' => 'active', 'badgeClass' => 'badge active']
        ]
    ];

    $rules = [
        "title" => '$title',
        "#page-title" => '$title',
        ".greeting" => '$greeting',
        
        // Loop Widget Statistik
        ".stat-grid" => [
            ".stat-card" => [
                "@foreach" => '$stats as $st',
                ".stat-title" => '$st["label"]',
                ".stat-value" => '$st["val"]'
            ]
        ],
        
        // Loop Tabel Pengguna
        "tbody" => [
            "tr.user-row" => [
                "@foreach" => '$users as $u',
                ".user-id" => '"#" . $u["id"]',
                ".user-name" => '$u["name"]',
                ".user-email" => '$u["email"]',
                
                // Menimpa Atribut dan Teks sekaligus
                ".status-badge" => [
                    "[class]" => '$u["badgeClass"]',
                    "@text"   => 'ucfirst($u["status"])'
                ],
                
                // Menimpa Atribut Majemuk
                "a.btn-edit" => [
                    "[data-id]" => '$u["id"]',
                    "[href]"    => '"?page=profile&id=" . $u["id"]'
                ]
            ]
        ]
    ];

    render_template(__DIR__ . '/dashboard.html', $data, $rules);
}
