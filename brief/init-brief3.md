Iterate on the `compiler.php` file. We need to add a new Logic Directive to safely inject RAW HTML (bypassing XSS protection) for cases like rendering WYSIWYG editor content.

### NEW DIRECTIVE RULE: `@html`
- If the array key is `"@html"` (or a similar raw injection marker you find fits the DSL):
  - Act exactly like standard Text Manipulation (finding the parent context node, assigning a unique marker to its `nodeValue`).
  - BUT, when registering it to the `$replacements` array, DO NOT wrap the PHP execution in `htmlspecialchars`. 
  - Just output it as raw PHP echo: `<?= {$value} ?? '' ?>`

### IMPLEMENTATION DETAIL
Inside the `$applyRules` closure, in the `elseif (is_array($value))` block, right next to where you handle `'@foreach'`, add a handler for `'@html'`. 

Example usage expected in index.php:
"div.content" => [
    "@html" => '$blog["wysiwyg_content"]'
]

Return the updated `compiler.php` keeping everything else intact.