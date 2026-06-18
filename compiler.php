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

        // Fungsi rekursif untuk mengurai rules array (text, attribute, loops)
        $applyRules = function(array $currentRules, ?DOMNode $context = null) use (&$applyRules, $xpath, &$replacements, $getMarker, $dom) {
            
            // 1. Aturan Teks
            if (isset($currentRules['texts'])) {
                foreach ($currentRules['texts'] as $selector => $phpVar) {
                    $nodes = $context ? $xpath->query($selector, $context) : $xpath->query($selector);
                    if ($nodes !== false) {
                        foreach ($nodes as $node) {
                            $marker = $getMarker();
                            $node->nodeValue = $marker;
                            $replacements[$marker] = "<?= htmlspecialchars({$phpVar} ?? '', ENT_QUOTES, 'UTF-8') ?>";
                        }
                    }
                }
            }

            // 2. Aturan Atribut
            if (isset($currentRules['attributes'])) {
                foreach ($currentRules['attributes'] as $selector => $attrs) {
                    $nodes = $context ? $xpath->query($selector, $context) : $xpath->query($selector);
                    if ($nodes !== false) {
                        foreach ($nodes as $node) {
                            if ($node instanceof DOMElement) {
                                foreach ($attrs as $attr => $phpVar) {
                                    $marker = $getMarker();
                                    $node->setAttribute($attr, $marker);
                                    $replacements[$marker] = "<?= htmlspecialchars({$phpVar} ?? '', ENT_QUOTES, 'UTF-8') ?>";
                                }
                            }
                        }
                    }
                }
            }

            // 3. Aturan Looping
            if (isset($currentRules['loops'])) {
                foreach ($currentRules['loops'] as $containerSel => $loopConfig) {
                    $containers = $context ? $xpath->query($containerSel, $context) : $xpath->query($containerSel);
                    if ($containers !== false) {
                        foreach ($containers as $container) {
                            $itemSel = $loopConfig['item'];
                            $items = $xpath->query($itemSel, $container);
                            
                            if ($items !== false && $items->length > 0) {
                                $templateNode = $items->item(0);
                                
                                // Aplikasikan rules secara rekursif pada template node (child element)
                                $applyRules($loopConfig, $templateNode);
                                
                                $startMarker = $getMarker();
                                $endMarker = $getMarker();
                                
                                $arrayVar = $loopConfig['array'];
                                $alias = $loopConfig['alias'];
                                
                                $replacements[$startMarker] = "<?php foreach ({$arrayVar} as {$alias}): ?>";
                                $replacements[$endMarker] = "<?php endforeach; ?>";
                                
                                // Sisipkan PHP Foreach tag sebelum dan sesudah node template
                                $container->insertBefore($dom->createTextNode($startMarker), $templateNode);
                                if ($templateNode->nextSibling) {
                                    $container->insertBefore($dom->createTextNode($endMarker), $templateNode->nextSibling);
                                } else {
                                    $container->appendChild($dom->createTextNode($endMarker));
                                }
                                
                                // Bersihkan item dummy lainnya di dalam container HTML
                                for ($i = 1; $i < $items->length; $i++) {
                                    $container->removeChild($items->item($i));
                                }
                            }
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
