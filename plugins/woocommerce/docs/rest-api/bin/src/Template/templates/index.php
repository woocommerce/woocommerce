<article class="home-page">
    <header class="home-header">
        <h1>WooCommerce REST API Documentation</h1>
        <p class="lead">The WooCommerce REST API provides a way to interact with your WooCommerce store programmatically.</p>
    </header>

    <section class="api-overview">
        <h2>API Versions</h2>
        <p>The following API versions are documented:</p>
        <ul class="version-list">
            <?php foreach ($versions as $version => $count): ?>
            <li>
                <strong><?php $engine->e($version); ?></strong>
                <span class="endpoint-count"><?php $engine->e((string)$count); ?> endpoints</span>
            </li>
            <?php endforeach; ?>
        </ul>
    </section>

    <section class="getting-started">
        <h2>Getting Started</h2>

        <h3>Authentication</h3>
        <p>The WooCommerce REST API uses API keys for authentication. You can generate API keys from your WordPress admin panel under WooCommerce &gt; Settings &gt; Advanced &gt; REST API.</p>

        <h3>Base URL</h3>
        <p>All API requests should be made to:</p>
        <pre><code>https://your-store.com/wp-json/wc/v3/</code></pre>

        <h3>Example Request</h3>
        <pre><code class="language-bash">curl -X GET https://your-store.com/wp-json/wc/v3/products \
  -u consumer_key:consumer_secret</code></pre>
    </section>

    <section class="categories-overview">
        <h2>API Categories</h2>
        <div class="category-grid">
            <?php foreach ($topCategories as $category): ?>
            <div class="category-card">
                <h3><?php $engine->e($category->name); ?></h3>
                <p class="endpoint-count"><?php $engine->e((string)$category->getTotalEndpointCount()); ?> endpoints</p>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
</article>
