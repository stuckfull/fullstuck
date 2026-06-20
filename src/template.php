<?php

/**
 * Merender file HTML menjadi dinamis melalui DOM Manipulation dan men-cache hasilnya ke file PHP.
 * Pendekatan prosedural/deklaratif (tanpa class/OOP).
 * 
 * @param string $templatePath Path ke file HTML statis
 * @param array $data Array asosiatif berisi data yang ingin dirender
 * @param array $rules Aturan (rules) injeksi DOM berupa array DSL baru
 * @param string $cacheDir Folder tujuan penyimpanan cache
 * @param bool $forceRebuild Paksa recompile mengabaikan cache
 */
function fst_template(string $templatePath, array $data, array $rules, string $cacheDir = __DIR__ . '/build-template', bool $forceRebuild = false): void {
    if (!file_exists($templatePath)) {
        throw new \RuntimeException("Template not found: {$templatePath}");
    }

    if (!file_exists($cacheDir)) {
        mkdir($cacheDir, 0755, true);
    }
    
    $cacheFile = $cacheDir . '/' . basename($templatePath) . '.php';

    // Cek validitas cache
    if ($forceRebuild || !file_exists($cacheFile) || filemtime($templatePath) > filemtime($cacheFile)) {
        
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
            $selector = trim($selector);
            
            // Native XPath escape hatch
            if (str_starts_with($selector, '//') || str_starts_with($selector, './/')) {
                return $selector;
            }
            
            // Blacklist check
            if (strpos($selector, ':') !== false || strpos($selector, '+') !== false || strpos($selector, '~') !== false) {
                return './/FST_BLACKLISTED_NODE';
            }
            
            $paths = [];
            foreach (explode(',', $selector) as $sel) {
                $sel = trim($sel);
                $sel = preg_replace('/\s*>\s*/', '/', $sel); // Child
                $sel = preg_replace('/\s+/', '//', $sel); // Descendant
                $sel = preg_replace('/#([\w\-]+)/', '[@id="$1"]', $sel); // ID
                $sel = preg_replace('/\.([\w\-]+)/', '[contains(concat(" ", normalize-space(@class), " "), " $1 ")]', $sel); // Class
                
                // Convert CSS [attr="val"] to XPath [@attr="val"]
                $sel = preg_replace('/\[([\w\-]+)=([\'"]?.*?[\'"]?)\]/', '[@$1=$2]', $sel);
                // Convert [attr] to [@attr]
                $sel = preg_replace('/\[([\w\-]+)\]/', '[@$1]', $sel);
                // Handle tagless attributes by prepending *
                $sel = preg_replace('/(^|\/|\|)(\[)/', '$1*$2', $sel);
                
                // Prepend relative context if not already set
                if (!str_starts_with($sel, '/') && !str_starts_with($sel, '.')) {
                    $sel = './/' . $sel;
                }
                
                $paths[] = $sel;
            }
            return implode(' | ', $paths);
        };

        // Fungsi rekursif untuk mengurai rules array (DSL Declarative Baru)
        $applyRules = function(array $currentRules, ?DOMNode $context = null) use (&$applyRules, $xpath, &$replacements, $getMarker, $dom, $css2xpath) {
            foreach ($currentRules as $key => $value) {
                
                // 1. Attribute Manipulation Directive (Wrapped in [...])
                if (str_starts_with($key, '[') && str_ends_with($key, ']')) {
                    if ($context instanceof DOMElement) {
                        $attrName = substr($key, 1, -1);
                        if ($value === '@remove') {
                            $context->removeAttribute($attrName);
                        } else {
                            $marker = $getMarker();
                            $context->setAttribute($attrName, $marker);
                            $replacements[$marker] = "<?= htmlspecialchars({$value} ?? '', ENT_QUOTES, 'UTF-8') ?>";
                        }
                    }
                    continue;
                }

                // Abaikan jika directive lolos ke sini sebagai key top-level
                if (str_starts_with($key, '@')) {
                    continue;
                }

                // 2. CSS Selector
                $isSingleSelection = false;
                if (str_starts_with($key, '^')) {
                    $isSingleSelection = true;
                    $key = substr($key, 1);
                }

                $xpathSel = $css2xpath($key);
                $nodes = $context ? $xpath->query($xpathSel, $context) : $xpath->query($xpathSel);
                
                if ($nodes === false || $nodes->length === 0) continue;

                $targetNodes = [];
                if ($isSingleSelection) {
                    $targetNodes[] = $nodes->item(0);
                } else {
                    foreach ($nodes as $n) $targetNodes[] = $n;
                }

                // 3. Text Manipulation (Jika value berupa STRING)
                if (is_string($value)) {
                    if ($value === '@remove') {
                        foreach ($targetNodes as $node) {
                            $node->parentNode->removeChild($node);
                        }
                        continue;
                    }

                    foreach ($targetNodes as $node) {
                        $marker = $getMarker();
                        $node->nodeValue = $marker;
                        $replacements[$marker] = "<?= htmlspecialchars({$value} ?? '', ENT_QUOTES, 'UTF-8') ?>";
                    }
                } 
                // 4. Nested Rules & Logic Directives (Jika value berupa ARRAY)
                elseif (is_array($value)) {
                    
                    if (isset($value['@if'])) {
                        foreach ($targetNodes as $node) {
                            $startMarker = $getMarker();
                            $endMarker = $getMarker();
                            
                            $replacements[$startMarker] = "<?php if ({$value['@if']}): ?>";
                            $replacements[$endMarker] = "<?php endif; ?>";
                            
                            $startTextNode = $dom->createTextNode($startMarker);
                            $endTextNode = $dom->createTextNode($endMarker);
                            
                            $node->parentNode->insertBefore($startTextNode, $node);
                            if ($node->nextSibling) {
                                $node->parentNode->insertBefore($endTextNode, $node->nextSibling);
                            } else {
                                $node->parentNode->appendChild($endTextNode);
                            }
                        }
                        unset($value['@if']);
                    }

                    if (isset($value['@text'])) {
                        // Logic Directive: @text (Safe Text with XSS protection)
                        foreach ($targetNodes as $node) {
                            $marker = $getMarker();
                            $node->nodeValue = $marker;
                            $replacements[$marker] = "<?= htmlspecialchars({$value['@text']} ?? '', ENT_QUOTES, 'UTF-8') ?>";
                        }
                        unset($value['@text']);
                    }

                    if (isset($value['@html'])) {
                        // Logic Directive: @html (Raw HTML bypass XSS)
                        foreach ($targetNodes as $node) {
                            $marker = $getMarker();
                            $node->nodeValue = $marker;
                            $replacements[$marker] = "<?= {$value['@html']} ?? '' ?>";
                        }
                        unset($value['@html']);
                    }

                    if (isset($value['@append'])) {
                        // Logic Directive: @append (Insert raw HTML at the end of node's children)
                        foreach ($targetNodes as $node) {
                            $marker = $getMarker();
                            $replacements[$marker] = "<?= {$value['@append']} ?? '' ?>";
                            $node->appendChild($dom->createTextNode($marker));
                        }
                        unset($value['@append']);
                    }

                    if (isset($value['@prepend'])) {
                        // Logic Directive: @prepend (Insert raw HTML at the beginning of node's children)
                        foreach ($targetNodes as $node) {
                            $marker = $getMarker();
                            $replacements[$marker] = "<?= {$value['@prepend']} ?? '' ?>";
                            $node->insertBefore($dom->createTextNode($marker), $node->firstChild);
                        }
                        unset($value['@prepend']);
                    }

                    if (isset($value['@foreach'])) {
                        // Logic Directive: @foreach
                        $templateNode = $nodes->item(0);
                        $container = $templateNode->parentNode;
                        
                        $foreachStr = $value['@foreach'];
                        unset($value['@foreach']); // Hapus directive agar tidak dieksekusi di nested rule
                        
                        $startMarker = $getMarker();
                        $endMarker = $getMarker();
                        
                        $replacements[$startMarker] = "<?php foreach ({$foreachStr}): ?>";
                        $replacements[$endMarker] = "<?php endforeach; ?>";
                        
                        // Inject foreach wrap
                        $container->insertBefore($dom->createTextNode($startMarker), $templateNode);
                        if ($templateNode->nextSibling) {
                            $container->insertBefore($dom->createTextNode($endMarker), $templateNode->nextSibling);
                        } else {
                            $container->appendChild($dom->createTextNode($endMarker));
                        }
                        
                        // Remove siblings matching the selector to avoid duplication
                        for ($i = 1; $i < $nodes->length; $i++) {
                            $nodeToRemove = $nodes->item($i);
                            if ($nodeToRemove->parentNode) {
                                $nodeToRemove->parentNode->removeChild($nodeToRemove);
                            }
                        }
                        
                        // Eksekusi nested rule pada $templateNode
                        if (!empty($value)) {
                            $applyRules($value, $templateNode);
                        }
                        
                    } else {
                        // Atribut, Teks, dan Nested Selector pada target nodes
                        if (!empty($value)) {
                            foreach ($targetNodes as $node) {
                                $applyRules($value, $node);
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
        
        // Replace semua marker dengan script PHP
        foreach ($replacements as $marker => $phpCode) {
            $htmlOut = str_replace($marker, $phpCode, $htmlOut);
        }
        
        file_put_contents($cacheFile, $htmlOut);
    }

    // Render file cache (Output)
    $shared_data = function_exists('fst_app') ? (fst_app('shared_view_data') ?? []) : [];
    $data = array_merge($shared_data, $data);
    extract($data, EXTR_SKIP);
    require $cacheFile;
}
