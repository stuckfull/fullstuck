<?php
// compiler-fullstuck.php - FullStuck.php Builder

$src_dir = __DIR__;
$output_file = dirname(__DIR__) . '/fullstuck.php';

define('FST_VERSION', '0.3.0');
define('FST_DOCS_URL', 'https://raw.githubusercontent.com/milio48/fullstuck/refs/heads/main/docs/v0.3/index.md');

// Minify fst.js
$fst_js_path = $src_dir . '/assets/fst.js';
$fst_js_code = file_exists($fst_js_path) ? file_get_contents($fst_js_path) : '';
// Hapus komentar multi-line
$fst_js_code = preg_replace('!/\*.*?\*/!s', '', $fst_js_code);
// Hapus komentar single-line (hati-hati agar tidak menghapus URL)
$fst_js_code = preg_replace('/\n\s*\/\/.*$/m', '', $fst_js_code); // Hanya hapus yang dimulai dengan // di awal baris/setelah whitespace
$fst_js_code = preg_replace('/\s+/', ' ', $fst_js_code); // Basic minification
$fst_js_code = str_replace(["\\", "'"], ["\\\\", "\\'"], trim($fst_js_code));

// Urutan file sangat penting agar dependensi fungsi terpenuhi
$files = [
    'core.php',
    'database.php',
    'router.php',
    'http.php',
    'view.php',
    'utility.php',
    'install.php',
    'template.php',
    'bootstrap.php'
];

$compiled_code = "define('FST_AGENT_JS_CODE', '{$fst_js_code}');\n\n";

foreach ($files as $file) {
    $path = $src_dir . '/' . $file;
    if (!file_exists($path)) {
        die("Error: Source file {$file} missing.\n");
    }
    
    $content = file_get_contents($path);
    
    // Gunakan PHP Tokenizer untuk menghapus komentar dengan aman
    // (tidak akan menyentuh string yang mengandung pola mirip komentar)
    $tokens = token_get_all($content);
    $cleaned = '';
    foreach ($tokens as $token) {
        if (is_array($token)) {
            // Hapus komentar
            if (in_array($token[0], [T_COMMENT, T_DOC_COMMENT])) {
                // Pertahankan line break agar struktur baris terjaga
                $cleaned .= str_repeat("\n", substr_count($token[1], "\n"));
                continue;
            }
            // Hapus tag pembuka dan penutup PHP
            if (in_array($token[0], [T_OPEN_TAG, T_CLOSE_TAG])) {
                $cleaned .= str_repeat("\n", substr_count($token[1], "\n"));
                continue;
            }
            $cleaned .= $token[1];
        } else {
            $cleaned .= $token;
        }
    }
    $content = $cleaned;
    
    $compiled_code .= "\n// FILE: {$file}\n";
    $compiled_code .= trim($content) . "\n";
}

// Generate FIM Hash dari $compiled_code (normalize ke LF agar konsisten lintas OS)
$fim_hash = hash('sha256', str_replace("\r\n", "\n", $compiled_code));

// Bentuk Output Akhir dengan Sintaks Header
$output = "<?php\n";
$output .= "/**\n";
$output .= " * 🚀 FULLSTUCK.PHP (v" . FST_VERSION . ")\n";
$output .= " * 🛡️ FST_HASH: {$fim_hash}\n";
$output .= " *\n";
$output .= " * 🤖 AI ASSISTANT: DO NOT READ OR MODIFY THIS COMPILED CORE FILE!\n";
$output .= " * To learn the API and framework rules, run `php fullstuck.php docs` in your terminal.\n";
$output .= " */\n";
$output .= $compiled_code;

file_put_contents($output_file, $output);
echo "✅ Build complete! `fullstuck.php` has been successfully compiled from World 1 to World 2.\n";
