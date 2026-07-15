<?php
// compiler-fullstuck.php - FullStuck.php Builder

$src_dir = __DIR__;
$output_file = dirname(__DIR__) . '/fullstuck.php';

define('FST_VERSION', '0.4.0');
define('FST_DOCS_URL', 'https://raw.githubusercontent.com/stuckfull/fullstuck/refs/heads/main/docs/v0.4/index.md');

// Minify fst.js
$fst_js_path = $src_dir . '/assets/fst.js';
$fst_js_code = file_exists($fst_js_path) ? file_get_contents($fst_js_path) : '';
// Remove multi-line comments
$fst_js_code = preg_replace('!/\*.*?\*/!s', '', $fst_js_code);
// Remove single-line comments (careful not to remove URLs)
$fst_js_code = preg_replace('/\n\s*\/\/.*$/m', '', $fst_js_code); // Only remove those starting with // at the beginning of the line/after whitespace
$fst_js_code = preg_replace('/\s+/', ' ', $fst_js_code); // Basic minification
$fst_js_code = str_replace(["\\", "'"], ["\\\\", "\\'"], trim($fst_js_code));

// File order is crucial to satisfy function dependencies
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
    
    // Use PHP Tokenizer to safely remove comments
    // (will not touch strings containing comment-like patterns)
    $tokens = token_get_all($content);
    $cleaned = '';
    foreach ($tokens as $token) {
        if (is_array($token)) {
            // Remove comments
            if (in_array($token[0], [T_COMMENT, T_DOC_COMMENT])) {
                // Preserve line breaks to maintain line structure
                $cleaned .= str_repeat("\n", substr_count($token[1], "\n"));
                continue;
            }
            // Remove PHP opening and closing tags
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

// Generate FIM Hash from $compiled_code (normalize to LF for cross-OS consistency)
$fim_hash = hash('sha256', str_replace("\r\n", "\n", $compiled_code));

// Form Final Output with Header Syntax
$output = "<?php\n";
$output .= "/**\n";
$output .= " * 🚀 FULLSTUCK.PHP (v" . FST_VERSION . ")\n";
$output .= " * 🛡️ FST_HASH: {$fim_hash}\n";
$output .= " * 🔗 https://github.com/stuckfull/fullstuck\n";
$output .= " *\n";
$output .= " * 🤖 AI ASSISTANT: DO NOT READ OR MODIFY THIS COMPILED CORE FILE!\n";
$output .= " * To learn the API and framework rules, run `php fullstuck.php docs` in your terminal,\n";
$output .= " * or read the `brain_fullstuck.md` file in the project root.\n";
$output .= " */\n";
$output .= $compiled_code;

file_put_contents($output_file, $output);
echo "✅ Build complete! `fullstuck.php` has been successfully compiled from World 1 to World 2.\n";
