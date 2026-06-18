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

// Aturan/Ruleset penyuntikkan ke DOM (Berbasis Deklaratif Array murni)
$rules = [
    'texts' => [
        "//title" => '$pageTitle'
    ],
    'loops' => [
        "//div[@id='blog-container']" => [
            'item'  => ".//article[contains(@class, 'post-item')]",
            'array' => '$blogs',
            'alias' => '$blog',
            
            // Nested Rules: Diaplikasikan untuk setiap item loop secara spesifik
            'texts' => [
                ".//h2" => '$blog["title"]',
                ".//p"  => '$blog["summary"]'
            ],
            
            // Opsional: Demonstrasi fitur merubah atribut
            /* 'attributes' => [
                ".//a" => ["href" => '$blog["url"]']
            ] */
        ]
    ]
];

// Eksekusi fungsi satu pintu
render_template(__DIR__ . '/blog-list.html', $data, $rules);
