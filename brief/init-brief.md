Act as an Expert PHP Developer who specializes in zero-dependency, ultra-minimalist architectures, and performance optimization. 

We are building a DOM-based Templating Engine with JIT (Just-In-Time) Caching. The goal is to keep Frontend HTML files 100% static and pure (no PHP tags, no custom templating syntax like {{ }}), while allowing the Backend to make them dynamic via DOM manipulation.

Constraints & Requirements:
1. STRICTLY NO Composer, NO external libraries. Vanilla PHP 8.x only.
2. The core logic must be encapsulated in a single, portable class or file.
3. Use PHP's native `DOMDocument` and `DOMXPath` for parsing.
4. Implement a caching mechanism: 
   - Compile the manipulated DOM into a `.php` file with injected native PHP tags.
   - Save it to a `build-template/` directory.
   - Serve the compiled `.php` file directly on subsequent requests.
   - Automatically rebuild the cache ONLY if the original `.html` file's modified time is newer than the cache.
5. Provide a clean, chainable, or intuitive API to map array data to CSS Selectors (e.g., text injection, attribute injection, and looping).

Below is the skeleton code. Complete the implementation of the `DomCompiler` class, specifically handling text injection and the `foreach` looping mechanism using placeholders, and ensure the caching logic is flawless.

```

---

### 2. Skeleton Code (Simpan sebagai `compiler.php`)

```php
<?php

class DomCompiler {
    private string $templatePath;
    private string $cacheDir;
    private DOMDocument $dom;
    private DOMXPath $xpath;
    private array $replacements = [];

    public function __construct(string $templatePath, string $cacheDir = __DIR__ . '/build-template') {
        $this->templatePath = $templatePath;
        $this->cacheDir = $cacheDir;
        
        if (!file_exists($this->cacheDir)) {
            mkdir($this->cacheDir, 0777, true);
        }
    }

    /**
     * Memeriksa apakah cache valid dan tidak perlu di-rebuild
     */
    private function isCacheValid(string $cacheFile): bool {
        if (!file_exists($cacheFile)) return false;
        return filemtime($this->templatePath) <= filemtime($cacheFile);
    }

    /**
     * Set teks ke dalam elemen spesifik menggunakan XPath/CSS Selector
     */
    public function setText(string $selector, string $phpVariable): self {
        // TODO: Antigravity, implementasikan pencarian node, 
        // ganti isinya dengan marker unik, dan daftarkan ke $this->replacements
        return $this;
    }

    /**
     * Setup looping untuk list/komponen
     */
    public function setLoop(string $containerSelector, string $itemSelector, string $arrayVariable, string $alias, callable $callback): self {
        // TODO: Antigravity, implementasikan logic isolasi elemen template,
        // penempatan marker __FOREACH_START__ dan __FOREACH_END__,
        // eksekusi callback untuk child node, dan daftarkan ke $this->replacements
        return $this;
    }

    /**
     * Eksekusi kompilasi DOM ke String PHP dan simpan sebagai Cache
     */
    public function compile(): string {
        $cacheFile = $this->cacheDir . '/' . basename($this->templatePath) . '.php';

        if ($this->isCacheValid($cacheFile)) {
            return $cacheFile;
        }

        // TODO: Antigravity, loadHTML, jalankan manipulasi marker dari array state,
        // saveHTML, replace marker dengan sintaks PHP asli, dan simpan ke $cacheFile.
        
        return $cacheFile;
    }

    /**
     * Render final output
     */
    public function render(array $data): void {
        $cacheFile = $this->compile();
        extract($data);
        require $cacheFile;
    }
}

// ==========================================
// PENGGUNAAN (Simulasi eksekusi)
// ==========================================
/*
$compiler = new DomCompiler(__DIR__ . '/blog-list.html');

$compiler
    ->setText("//title", '$pageTitle')
    ->setLoop("//div[@id='blog-container']", ".//article[contains(@class, 'post-item')]", '$blogs', '$blog', function($item) {
        $item->setText(".//h2", '$blog["title"]');
        $item->setText(".//p", '$blog["summary"]');
    })
    ->render([
        'pageTitle' => 'Eksperimen DOM Templating',
        'blogs' => [
            ['title' => 'Vibe Coding', 'summary' => 'Menyenangkan...'],
            ['title' => 'Single File', 'summary' => 'Cepat dan ringan...']
        ]
    ]);
*/
?>
