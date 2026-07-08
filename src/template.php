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
function fst_template(string $templatePath, array $data, array $rules, ?string $cacheDir = null, bool $forceRebuild = false): void {
    if ($cacheDir === null) {
        $cacheDir = defined('FST_ROOT_DIR') ? FST_ROOT_DIR . '/view-cache' : sys_get_temp_dir() . '/fst_view_cache';
    }
    
    if (!file_exists($templatePath)) {
        throw new \RuntimeException("Template not found: {$templatePath}");
    }

    if (!file_exists($cacheDir)) {
        mkdir($cacheDir, 0755, true);
    }
    
    $base = defined('FST_ROOT_DIR') ? FST_ROOT_DIR : '';
    $relative_path = str_replace([$base, '/', '\\', ':'], ['', '__', '__', ''], $templatePath);
    $relative_path = ltrim($relative_path, '_');
    $cacheFile = $cacheDir . '/' . $relative_path . '.php';

    array_walk_recursive($rules, function($item) {
        if ($item instanceof \Closure) {
            if (function_exists('fst_abort')) {
                fst_abort(500, "AI Warning: fst_template does not support Closures. Use PHP expression strings instead!");
            }
            throw new \Exception("AI Warning: fst_template does not support Closures. Use PHP expression strings instead!");
        }
    });

    $useHtml5 = class_exists('\Dom\HTMLDocument');
    $rules_hash = md5(serialize($rules) . ($useHtml5 ? 'html5' : 'legacy'));
    $cache_valid = false;
    if (!$forceRebuild && file_exists($cacheFile) && filemtime($templatePath) <= filemtime($cacheFile)) {
        $fp = fopen($cacheFile, 'r');
        if ($fp) {
            $first_line = fgets($fp);
            fclose($fp);
            if (preg_match('/^\/\/\s*fst_rules_hash:\s*([a-f0-9]{32})/', trim(str_replace(['<?php', '?>'], '', $first_line)), $matches)) {
                if ($matches[1] === $rules_hash) {
                    $cache_valid = true;
                }
            }
        }
    }

    // Cek validitas cache
    if (!$cache_valid) {
        
        $html = file_get_contents($templatePath);
        
        if ($useHtml5) {
            $dom = \Dom\HTMLDocument::createFromString($html, \LIBXML_NOERROR | \LIBXML_HTML_NOIMPLIED | \Dom\HTML_NO_DEFAULT_NS);
        } else {
            $dom = new DOMDocument();
            libxml_use_internal_errors(true);
            if ($html) {
                // Force UTF-8 encoding
                $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
            }
            libxml_clear_errors();
        }
        
        $replacements = [];
        $markerCount = 0;
        
        // Generator marker unik (untuk text node & comment)
        $getMarker = function() use (&$markerCount) {
            $markerCount++;
            return "@@__FST_MARKER_{$markerCount}__@@";
        };
        
        // Generator marker untuk atribut (harus valid XML name, tanpa @)
        $getAttrMarker = function() use (&$markerCount) {
            $markerCount++;
            return "fst_attr_marker_{$markerCount}";
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

        $xpath = $useHtml5 ? null : new DOMXPath($dom);
        $xpath5 = $useHtml5 ? new \Dom\XPath($dom) : null;

        // Fungsi rekursif untuk mengurai rules array (DSL Declarative Baru)
        $applyRules = function(array $currentRules, $context = null) use (&$applyRules, $dom, $xpath, $xpath5, $useHtml5, &$replacements, $getMarker, $getAttrMarker, $css2xpath) {
            foreach ($currentRules as $key => $value) {
                
                // 1. Attribute Manipulation Directive (Wrapped in [...])
                if (str_starts_with($key, '[') && str_ends_with($key, ']') && is_object($context) && method_exists($context, 'setAttribute') && strpos($key, '=') === false) {
                    $attrName = substr($key, 1, -1);
                    if ($value === '@remove') {
                        $context->removeAttribute($attrName);
                    } else {
                        $marker = $getMarker();
                        $context->setAttribute($attrName, $marker);
                        $replacements[$marker] = "<?= htmlspecialchars({$value} ?? '', ENT_QUOTES, 'UTF-8') ?>";
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

                $targetNodes = [];
                $useXPath = false;
                if ($useHtml5 && !str_starts_with($key, '//') && !str_starts_with($key, './/')) {
                    try {
                        if ($isSingleSelection) {
                            $node = $context ? $context->querySelector($key) : $dom->querySelector($key);
                            if ($node) $targetNodes[] = $node;
                        } else {
                            $nodeList = $context ? $context->querySelectorAll($key) : $dom->querySelectorAll($key);
                            if ($nodeList->length > 0) {
                                foreach ($nodeList as $n) $targetNodes[] = $n;
                            }
                        }
                    } catch (\Exception $e) {
                        // Fallback ke xpath jika selector CSS native tidak valid
                        $useXPath = true;
                    }
                } else {
                    $useXPath = true;
                }

                if ($useXPath) {
                    $xpathSel = $css2xpath($key);
                    $xp = $useHtml5 ? $xpath5 : $xpath;
                    $nodeList = $xp->query($xpathSel, $context ?? $dom);
                    
                    if ($nodeList !== false && $nodeList->length > 0) {
                        if ($isSingleSelection) {
                            $targetNodes[] = $nodeList->item(0);
                        } else {
                            foreach ($nodeList as $n) $targetNodes[] = $n;
                        }
                    }
                }

                if (empty($targetNodes)) continue;

                // 3. Text Manipulation (Jika value berupa STRING)
                if (is_string($value)) {
                    if ($value === '@remove') {
                        foreach ($targetNodes as $node) {
                            if ($node->parentNode) {
                                $node->parentNode->removeChild($node);
                            }
                        }
                        continue;
                    }

                    foreach ($targetNodes as $node) {
                        $marker = $getMarker();
                        $node->textContent = $marker;
                        $replacements[$marker] = "<?= htmlspecialchars({$value} ?? '', ENT_QUOTES, 'UTF-8') ?>";
                    }
                } 
                // 4. Nested Rules & Logic Directives (Jika value berupa ARRAY)
                elseif (is_array($value)) {
                    
                    if (isset($value['@attrs'])) {
                        foreach ($targetNodes as $node) {
                            $attrMarker = $getAttrMarker();
                            if (is_object($node) && method_exists($node, 'setAttribute')) {
                                $node->setAttribute($attrMarker, $attrMarker);
                                // Ganti atribut marker lengkap beserta nilainya dengan kode PHP
                                $replacements[$attrMarker . '="' . $attrMarker . '"'] = "<?= {$value['@attrs']} ?>";
                            }
                        }
                        unset($value['@attrs']);
                    }

                    if (isset($value['@if'])) {
                        foreach ($targetNodes as $node) {
                            $startMarker = $getMarker();
                            $endMarker = $getMarker();
                            
                            $replacements[$startMarker] = "<?php if ({$value['@if']}): ?>";
                            $replacements[$endMarker] = "<?php endif; ?>";
                            
                            // Gunakan comment node untuk menghindari HierarchyRequestError di root document 
                            // dan mencegah libxml2 text-node hoisting
                            $startCommentNode = $dom->createComment($startMarker);
                            $endCommentNode = $dom->createComment($endMarker);
                            
                            $node->parentNode->insertBefore($startCommentNode, $node);
                            if ($node->nextSibling) {
                                $node->parentNode->insertBefore($endCommentNode, $node->nextSibling);
                            } else {
                                $node->parentNode->appendChild($endCommentNode);
                            }
                        }
                        unset($value['@if']);
                    }

                    if (isset($value['@text'])) {
                        // Logic Directive: @text (Safe Text with XSS protection)
                        foreach ($targetNodes as $node) {
                            $marker = $getMarker();
                            $node->textContent = $marker;
                            $replacements[$marker] = "<?= htmlspecialchars({$value['@text']} ?? '', ENT_QUOTES, 'UTF-8') ?>";
                        }
                        unset($value['@text']);
                    }

                    if (isset($value['@html'])) {
                        // Logic Directive: @html (Raw HTML bypass XSS)
                        foreach ($targetNodes as $node) {
                            $marker = $getMarker();
                            $node->textContent = $marker;
                            $replacements[$marker] = "<?= {$value['@html']} ?? '' ?>";
                        }
                        unset($value['@html']);
                    }

                    if (isset($value['@append'])) {
                        // Logic Directive: @append (Insert raw HTML at the end of node's children)
                        foreach ($targetNodes as $node) {
                            $marker = $getMarker();
                            $replacements[$marker] = "<?= {$value['@append']} ?? '' ?>";
                            $node->appendChild($dom->createComment($marker));
                        }
                        unset($value['@append']);
                    }

                    if (isset($value['@prepend'])) {
                        // Logic Directive: @prepend (Insert raw HTML at the beginning of node's children)
                        foreach ($targetNodes as $node) {
                            $marker = $getMarker();
                            $replacements[$marker] = "<?= {$value['@prepend']} ?? '' ?>";
                            $node->insertBefore($dom->createComment($marker), $node->firstChild);
                        }
                        unset($value['@prepend']);
                    }

                    if (isset($value['@foreach'])) {
                        // Logic Directive: @foreach
                        $templateNode = $targetNodes[0];
                        $container = $templateNode->parentNode;
                        
                        $foreachStr = $value['@foreach'];
                        unset($value['@foreach']); // Hapus directive agar tidak dieksekusi di nested rule
                        
                        $startMarker = $getMarker();
                        $endMarker = $getMarker();
                        
                        $replacements[$startMarker] = "<?php foreach ({$foreachStr}): ?>";
                        $replacements[$endMarker] = "<?php endforeach; ?>";
                        
                        $startCommentNode = $dom->createComment($startMarker);
                        $endCommentNode = $dom->createComment($endMarker);
                        
                        // Inject foreach wrap
                        $container->insertBefore($startCommentNode, $templateNode);
                        if ($templateNode->nextSibling) {
                            $container->insertBefore($endCommentNode, $templateNode->nextSibling);
                        } else {
                            $container->appendChild($endCommentNode);
                        }
                        
                        // Remove siblings matching the selector to avoid duplication
                        for ($i = 1; $i < count($targetNodes); $i++) {
                            $nodeToRemove = $targetNodes[$i];
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
        
        $htmlOut = $useHtml5 ? $dom->saveHtml() : $dom->saveHTML();
        
        // Fix encoded scripts and styles in HTML5 parser, and restore raw PHP tags, and remove XML hack in legacy mode
        if ($useHtml5) {
            $htmlOut = preg_replace_callback('/(<(?:script|style)[^>]*>)(.*?)(<\/(?:script|style)>)/is', function($m) {
                return $m[1] . htmlspecialchars_decode($m[2], ENT_QUOTES) . $m[3];
            }, $htmlOut);
            
            // HTML5 parser converts PHP blocks into bogus comments like <!--?php ... ?-->. Restore them.
            $htmlOut = str_replace(['<!--?', '?-->'], ['<?', '?>'], $htmlOut);
            
            // HTML5 parser generates bogus closing tags for void elements. Remove them.
            $htmlOut = preg_replace('/<\/(?:area|base|br|col|embed|hr|img|input|link|meta|param|source|track|wbr)>/i', '', $htmlOut);
        } else {
            $htmlOut = str_replace('<?xml encoding="utf-8" ?>', '', $htmlOut);
        }
        
        // Replace semua marker dengan script PHP dalam 1 pass (Lebih cepat & mencegah double-replace)
        $trans = [];
        foreach ($replacements as $marker => $phpCode) {
            $trans['<!--' . $marker . '-->'] = $phpCode; // Untuk comment node
            $trans[$marker] = $phpCode; // Untuk attribute & text node
        }
        $htmlOut = strtr($htmlOut, $trans);
        
        $htmlOut = "<?php // fst_rules_hash: {$rules_hash} ?>\n" . $htmlOut;
        file_put_contents($cacheFile, $htmlOut);
    }

    // Render file cache (Output)
    $shared_data = function_exists('fst_app') ? (fst_app('shared_view_data') ?? []) : [];
    $data = array_merge($shared_data, $data);
    extract($data, EXTR_SKIP);
    require $cacheFile;
}

/**
 * Merender file HTML menjadi dinamis dan mengembalikan hasilnya sebagai string.
 */
function fst_template_render(string $templatePath, array $data, array $rules, ?string $cacheDir = null, bool $forceRebuild = false): string {
    ob_start();
    fst_template($templatePath, $data, $rules, $cacheDir, $forceRebuild);
    return ob_get_clean();
}
