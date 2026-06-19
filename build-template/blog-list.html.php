<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($pageTitle ?? '', ENT_QUOTES, 'UTF-8') ?></title>
    <style>
        body { font-family: sans-serif; padding: 20px; line-height: 1.6; }
        .banner-promo { background: #ffd700; padding: 10px; text-align: center; margin-bottom: 20px; }
        .alert { background: #ffebee; color: #c62828; padding: 10px; margin-bottom: 10px; border-radius: 4px; }
        .debug-panel { background: #333; color: lime; padding: 10px; font-family: monospace; margin-bottom: 20px; }
        .thumbnail { max-width: 150px; display: block; margin-bottom: 10px; }
        .nav { list-style: none; padding: 0; display: flex; gap: 15px; background: #eee; padding: 10px; }
        .nav a { text-decoration: none; color: #333; font-weight: bold; }
        .content { display: block; padding: 15px; border: 1px solid #ccc; background: #fafafa; margin-top: 10px; }
        .user-controls { margin-bottom: 20px; }
        .user-controls a, .user-controls button { padding: 8px 15px; text-decoration: none; border-radius: 4px; border: none; cursor: pointer; }
        .btn-primary { background: #4a90e2; color: white; }
        .btn-danger { background: #e74c3c; color: white; }
    </style>
<?= "<style> .injected-style { border: 2px dashed purple; padding: 10px; } </style>" ?? '' ?></head>
<body>
    <!-- 5. RUN-TIME LOGIC (IF Murni) -->
    <?php if ($isPromoActive): ?><div class="banner-promo">
        <strong>PROMO:</strong> Dapatkan fitur premium sekarang juga!
    </div><?php endif; ?>

    <header>
        <div class="user-controls">
            <!-- 5. RUN-TIME LOGIC (IF & ELSE) -->
            <?php if ($isLoggedIn): ?><a href="?page=dashboard" class="btn-dashboard btn-primary">Ke Dashboard</a><?php endif; ?>
            <?php if (!$isLoggedIn): ?><a href="?page=login" class="btn-login btn-primary">Masuk</a><?php endif; ?>

            <!-- 5. RUN-TIME LOGIC (TERNARY) -->
            <button class="<?= htmlspecialchars($isLoggedIn ? "btn-danger" : "btn-primary" ?? '', ENT_QUOTES, 'UTF-8') ?>" href="<?= htmlspecialchars($isLoggedIn ? "?logout=1" : "?page=login" ?? '', ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($isLoggedIn ? "Logout" : "Login" ?? '', ENT_QUOTES, 'UTF-8') ?></button>
        </div>

        <!-- 5. RUN-TIME LOGIC (FOREACH) -->
        <ul class="nav">
            <?php foreach ($menus as $menu): ?><li><a href="<?= htmlspecialchars($menu["url"] ?? '', ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($menu["label"] ?? '', ENT_QUOTES, 'UTF-8') ?></a></li><?php endforeach; ?>
            
        </ul>
    </header>

    <main><?= "<div class=\"alert\">Ini hasil injeksi @prepend di awal &lt;main&gt;</div>" ?? '' ?>
        <hr>
        <!-- 3. TARGETING TUNGGAL (^ prefix) -->
        <div class="alert"><?= htmlspecialchars("Ini alert pertama saja yang terganti (" . date("H:i:s") . ")" ?? '', ENT_QUOTES, 'UTF-8') ?></div>
        <div class="alert">Alert Kedua (Abaikan)</div>
        <hr>

        <!-- 4. COMPILE-TIME CLEANUP (@remove Node) -->
        

        <!-- 4. COMPILE-TIME CLEANUP (@remove Attribute) -->
        <p><strong>Gambar di bawah seharusnya tidak memiliki border merah (style) dan atribut data-dummy:</strong></p>
        <img class="thumbnail" src="<?= htmlspecialchars($realImageUrl ?? '', ENT_QUOTES, 'UTF-8') ?>" alt="Thumbnail">

        <!-- 1. EXPLICIT TEXT (@text) -->
        <h3><?= htmlspecialchars($subJudul ?? '', ENT_QUOTES, 'UTF-8') ?></h3>

        <!-- 2. MANIPULASI ATRIBUT -->
        <p>
            <a class="external" href="<?= htmlspecialchars($linkUrl ?? '', ENT_QUOTES, 'UTF-8') ?>" target="<?= htmlspecialchars("_blank" ?? '', ENT_QUOTES, 'UTF-8') ?>">Eksternal Link Dummy</a> | 
            <a data-type="link" href="#"><?= htmlspecialchars("Teks Link Baru" ?? '', ENT_QUOTES, 'UTF-8') ?></a>
        </p>

        <!-- 1. RAW HTML -->
        <span class="content injected-style"><?= $htmlContent ?? '' ?></span>
    </main>
</body>
</html>
