<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php $engine->e($title ?? 'WooCommerce REST API'); ?> - WooCommerce REST API Documentation</title>
    <link rel="icon" type="image/png" href="<?php $engine->e($baseUrl); ?>images/woo_logo.png">
    <link rel="stylesheet" href="<?php $engine->e($baseUrl); ?>css/style.css">
    <!-- Prism.js for syntax highlighting -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/themes/prism-tomorrow.min.css">
</head>
<body>
    <header class="site-header">
        <div class="header-content">
            <a href="<?php $engine->e($baseUrl); ?>index.html" class="logo">
                <img src="<?php $engine->e($baseUrl); ?>images/woo_logo.png" alt="WooCommerce" height="32">
                <span>WooCommerce REST API Documentation</span>
            </a>
            <button class="menu-toggle" aria-label="Toggle navigation">
                <span></span>
                <span></span>
                <span></span>
            </button>
        </div>
    </header>

    <div class="site-container">
        <aside class="sidebar" id="sidebar">
            <?php echo $engine->render('sidebar', ['categories' => $categories, 'staticPages' => $staticPages ?? [], 'baseUrl' => $baseUrl, 'currentPath' => $currentPath ?? '']); ?>
        </aside>

        <main class="content">
            <?php echo $content; ?>
        </main>
    </div>

    <!-- Prism.js for syntax highlighting -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/prism.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-markup.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-markup-templating.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-bash.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-javascript.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-php.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-python.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-ruby.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-json.min.js"></script>
    <script src="<?php $engine->e($baseUrl); ?>js/main.js"></script>
</body>
</html>
