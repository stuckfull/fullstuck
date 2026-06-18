Act as an Expert PHP Developer specializing in zero-dependency, procedural architectures, and JIT (Just-In-Time) compilation. We are iterating on a DOM-based Templating Engine (compiler.php) that converts static HTML into cached PHP files using `DOMDocument`.

Your task is to REFACTOR the existing `$applyRules` logic and the `$css2xpath` converter to strictly support a new Declarative DSL (Domain-Specific Language) array syntax and enforce strict CSS Selector constraints.

### 1. SYNTAX RULES (The New DSL)
The ruleset array will now follow these exact semantics:

- **Logic Directives (Prefix `@`):**
  Used exclusively for structural compiler instructions. 
  Example: `"@foreach" => '$blogs as $blog'`. 
  Implementation: The compiler must extract this, split the string by ` as `, inject `<?php foreach(...): ?>` before the context node, and `<?php endforeach; ?>` after it. The context node acts as the template. Any other siblings matching the context node must be removed to avoid duplication.

- **Attribute Manipulation (Wrapped in `[...]`):**
  Used exclusively for setting HTML attributes on the active node.
  Example: `"[href]" => '$blog["url"]'` or `"[class]" => '$statusClass'`.
  Implementation: Extract the attribute name (remove brackets) and use `$node->setAttribute()`. Value must be XSS-protected via `htmlspecialchars()`.

- **Text Manipulation & Nested Rules (Standard CSS Selectors):**
  If a key is neither a directive nor an attribute, it is a CSS Selector.
  - If the value is a STRING: Inject it as text content (replace `nodeValue` with XSS-protected PHP echo).
  - If the value is an ARRAY: Treat it as nested rules, passing the matched nodes as the new context.

### 2. STRICT CSS SELECTOR CONSTRAINTS (Whitelist/Blacklist)
The `$css2xpath` function MUST be updated to strictly allow only the following basic selectors and safely ignore complex ones to prevent regex overhead or DOM errors.

✅ **WHITELIST (Must Support):**
- Tag Selectors: `h1`, `p`, `article`
- ID Selectors: `#container`
- Class Selectors: `.post-item`
- Compound Selectors: `article.post-item`, `div#content`
- Descendant Combinators: `.container h2` (separated by space)
- Direct Child Combinators: `ul.nav > li`
- Basic Attribute Selectors: `input[name="username"]`
- Native XPath Escape Hatch: Any string starting with `//` or `.//` MUST be returned as-is.

❌ **BLACKLIST (Must Ignore / Do Not Parse):**
- Pseudo-classes: `:hover`, `:nth-child()`, `:first-of-type`, `:not()`
- Pseudo-elements: `::before`, `::after`
- State Pseudo-classes: `:checked`, `:active`
- Sibling Combinators: `+`, `~`
*Instruction:* If `$css2xpath` detects blacklisted patterns (like `:` or `+` or `~`), it should ideally return a string that yields no DOM nodes safely, or strip them cleanly without breaking the base selector.

### 3. PREVIOUS REQUIREMENTS (Do Not Break)
- Maintain the procedural `render_template()` function wrapper. NO OOP/Classes.
- Maintain the `@@__FST_MARKER_X__@@` generator technique.
- Maintain the `<?xml encoding="utf-8" ?>` hack for proper UTF-8 parsing.
- Maintain the caching strategy (`build-template/` directory and `filemtime` checks).

- Basic Attribute Selectors: `input[name="username"]`, `img[src="logo.jpg"]`, or `[data-id]`. 
  (CRITICAL INSTRUCTION: Your `$css2xpath` regex MUST explicitly convert CSS attribute brackets `[attr="value"]` to XPath attribute notation `[@attr="value"]` by inserting the `@` symbol, and ensure tagless attributes like `[src="logo"]` are converted to `//*[@src="logo"]`).

Output the FULL, updated, and complete `compiler.php` code. Do not omit any parts. Make the code clean, functional, and highly optimized for performance.