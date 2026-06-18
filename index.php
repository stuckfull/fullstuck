<?php
require 'compiler.php';

// Simple Router (Gabungan Semua Demo)
$route = $_GET['page'] ?? 'dashboard';

// Menu navigasi atas untuk kemudahan demo
$navMenuHtml = <<<HTML
<div style="background:#222; color:#fff; padding:15px; text-align:center; font-family:sans-serif; margin-bottom:0;">
    <strong style="margin-right:20px;">Demo Navigasi:</strong> 
    <a href="?page=dashboard" style="color:#4a90e2; margin:0 10px; text-decoration:none; font-weight:bold;">1. Dashboard</a> | 
    <a href="?page=profile" style="color:#4a90e2; margin:0 10px; text-decoration:none; font-weight:bold;">2. Profile</a> | 
    <a href="?page=blog" style="color:#4a90e2; margin:0 10px; text-decoration:none; font-weight:bold;">3. Blog List</a>
</div>
HTML;

echo $navMenuHtml;

if ($route === 'profile') {
    // ==========================================
    // CONTROLLER: PROFILE (Demo @html & Atribut)
    // ==========================================
    
    $data = [
        'pageTitle' => 'Profil: Alex Subroto',
        'user' => [
            'name' => 'Alex Subroto',
            'role' => 'Senior Developer',
            'avatar_url' => 'https://ui-avatars.com/api/?name=Alex+Subroto&background=4a90e2&color=fff&size=150',
            // Contoh data RAW HTML dari sistem WYSIWYG
            'bio_html' => '<p><strong>Halo!</strong> Saya adalah seorang pengembang <em>web</em> dari Indonesia.</p> <p>Saat ini sedang bereksperimen membangun sistem berbasis <span style="color:red; font-weight:bold;">PHP Prosedural Murni</span>.</p>',
            'socials' => [
                ['platform' => 'GitHub', 'url' => 'https://github.com/alex-subroto'],
                ['platform' => 'LinkedIn', 'url' => 'https://linkedin.com/in/alex-subroto'],
                ['platform' => 'Twitter / X', 'url' => 'https://x.com/alex_codes']
            ]
        ]
    ];

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

    render_template(__DIR__ . '/profile.html', $data, $rules);

} elseif ($route === 'blog') {
    // ==========================================
    // CONTROLLER: BLOG (Demo Semua Fitur DSL)
    // ==========================================
    
    $data = [
        'pageTitle' => 'Eksperimen Fitur Komplit',
        'subJudul' => 'Daftar Artikel (Dirender dengan @text)',
        'htmlContent' => '<i>Ini di-render secara <b>Raw HTML</b> tanpa lolos XSS escape.</i>',
        'linkUrl' => 'https://github.com/alex-subroto',
        'realImageUrl' => 'https://ui-avatars.com/api/?name=Thumbnail&background=random',
        
        // Logika Kondisional
        'isPromoActive' => true,
        'isLoggedIn' => true, // Coba ubah menjadi false untuk melihat perubahan elemen auth
        
        'menus' => [
            ['url' => '?page=dashboard', 'label' => 'Beranda'],
            ['url' => '?page=profile', 'label' => 'Profil Pengguna'],
            ['url' => '?page=blog', 'label' => 'Daftar Artikel']
        ]
    ];

    $rules = [
        // 1. MANIPULASI TEKS & HTML
        "title" => '$pageTitle', 
        "h3" => ["@text" => '$subJudul'],
        "span.content" => ["@html" => '$htmlContent'],

        // 2. MANIPULASI ATRIBUT
        "a.external" => ["[href]" => '$linkUrl', "[target]" => '"_blank"'],
        "a[data-type='link']" => 'Teks Link Baru',

        // 3. TARGETING TUNGGAL (^ prefix)
        "^div.alert" => '"Ini alert pertama saja yang terganti (" . date("H:i:s") . ")"',

        // 4. COMPILE-TIME CLEANUP
        "div.debug-panel" => "@remove",
        "img.thumbnail" => [
            "[style]" => "@remove",       
            "[data-dummy]" => "@remove",  
            "[src]" => '$realImageUrl'    
        ],

        // 5. RUN-TIME LOGIC (@if, Ternary, @foreach)
        "div.banner-promo" => [
            "@if" => '$isPromoActive'
        ],
        "a.btn-dashboard" => [
            "@if" => '$isLoggedIn'
        ],
        "a.btn-login" => [
            "@if" => '!$isLoggedIn'
        ],
        "button.btn-auth" => [
            "@text"   => '$isLoggedIn ? "Logout" : "Login"',
            "[href]"  => '$isLoggedIn ? "?logout=1" : "?page=login"',
            "[class]" => '$isLoggedIn ? "btn-danger" : "btn-primary"'
        ],
        "ul.nav > li" => [
            "@foreach" => '$menus as $menu',
            "a" => [
                "[href]" => '$menu["url"]',
                "@text"  => '$menu["label"]'
            ]
        ]
    ];

    render_template(__DIR__ . '/blog-list.html', $data, $rules, __DIR__ . '/build-template');

} else {
    // ==========================================
    // CONTROLLER: DASHBOARD (Demo Tabel & Badge)
    // ==========================================
    
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
