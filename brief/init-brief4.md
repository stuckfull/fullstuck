That's excellent. However, we found a missing feature based on the README you generated earlier regarding injecting text AND attributes simultaneously.

We will NOT use the `"text"` key as it might collide with the `<text>` SVG tag. Instead, we will use a new logic directive: `"@text"`. This keeps the API consistent (`@html` for innerHTML, `@text` for innerText with XSS protection).

### TASK:
Inside the `compiler.php`, specifically inside the `$applyRules` closure where you handle nested rules (`elseif (is_array($value))`), add the logic for the `@text` directive right next to `@html`.

Logic for `@text`:
1. Loop through `$nodes`.
2. Assign a new marker to `$node->nodeValue`.
3. Register the marker to `$replacements` WITH `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')` protection (unlike `@html`).
4. `unset($value['@text']);` so it doesn't get processed as a nested selector.

Provide ONLY the updated `compiler.php` code. Keep everything else intact.