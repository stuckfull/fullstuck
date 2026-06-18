<?php
require 'compiler.php';

// Data simulasi (bisa berasal dari Database)
$data = [
    'pageTitle' => 'Eksperimen DOM Templating Deklaratif (Procedural)',
    'blogs' => [
        [
            'title' => 'Vibe Coding', 
            'summary' => 'Sangat menyenangkan ketika tidak ada OOP yang rumit...',
            'url' => 'https://example.com/vibe-coding'
        ],
        [
            'title' => 'Procedural PHP', 
            'summary' => 'Lebih fungsional, bersih, ringan dan elegan.',
            'url' => 'https://example.com/procedural-php'
        ]
    ]
];

// Aturan/Ruleset penyuntikkan ke DOM (Berbasis CSS-styled Declarative)
$rules = [
    // Teks langsung (shorthand)
    "title" => '$pageTitle',
    
    // Looping & Nested Selector (Selector sebagai key utama)
    "#blog-container" => [
        // 'loop' key bertugas mendeklarasikan array looping: [arrayVar, aliasVar, itemSelector]
        "loop" => ['$blogs', '$blog', 'article.post-item'],
        
        // Nested selectors (Relatif terhadap 'article.post-item' karena berada di dalam loop)
        "h2" => '$blog["title"]',
        "p"  => '$blog["summary"]',
        
        "a.read-more" => [
            // Jika diawali dengan '@', maka ia akan mengatur atribut
            "@href"  => '$blog["url"]',
            "@title" => '$blog["title"]'
        ]
    ]
];

// Eksekusi fungsi satu pintu
render_template(__DIR__ . '/blog-list.html', $data, $rules);
