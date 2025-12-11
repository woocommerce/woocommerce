<nav class="sidebar-nav">
    <a href="<?php $engine->e($baseUrl); ?>index.html" class="nav-home<?php echo ($currentPath === '' || $currentPath === 'index.html') ? ' active' : ''; ?>">
        <span class="home-icon">&#8962;</span>
        <span>Home</span>
    </a>

<?php
// Render static pages (excluding index)
$nonIndexPages = array_filter($staticPages ?? [], fn($p) => !$p->isIndex);
foreach ($nonIndexPages as $page):
    $pagePath = $page->slug . '.html';
    $isActive = ($currentPath === $pagePath);
?>
    <a href="<?php $engine->e($baseUrl . $pagePath); ?>" class="nav-static-page<?php echo $isActive ? ' active' : ''; ?>">
        <span><?php $engine->e($page->title); ?></span>
    </a>
<?php endforeach; ?>

<?php
if (!function_exists('renderCategory')) {
function renderCategory($category, $engine, $baseUrl, $currentPath, $depth = 0) {
    $hasChildren = count($category->getChildren()) > 0;
    // Only count endpoints that have descriptions (those without are not linked)
    $endpointsWithDescription = array_filter($category->getEndpoints(), fn($d) => $d->hasDescription());
    $hasEndpoints = count($endpointsWithDescription) > 0;
    $isExpanded = str_starts_with($currentPath, 'endpoints/' . str_replace('/', '-', strtolower($category->path)));
    $indent = str_repeat('  ', $depth);

    echo $indent . '<div class="nav-category' . ($isExpanded ? ' expanded' : '') . '" data-path="' . $engine->escape($category->path) . '">' . "\n";

    if ($hasChildren || $hasEndpoints) {
        echo $indent . '  <button class="nav-category-toggle" aria-expanded="' . ($isExpanded ? 'true' : 'false') . '">' . "\n";
        echo $indent . '    <span class="toggle-icon">' . ($isExpanded ? '&#9660;' : '&#9654;') . '</span>' . "\n";
        echo $indent . '    <span class="category-name">' . $engine->escape($category->name) . '</span>' . "\n";
        echo $indent . '  </button>' . "\n";
    } else {
        echo $indent . '  <span class="nav-category-label">' . $engine->escape($category->name) . '</span>' . "\n";
    }

    if ($hasChildren || $hasEndpoints) {
        echo $indent . '  <div class="nav-category-content"' . ($isExpanded ? '' : ' style="display: none;"') . '>' . "\n";

        // Render endpoints (only those with descriptions)
        foreach ($category->getEndpoints() as $descriptor) {
            // Skip endpoints without descriptions - they're not linked in navigation
            if (!$descriptor->hasDescription()) {
                continue;
            }

            $endpointPath = $baseUrl . 'endpoints/' . str_replace('/', '-', strtolower($category->path)) . '/' . strtolower(implode('-', $descriptor->verbs)) . '-' . slugify($descriptor->route) . '.html';
            $isActive = ($currentPath === $endpointPath);

            echo $indent . '    <a href="' . $engine->escape($endpointPath) . '" class="nav-endpoint' . ($isActive ? ' active' : '') . '">' . "\n";
            echo $indent . '      <span class="endpoint-name">' . $engine->escape($descriptor->name) . '</span>' . "\n";
            echo $indent . '    </a>' . "\n";
        }

        // Render child categories
        foreach ($category->getChildren() as $child) {
            renderCategory($child, $engine, $baseUrl, $currentPath, $depth + 2);
        }

        echo $indent . '  </div>' . "\n";
    }

    echo $indent . '</div>' . "\n";
}
}

if (!function_exists('slugify')) {
function slugify($route) {
    // Remove parameter patterns and convert to slug
    $slug = preg_replace('/\(\?P<([^>]+)>[^)]+\)/', '$1', $route);
    $slug = preg_replace('/[^a-zA-Z0-9]+/', '-', $slug);
    $slug = trim($slug, '-');
    return strtolower($slug);
}
}

// Check if any endpoint is currently active
$endpointsExpanded = str_starts_with($currentPath, 'endpoints/');
?>

<?php if (!empty($categories)): ?>
    <div class="nav-category<?php echo $endpointsExpanded ? ' expanded' : ''; ?>" data-path="endpoints">
        <button class="nav-category-toggle" aria-expanded="<?php echo $endpointsExpanded ? 'true' : 'false'; ?>">
            <span class="toggle-icon"><?php echo $endpointsExpanded ? '&#9660;' : '&#9654;'; ?></span>
            <span class="category-name">Endpoints</span>
        </button>
        <div class="nav-category-content"<?php echo $endpointsExpanded ? '' : ' style="display: none;"'; ?>>
<?php
foreach ($categories as $category) {
    renderCategory($category, $engine, $baseUrl, $currentPath, 3);
}
?>
        </div>
    </div>
<?php endif; ?>
</nav>
