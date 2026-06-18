<?php

/**
 * Merender file HTML menjadi dinamis melalui DOM Manipulation dan men-cache hasilnya ke file PHP.
 * Pendekatan prosedural/deklaratif (tanpa class/OOP).
 * 
 * @param string $templatePath Path ke file HTML statis
 * @param array $data Array asosiatif berisi data yang ingin dirender
 * @param array $rules Aturan (rules) injeksi DOM berupa array
 * @param string $cacheDir Folder tujuan penyimpanan cache
 */
function render_template(string $templatePath, array $data, array $rules, string $cacheDir = __DIR__ . '/build-template'): void {
    if (!file_exists($cacheDir)) {
        mkdir($cacheDir, 0777, true);
    }
    
    $cacheFile = $cacheDir . '/' . basename($templatePath) . '.php';

    // Cek validitas cache
    if (!file_exists($cacheFile) || filemtime($templatePath) > filemtime($cacheFile)) {
        
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $html = file_get_contents($templatePath);
        if ($html) {
            // Force UTF-8 encoding
            $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        }
        libxml_clear_errors();
        
        $xpath = new DOMXPath($dom);
        $replacements = [];
        $markerCount = 0;
        
        // Generator marker unik
        $getMarker = function() use (&$markerCount) {
            $markerCount++;
            return "@@__FST_MARKER_{$markerCount}__@@";
        };

        $css2xpath = function(string $selector): string {
            if (str_starts_with(trim($selector), '//') || str_starts_with(trim($selector), './/')) {
                return $selector;
            }
            $paths = [];
            foreach (explode(',', $selector) as $sel) {
                $sel = trim($sel);
                $sel = preg_replace('/\s*>\s*/', '/', $sel);
                $sel = preg_replace('/\s+/', '//', $sel);
                $sel = preg_replace('/#([\w\-]+)/', '[@id="$1"]', $sel);
                $sel = preg_replace('/\.([\w\-]+)/', '[contains(concat(" ", normalize-space(@class), " "), " $1 ")]', $sel);
                $sel = preg_replace('/(^|\/|\|)(\[)/', '$1*$2', $sel);
                if (!str_starts_with($sel, '/') && !str_starts_with($sel, '.')) {
                    $sel = './/' . $sel;
                }
                $paths[] = $sel;
            }
            return implode(' | ', $paths);
        };

        // Fungsi rekursif untuk mengurai rules array (Nested Selector / CSS Style)
        $applyRules = function(array $currentRules, ?DOMNode $context = null) use (&$applyRules, $xpath, &$replacements, $getMarker, $dom, $css2xpath) {
            foreach ($currentRules as $selector => $value) {
                // Abaikan reserved keys
                if ($selector === 'loop' || $selector === 'text' || str_starts_with($selector, '@')) {
                    continue;
                }

                $xpathSel = $css2xpath($selector);
                $nodes = $context ? $xpath->query($xpathSel, $context) : $xpath->query($xpathSel);
                
                if ($nodes === false || $nodes->length === 0) continue;

                if (is_string($value)) {
                    // Shorthand untuk mengatur text node
                    foreach ($nodes as $node) {
                        $marker = $getMarker();
                        $node->nodeValue = $marker;
                        $replacements[$marker] = "<?= htmlspecialchars({$value} ?? '', ENT_QUOTES, 'UTF-8') ?>";
                    }
                } elseif (is_array($value)) {
                    // Konfigurasi kompleks (Looping, Atribut, atau Nested Selectors)
                    if (isset($value['loop'])) {
                        // Aturan Looping
                        foreach ($nodes as $container) {
                            list($arrayVar, $alias, $itemSel) = $value['loop'];
                            $itemXPath = $css2xpath($itemSel);
                            $items = $xpath->query($itemXPath, $container);
                            
                            if ($items !== false && $items->length > 0) {
                                $templateNode = $items->item(0);
                                
                                // Terapkan sisa rule array ini pada item template (nesting)
                                $applyRules($value, $templateNode);
                                
                                $startMarker = $getMarker();
                                $endMarker = $getMarker();
                                
                                $replacements[$startMarker] = "<?php foreach ({$arrayVar} as {$alias}): ?>";
                                $replacements[$endMarker] = "<?php endforeach; ?>";
                                
                                // Sisipkan PHP Foreach tag
                                $container->insertBefore($dom->createTextNode($startMarker), $templateNode);
                                if ($templateNode->nextSibling) {
                                    $container->insertBefore($dom->createTextNode($endMarker), $templateNode->nextSibling);
                                } else {
                                    $container->appendChild($dom->createTextNode($endMarker));
                                }
                                
                                // Bersihkan item dummy lainnya
                                for ($i = 1; $i < $items->length; $i++) {
                                    $container->removeChild($items->item($i));
                                }
                            }
                        }
                    } else {
                        // Atribut, Eksplisit Text, dan Nested Selector biasa
                        foreach ($nodes as $node) {
                            if (isset($value['text'])) {
                                $marker = $getMarker();
                                $node->nodeValue = $marker;
                                $replacements[$marker] = "<?= htmlspecialchars({$value['text']} ?? '', ENT_QUOTES, 'UTF-8') ?>";
                            }
                            
                            foreach ($value as $k => $v) {
                                if (str_starts_with($k, '@') && $node instanceof DOMElement) {
                                    $attrName = substr($k, 1);
                                    $marker = $getMarker();
                                    $node->setAttribute($attrName, $marker);
                                    $replacements[$marker] = "<?= htmlspecialchars({$v} ?? '', ENT_QUOTES, 'UTF-8') ?>";
                                }
                            }
                            
                            // Eksekusi nested rules lebih dalam dengan elemen ini sebagai context
                            $applyRules($value, $node);
                        }
                    }
                }
            }
        };

        // Mulai eksekusi ruleset
        $applyRules($rules);
        
        $htmlOut = $dom->saveHTML();
        // Hapus hack header XML
        $htmlOut = str_replace('<?xml encoding="utf-8" ?>', '', $htmlOut);
        
        // Replace semua marker teks di file final dengan script PHP
        foreach ($replacements as $marker => $phpCode) {
            $htmlOut = str_replace($marker, $phpCode, $htmlOut);
        }
        
        file_put_contents($cacheFile, $htmlOut);
    }

    // Render file cache (Output)
    extract($data);
    require $cacheFile;
}
