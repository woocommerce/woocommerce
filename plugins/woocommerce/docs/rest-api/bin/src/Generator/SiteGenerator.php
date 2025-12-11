<?php

declare(strict_types=1);

namespace WooCommerce\RestApiDocs\Generator;

use RuntimeException;
use WooCommerce\RestApiDocs\Model\Category;
use WooCommerce\RestApiDocs\Model\Endpoint;
use WooCommerce\RestApiDocs\Model\EndpointDescriptor;
use WooCommerce\RestApiDocs\Model\StaticPage;
use WooCommerce\RestApiDocs\Parser\StaticPageParser;
use WooCommerce\RestApiDocs\Template\TemplateEngine;
use WooCommerce\RestApiDocs\Util\MarkdownParser;
use WooCommerce\RestApiDocs\Util\RouteFormatter;

/**
 * Generates the static documentation site.
 */
final class SiteGenerator
{
    private readonly StaticPageParser $staticPageParser;

    /**
     * @param TemplateEngine $templateEngine The template engine
     * @param string $outputDir The output directory for generated files
     * @param string $assetsDir The source assets directory
     */
    public function __construct(
        private readonly TemplateEngine $templateEngine,
        private readonly string $outputDir,
        private readonly string $assetsDir,
    ) {
        $this->staticPageParser = new StaticPageParser($assetsDir . '/pages');
    }

    /**
     * Generate the complete documentation site.
     *
     * @param array<EndpointDescriptor> $descriptors All endpoint descriptors (used for sidebar)
     * @param array<Endpoint> $endpoints All endpoints from schema
     * @param bool $excludeIncomplete Skip endpoints with incomplete schemas
     * @param bool $incremental Preserve existing content, only add/update specified pages
     * @param array<EndpointDescriptor>|null $filteredDescriptors Subset of descriptors to generate pages for (null = all)
     * @return array{pages: int, errors: array<string>} Generation statistics
     */
    public function generate(
        array $descriptors,
        array $endpoints,
        bool $excludeIncomplete = false,
        bool $incremental = false,
        ?array $filteredDescriptors = null
    ): array {
        $errors = [];
        $pagesGenerated = 0;

        // Load static pages
        $staticPages = $this->staticPageParser->loadAll();

        // Build endpoint lookup map
        $endpointMap = [];
        foreach ($endpoints as $endpoint) {
            $endpointMap[$endpoint->getIdentifier()] = $endpoint;
        }

        // Build category tree from ALL descriptors (for complete sidebar)
        $categories = $this->buildCategoryTree($descriptors);

        // Prepare output directory (creates structure but doesn't clear existing files)
        $this->prepareOutputDirectory();

        // Copy assets (always, in case they've changed)
        $this->copyAssets();

        // Generate static pages (including index)
        foreach ($staticPages as $page) {
            try {
                $this->generateStaticPage($page, $categories, $staticPages);
                $pagesGenerated++;
            } catch (\Exception $e) {
                $errors[] = "Failed to generate static page {$page->slug}: " . $e->getMessage();
            }
        }

        // Determine which descriptors to generate pages for
        $descriptorsToGenerate = $filteredDescriptors ?? $descriptors;

        // Generate endpoint pages
        foreach ($descriptorsToGenerate as $descriptor) {
            if ($descriptor->ignore) {
                continue;
            }

            try {
                // Get the first matching endpoint from schema
                $schemaEndpoint = null;
                foreach ($descriptor->getIdentifiers() as $identifier) {
                    if (isset($endpointMap[$identifier])) {
                        $schemaEndpoint = $endpointMap[$identifier];
                        break;
                    }
                }

                if ($excludeIncomplete && $schemaEndpoint === null) {
                    continue;
                }

                $this->generateEndpointPage($descriptor, $schemaEndpoint, $categories, $staticPages);
                $pagesGenerated++;
            } catch (\Exception $e) {
                $errors[] = "Failed to generate page for {$descriptor->route}: " . $e->getMessage();
            }
        }

        return [
            'pages' => $pagesGenerated,
            'errors' => $errors,
        ];
    }

    /**
     * Build category tree from descriptors.
     *
     * @param array<EndpointDescriptor> $descriptors The descriptors
     * @return array<Category> Root categories
     */
    private function buildCategoryTree(array $descriptors): array
    {
        /** @var array<string, Category> $roots */
        $roots = [];

        foreach ($descriptors as $descriptor) {
            if ($descriptor->ignore) {
                continue;
            }

            $parts = $descriptor->getCategoryParts();
            if (count($parts) === 0) {
                continue;
            }

            // Navigate/create category path
            $currentPath = '';
            $parent = null;

            foreach ($parts as $i => $part) {
                $currentPath = $currentPath === '' ? $part : $currentPath . '/' . $part;

                if ($parent === null) {
                    // Root level
                    if (!isset($roots[$part])) {
                        $roots[$part] = new Category($part, $currentPath, null);
                    }

                    if ($i < count($parts) - 1) {
                        $parent = $roots[$part];
                    } else {
                        $roots[$part]->addEndpoint($descriptor);
                    }
                } else {
                    // Child level
                    if (!$parent->hasChild($part)) {
                        $parent->addChild(new Category($part, $currentPath, $parent));
                    }

                    $current = $parent->getChild($part);

                    if ($i < count($parts) - 1) {
                        $parent = $current;
                    } else {
                        $current->addEndpoint($descriptor);
                    }
                }
            }
        }

        // Convert to indexed array and sort
        $result = array_values($roots);
        foreach ($result as $category) {
            $category->sortChildren();
        }

        return $result;
    }

    /**
     * Prepare the output directory.
     */
    private function prepareOutputDirectory(): void
    {
        if (!is_dir($this->outputDir)) {
            if (!mkdir($this->outputDir, 0755, true)) {
                throw new RuntimeException("Failed to create output directory: {$this->outputDir}");
            }
        }

        // Create subdirectories
        $subdirs = ['css', 'js', 'images', 'endpoints'];
        foreach ($subdirs as $subdir) {
            $path = $this->outputDir . '/' . $subdir;
            if (!is_dir($path)) {
                mkdir($path, 0755, true);
            }
        }
    }

    /**
     * Copy assets to output directory.
     */
    private function copyAssets(): void
    {
        $this->copyDirectory($this->assetsDir . '/css', $this->outputDir . '/css');
        $this->copyDirectory($this->assetsDir . '/js', $this->outputDir . '/js');
        $this->copyDirectory($this->assetsDir . '/images', $this->outputDir . '/images');
    }

    /**
     * Recursively copy a directory.
     *
     * @param string $src Source directory
     * @param string $dst Destination directory
     */
    private function copyDirectory(string $src, string $dst): void
    {
        if (!is_dir($src)) {
            return;
        }

        if (!is_dir($dst)) {
            mkdir($dst, 0755, true);
        }

        $iterator = new \DirectoryIterator($src);
        foreach ($iterator as $file) {
            if ($file->isDot()) {
                continue;
            }

            $srcPath = $file->getPathname();
            $dstPath = $dst . '/' . $file->getFilename();

            if ($file->isDir()) {
                $this->copyDirectory($srcPath, $dstPath);
            } else {
                copy($srcPath, $dstPath);
            }
        }
    }

    /**
     * Generate a static page from markdown.
     *
     * @param StaticPage $page The static page to generate
     * @param array<Category> $categories Root categories for navigation
     * @param array<StaticPage> $staticPages All static pages for navigation
     */
    private function generateStaticPage(StaticPage $page, array $categories, array $staticPages): void
    {
        // Convert markdown to HTML
        $contentHtml = MarkdownParser::toHtml($page->content);

        // Wrap in article tag with appropriate class
        $wrappedContent = '<article class="static-page">' . "\n";
        $wrappedContent .= $contentHtml . "\n";

        // Add timestamp footer
        $timestamp = gmdate('Y-m-d\TH:i:s\Z');
        $wrappedContent .= '<footer class="site-footer">' . "\n";
        $wrappedContent .= '    <p class="last-updated">Last updated: ' . $timestamp . '</p>' . "\n";
        $wrappedContent .= '</footer>' . "\n";
        $wrappedContent .= '</article>';

        $html = $this->templateEngine->render('layout', [
            'title' => $page->title,
            'content' => $wrappedContent,
            'categories' => $categories,
            'staticPages' => $staticPages,
            'baseUrl' => './',
            'currentPath' => $page->isIndex ? '' : $page->slug . '.html',
        ]);

        file_put_contents($this->outputDir . '/' . $page->getOutputFilename(), $html);
    }

    /**
     * Generate an endpoint documentation page.
     *
     * @param EndpointDescriptor $descriptor The endpoint descriptor
     * @param Endpoint|null $schemaEndpoint The matching schema endpoint
     * @param array<Category> $categories Root categories for navigation
     * @param array<StaticPage> $staticPages All static pages for navigation
     */
    private function generateEndpointPage(
        EndpointDescriptor $descriptor,
        ?Endpoint $schemaEndpoint,
        array $categories,
        array $staticPages
    ): void {
        // Build output path
        $categorySlug = str_replace('/', '-', strtolower($descriptor->category));
        $verbSlug = strtolower(implode('-', $descriptor->verbs));
        $routeSlug = $this->slugifyRoute($descriptor->route);
        $filename = $verbSlug . '-' . $routeSlug . '.html';

        $endpointDir = $this->outputDir . '/endpoints/' . $categorySlug;
        if (!is_dir($endpointDir)) {
            mkdir($endpointDir, 0755, true);
        }

        // Calculate relative base URL (always 2 levels: endpoints/{category}/)
        $baseUrl = '../../';

        // Get parameters from schema
        $allParameters = $schemaEndpoint?->args ?? [];
        $routeParamPatterns = RouteFormatter::extractParametersWithPatterns($descriptor->route);
        $pathParamNames = array_keys($routeParamPatterns);
        $responseSchema = $schemaEndpoint?->schema ?? null;

        // Separate route parameters and query/body parameters
        $routeParams = [];
        $queryParams = [];

        foreach ($allParameters as $name => $param) {
            if (in_array($name, $pathParamNames)) {
                $routeParams[$name] = $param;
            } else {
                $queryParams[$name] = $param;
            }
        }

        // For route parameters not in schema, we'll still show them with no info
        // This is handled in the template

        // Generate code examples for all languages
        $examples = $this->generateCodeExamples($descriptor, $schemaEndpoint, $queryParams);

        // Render endpoint content
        $content = $this->templateEngine->render('endpoint', [
            'descriptor' => $descriptor,
            'displayRoute' => RouteFormatter::formatForDisplay($descriptor->route),
            'parameters' => $queryParams,
            'pathParams' => $pathParamNames,
            'routeParamsSchema' => $routeParams,
            'routeParamPatterns' => $routeParamPatterns,
            'responseSchema' => $responseSchema,
            'examples' => $examples,
        ]);

        // Render full page
        $html = $this->templateEngine->render('layout', [
            'title' => $descriptor->name,
            'content' => $content,
            'categories' => $categories,
            'staticPages' => $staticPages,
            'baseUrl' => $baseUrl,
            'currentPath' => 'endpoints/' . $categorySlug . '/' . $filename,
        ]);

        file_put_contents($endpointDir . '/' . $filename, $html);
    }

    /**
     * Generate code examples for all supported languages.
     *
     * @param EndpointDescriptor $descriptor The endpoint descriptor
     * @param Endpoint|null $schemaEndpoint The schema endpoint
     * @param array $bodyParams Parameters for the request body (non-route params)
     * @return array Array of examples keyed by language
     */
    private function generateCodeExamples(EndpointDescriptor $descriptor, ?Endpoint $schemaEndpoint, array $bodyParams = []): array
    {
        $verb = $descriptor->verbs[0];
        $route = $this->formatRouteWithSampleValues($descriptor->route);
        $requiresAuth = $descriptor->requiresAuth();

        // Generate sample body for POST/PUT/PATCH
        $sampleBody = null;
        $sampleBodyJson = null;
        $verbUpper = strtoupper($verb);
        if (in_array($verbUpper, ['POST', 'PUT', 'PATCH']) && !empty($bodyParams)) {
            $sampleBody = $this->generateSampleBody($bodyParams);
            $sampleBodyJson = json_encode($sampleBody, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        }

        return [
            'curl' => $this->generateCurlExample($verb, $route, $requiresAuth, $sampleBodyJson),
            'nodejs' => $this->generateNodeJsExample($verb, $route, $requiresAuth, $sampleBodyJson),
            'php' => $this->generatePhpExample($verb, $route, $requiresAuth, $sampleBody),
            'python' => $this->generatePythonExample($verb, $route, $requiresAuth, $sampleBodyJson),
            'ruby' => $this->generateRubyExample($verb, $route, $requiresAuth, $sampleBodyJson),
            'response' => $this->generateSampleResponse($schemaEndpoint),
        ];
    }

    /**
     * Format a route by replacing parameter placeholders with sample values.
     *
     * @param string $route The route pattern with regex groups
     * @return string The route with sample values
     */
    private function formatRouteWithSampleValues(string $route): string
    {
        $routeParamPatterns = RouteFormatter::extractParametersWithPatterns($route);

        // Replace each parameter pattern with a sample value
        foreach ($routeParamPatterns as $name => $info) {
            $pattern = $info['pattern'];
            $sampleValue = $this->generateSampleValueFromRegex($pattern, $name);

            // Replace the regex group with the sample value
            $route = preg_replace(
                '/\(\?P<' . preg_quote($name, '/') . '>[^)]+\)/',
                $sampleValue,
                $route
            );
        }

        return $route;
    }

    /**
     * Generate a sample value that matches a regex pattern.
     *
     * @param string $pattern The regex pattern
     * @param string $paramName The parameter name (for context)
     * @return string A sample value
     */
    private function generateSampleValueFromRegex(string $pattern, string $paramName): string
    {
        // Integer patterns (check for \d or digit character classes)
        if ($this->isIntegerPattern($pattern)) {
            // Use context-aware values for common ID parameters
            return match (true) {
                str_contains($paramName, 'id') => '123',
                str_contains($paramName, 'parent') => '456',
                str_contains($paramName, 'index') => '0',
                default => '123',
            };
        }

        // Fixed-length patterns like {3} for currency codes
        if (preg_match('/\{(\d+)\}$/', $pattern, $matches)) {
            $length = (int) $matches[1];

            // Common fixed-length values
            if ($length === 3 && str_contains($paramName, 'currency')) {
                return 'USD';
            }
            if ($length === 32) {
                // 32-char key (like cart item keys)
                return 'abc123def456abc123def456abc12345';
            }

            // Generate a generic alphanumeric string of the right length
            return substr(str_repeat('abc123', (int) ceil($length / 6)), 0, $length);
        }

        // Slug-like patterns
        if ($this->isSlugPattern($pattern)) {
            return match (true) {
                str_contains($paramName, 'slug') => 'sample-slug',
                str_contains($paramName, 'code') => 'sample-code',
                str_contains($paramName, 'key') => 'sample-key',
                str_contains($paramName, 'group') => 'sample-group',
                str_contains($paramName, 'location') => 'US',
                str_contains($paramName, 'zone') => 'zone-1',
                str_contains($paramName, 'namespace') => 'wc-v3',
                str_contains($paramName, 'step') => 'step-1',
                str_contains($paramName, 'type') => 'default',
                str_contains($paramName, 'identifier') => 'sample-id',
                default => 'sample-value',
            };
        }

        // Word with spaces pattern
        if ($this->isWordPattern($pattern)) {
            return match (true) {
                str_contains($paramName, 'slug') => 'sample-slug',
                default => 'sample-value',
            };
        }

        // Lowercase letters only
        if (preg_match('/^\[a-z\]\+$/', $pattern)) {
            return match (true) {
                str_contains($paramName, 'type') => 'default',
                default => 'value',
            };
        }

        // Default fallback - try to generate something reasonable
        return 'sample-value';
    }

    /**
     * Check if a pattern matches integers (digits only).
     */
    private function isIntegerPattern(string $pattern): bool
    {
        // Match patterns like [\d]+, \d+, [0-9]+, [\\d]+
        return (bool) preg_match('/^(\[\\\\?d\]|\[0-9\]|\\\\?d)\+$/', $pattern);
    }

    /**
     * Check if a pattern matches slug-like strings.
     */
    private function isSlugPattern(string $pattern): bool
    {
        // Match patterns like [\w-]+, [a-z0-9_-]+, [a-zA-Z0-9_-]+
        $slugPatterns = [
            '/^\[\\\\?w-?\]\+$/',                    // [\w-]+ or [\w]+
            '/^\[\\\\?w\\\\?-\]\+$/',                // [\w\-]+
            '/^\[a-z0-9_-?\]\+$/',                   // [a-z0-9_-]+ or [a-z0-9_]+
            '/^\[a-z0-9_\\\\?-\]\+$/',               // [a-z0-9_\-]+
            '/^\[a-zA-Z0-9_-?\]\+$/',                // [a-zA-Z0-9_-]+
            '/^\[a-zA-Z0-9_\\\\?-\]\+$/',            // [a-zA-Z0-9_\-]+
        ];

        foreach ($slugPatterns as $regex) {
            if (preg_match($regex, $pattern)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if a pattern matches word-like strings with spaces.
     */
    private function isWordPattern(string $pattern): bool
    {
        // Match patterns like \w[\w\s\-]*, [\S]+
        return (bool) preg_match('/^(\\\\?w\[\\\\?w\\\\?s\\\\?-\]\*|\[\\\\?S\]\+)$/', $pattern);
    }

    /**
     * Generate a curl example for an endpoint.
     *
     * @param string $verb HTTP verb
     * @param string $route The route
     * @param bool $requiresAuth Whether auth is required
     * @param string|null $bodyJson JSON body string
     * @return string The curl command
     */
    private function generateCurlExample(string $verb, string $route, bool $requiresAuth, ?string $bodyJson): string
    {
        $verbUpper = strtoupper($verb);

        $lines = [];
        $lines[] = "curl -X {$verbUpper} \\";
        $lines[] = "  'https://example.com/wp-json{$route}' \\";

        if ($requiresAuth) {
            $lines[] = "  -u 'consumer_key:consumer_secret' \\";
        }

        $lines[] = "  -H 'Content-Type: application/json'";

        if ($bodyJson !== null) {
            $lines[count($lines) - 1] .= ' \\';
            // Escape single quotes in JSON for shell
            $escapedJson = str_replace("'", "'\\''", $bodyJson);
            $lines[] = "  -d '{$escapedJson}'";
        }

        return implode("\n", $lines);
    }

    /**
     * Generate a Node.js example for an endpoint.
     *
     * @param string $verb HTTP verb
     * @param string $route The route
     * @param bool $requiresAuth Whether auth is required
     * @param string|null $bodyJson JSON body string
     * @return string The Node.js code
     */
    private function generateNodeJsExample(string $verb, string $route, bool $requiresAuth, ?string $bodyJson): string
    {
        $verbUpper = strtoupper($verb);

        $lines = [];
        $lines[] = "const https = require('https');";
        $lines[] = "";

        if ($bodyJson !== null) {
            $lines[] = "const data = JSON.stringify({$bodyJson});";
            $lines[] = "";
        }

        $lines[] = "const options = {";
        $lines[] = "  hostname: 'example.com',";
        $lines[] = "  path: '/wp-json{$route}',";
        $lines[] = "  method: '{$verbUpper}',";
        $lines[] = "  headers: {";
        $lines[] = "    'Content-Type': 'application/json',";

        if ($requiresAuth) {
            $lines[] = "    'Authorization': 'Basic ' + Buffer.from('consumer_key:consumer_secret').toString('base64'),";
        }

        if ($bodyJson !== null) {
            $lines[] = "    'Content-Length': Buffer.byteLength(data),";
        }

        $lines[] = "  },";
        $lines[] = "};";
        $lines[] = "";
        $lines[] = "const req = https.request(options, (res) => {";
        $lines[] = "  let body = '';";
        $lines[] = "  res.on('data', (chunk) => body += chunk);";
        $lines[] = "  res.on('end', () => console.log(JSON.parse(body)));";
        $lines[] = "});";
        $lines[] = "";
        $lines[] = "req.on('error', (e) => console.error(e));";

        if ($bodyJson !== null) {
            $lines[] = "req.write(data);";
        }

        $lines[] = "req.end();";

        return implode("\n", $lines);
    }

    /**
     * Generate a PHP example for an endpoint.
     *
     * @param string $verb HTTP verb
     * @param string $route The route
     * @param bool $requiresAuth Whether auth is required
     * @param array|null $body Body data array
     * @return string The PHP code
     */
    private function generatePhpExample(string $verb, string $route, bool $requiresAuth, ?array $body): string
    {
        $verbUpper = strtoupper($verb);
        $url = "https://example.com/wp-json{$route}";

        $lines = [];
        $lines[] = "<?php";
        $lines[] = "";
        $lines[] = "\$url = '{$url}';";

        if ($body !== null) {
            $lines[] = "\$data = " . $this->arrayToPhpCode($body, 0) . ";";
        }

        $lines[] = "";
        $lines[] = "\$options = [";
        $lines[] = "    'http' => [";
        $lines[] = "        'method' => '{$verbUpper}',";
        $lines[] = "        'header' => [";
        $lines[] = "            'Content-Type: application/json',";

        if ($requiresAuth) {
            $lines[] = "            'Authorization: Basic ' . base64_encode('consumer_key:consumer_secret'),";
        }

        $lines[] = "        ],";

        if ($body !== null) {
            $lines[] = "        'content' => json_encode(\$data),";
        }

        $lines[] = "    ],";
        $lines[] = "];";
        $lines[] = "";
        $lines[] = "\$context = stream_context_create(\$options);";
        $lines[] = "\$response = file_get_contents(\$url, false, \$context);";
        $lines[] = "";
        $lines[] = "if (\$response === false) {";
        $lines[] = "    die('Error fetching data');";
        $lines[] = "}";
        $lines[] = "";
        $lines[] = "\$result = json_decode(\$response, true);";
        $lines[] = "print_r(\$result);";

        return implode("\n", $lines);
    }

    /**
     * Convert a PHP array to PHP code representation.
     *
     * @param mixed $value The value to convert
     * @param int $indent Current indentation level
     * @return string PHP code
     */
    private function arrayToPhpCode(mixed $value, int $indent): string
    {
        $indentStr = str_repeat('    ', $indent);
        $nextIndent = str_repeat('    ', $indent + 1);

        if (is_array($value)) {
            if (empty($value)) {
                return '[]';
            }

            // Check if it's a sequential array
            $isSequential = array_keys($value) === range(0, count($value) - 1);

            $lines = ['['];
            foreach ($value as $key => $val) {
                $valCode = $this->arrayToPhpCode($val, $indent + 1);
                if ($isSequential) {
                    $lines[] = "{$nextIndent}{$valCode},";
                } else {
                    $keyCode = var_export($key, true);
                    $lines[] = "{$nextIndent}{$keyCode} => {$valCode},";
                }
            }
            $lines[] = "{$indentStr}]";
            return implode("\n", $lines);
        }

        if (is_string($value)) {
            return var_export($value, true);
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_null($value)) {
            return 'null';
        }

        return (string) $value;
    }

    /**
     * Generate a Python example for an endpoint.
     *
     * @param string $verb HTTP verb
     * @param string $route The route
     * @param bool $requiresAuth Whether auth is required
     * @param string|null $bodyJson JSON body string
     * @return string The Python code
     */
    private function generatePythonExample(string $verb, string $route, bool $requiresAuth, ?string $bodyJson): string
    {
        $verbUpper = strtoupper($verb);
        $url = "https://example.com/wp-json{$route}";

        $lines = [];
        $lines[] = "import urllib.request";
        $lines[] = "import json";
        $lines[] = "import base64";
        $lines[] = "";
        $lines[] = "url = '{$url}'";

        if ($bodyJson !== null) {
            // Python dict syntax is same as JSON (true/false/null differ but json.dumps handles it)
            $lines[] = "data = {$bodyJson}";
            $lines[] = "encoded_data = json.dumps(data).encode('utf-8')";
        } else {
            $lines[] = "encoded_data = None";
        }

        $lines[] = "";
        $lines[] = "req = urllib.request.Request(url, data=encoded_data, method='{$verbUpper}')";
        $lines[] = "req.add_header('Content-Type', 'application/json')";

        if ($requiresAuth) {
            $lines[] = "";
            $lines[] = "credentials = base64.b64encode(b'consumer_key:consumer_secret').decode('utf-8')";
            $lines[] = "req.add_header('Authorization', f'Basic {credentials}')";
        }

        $lines[] = "";
        $lines[] = "try:";
        $lines[] = "    with urllib.request.urlopen(req) as response:";
        $lines[] = "        result = json.loads(response.read().decode('utf-8'))";
        $lines[] = "        print(json.dumps(result, indent=2))";
        $lines[] = "except urllib.error.HTTPError as e:";
        $lines[] = "    print(f'Error: {e.code} - {e.reason}')";

        return implode("\n", $lines);
    }

    /**
     * Generate a Ruby example for an endpoint.
     *
     * @param string $verb HTTP verb
     * @param string $route The route
     * @param bool $requiresAuth Whether auth is required
     * @param string|null $bodyJson JSON body string
     * @return string The Ruby code
     */
    private function generateRubyExample(string $verb, string $route, bool $requiresAuth, ?string $bodyJson): string
    {
        $verbUpper = strtoupper($verb);
        $url = "https://example.com/wp-json{$route}";

        $lines = [];
        $lines[] = "require 'net/http'";
        $lines[] = "require 'uri'";
        $lines[] = "require 'json'";
        $lines[] = "require 'base64'";
        $lines[] = "";
        $lines[] = "uri = URI.parse('{$url}')";
        $lines[] = "";
        $lines[] = "http = Net::HTTP.new(uri.host, uri.port)";
        $lines[] = "http.use_ssl = true";
        $lines[] = "";

        // Map HTTP verb to Ruby class
        $rubyClass = match ($verbUpper) {
            'GET' => 'Get',
            'POST' => 'Post',
            'PUT' => 'Put',
            'PATCH' => 'Patch',
            'DELETE' => 'Delete',
            default => 'Get',
        };

        $lines[] = "request = Net::HTTP::{$rubyClass}.new(uri.request_uri)";
        $lines[] = "request['Content-Type'] = 'application/json'";

        if ($requiresAuth) {
            $lines[] = "request['Authorization'] = 'Basic ' + Base64.strict_encode64('consumer_key:consumer_secret')";
        }

        if ($bodyJson !== null) {
            $lines[] = "";
            $lines[] = "data = {$bodyJson}";
            $lines[] = 'request.body = data.to_json';
        }

        $lines[] = "";
        $lines[] = "response = http.request(request)";
        $lines[] = "";
        $lines[] = "puts JSON.pretty_generate(JSON.parse(response.body))";

        return implode("\n", $lines);
    }

    /**
     * Generate a sample response from the schema.
     *
     * @param Endpoint|null $schemaEndpoint The schema endpoint
     * @return string JSON sample response
     */
    private function generateSampleResponse(?Endpoint $schemaEndpoint): ?string
    {
        if ($schemaEndpoint === null || $schemaEndpoint->schema === null) {
            return null;
        }

        $schema = $schemaEndpoint->schema;
        $sample = $this->generateSampleFromSchema($schema);

        return json_encode($sample, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Generate sample data from a schema definition.
     *
     * @param array $schema The schema definition
     * @return mixed Sample data
     */
    private function generateSampleFromSchema(array $schema): mixed
    {
        $type = $schema['type'] ?? 'object';

        // Handle array types (nullable)
        if (is_array($type)) {
            $type = array_filter($type, fn($t) => $t !== 'null');
            $type = reset($type) ?: 'object';
        }

        if ($type === 'array' && isset($schema['items'])) {
            // Return array with one sample item
            return [$this->generateSampleFromSchema($schema['items'])];
        }

        if ($type !== 'object' || !isset($schema['properties'])) {
            // For primitive types at root level, generate a sample value
            return $this->generateSampleValue($schema, 'response');
        }

        $sample = [];
        foreach ($schema['properties'] as $propName => $propSchema) {
            $sample[$propName] = $this->generateSampleValueForResponse($propSchema, $propName);
        }

        return $sample;
    }

    /**
     * Generate a sample value for response schema (includes read-only fields).
     *
     * @param array $schema The property schema
     * @param string $name The property name
     * @return mixed Sample value
     */
    private function generateSampleValueForResponse(array $schema, string $name): mixed
    {
        $type = $schema['type'] ?? 'string';

        // Handle array of types
        if (is_array($type)) {
            $type = array_filter($type, fn($t) => $t !== 'null');
            $type = array_values($type);
            if (count($type) > 1) {
                $primitiveOrder = ['string', 'integer', 'number', 'boolean', 'object', 'array'];
                foreach ($primitiveOrder as $preferred) {
                    if (in_array($preferred, $type)) {
                        $type = $preferred;
                        break;
                    }
                }
                if (is_array($type)) {
                    $type = reset($type) ?: 'string';
                }
            } else {
                $type = reset($type) ?: 'string';
            }
        }

        // Handle enum
        if (isset($schema['enum']) && is_array($schema['enum']) && !empty($schema['enum'])) {
            return $schema['enum'][0];
        }

        return match ($type) {
            'integer' => $this->generateNumericSample($schema, $name),
            'number' => (float) $this->generateNumericSample($schema, $name),
            'boolean' => true,
            'array' => $this->generateArraySampleForResponse($schema, $name),
            'object' => $this->generateObjectSampleForResponse($schema, $name),
            default => $this->generateStringSample($schema, $name),
        };
    }

    /**
     * Generate sample array for response (includes read-only).
     */
    private function generateArraySampleForResponse(array $param, string $name): array
    {
        if (!isset($param['items'])) {
            return [];
        }

        return [$this->generateSampleValueForResponse($param['items'], $name)];
    }

    /**
     * Generate sample object for response (includes read-only).
     */
    private function generateObjectSampleForResponse(array $param, string $name): array|object
    {
        if (!isset($param['properties'])) {
            return new \stdClass();
        }

        $obj = [];
        foreach ($param['properties'] as $propName => $propSchema) {
            $obj[$propName] = $this->generateSampleValueForResponse($propSchema, $propName);
        }

        return empty($obj) ? new \stdClass() : $obj;
    }

    /**
     * Generate sample body data from parameter schema.
     *
     * @param array $params The parameters schema
     * @return array Sample body data
     */
    private function generateSampleBody(array $params): array
    {
        $body = [];

        foreach ($params as $name => $param) {
            // Skip read-only parameters
            if ($param['readonly'] ?? false) {
                continue;
            }

            $body[$name] = $this->generateSampleValue($param, $name);
        }

        return $body;
    }

    /**
     * Generate a sample value for a parameter based on its type.
     *
     * @param array $param The parameter schema
     * @param string $name The parameter name (used for context hints)
     * @param int|null $itemIndex Optional index for generating unique array items
     * @return mixed The sample value
     */
    private function generateSampleValue(array $param, string $name, ?int $itemIndex = null): mixed
    {
        $type = $param['type'] ?? 'string';

        // Handle multiple types - prefer primitive types over complex ones
        if (is_array($type)) {
            // Remove null from the type list
            $type = array_filter($type, fn($t) => $t !== 'null');
            $type = array_values($type);

            if (count($type) > 1) {
                // Prefer primitive types: string > integer > number > boolean > object > array
                $primitiveOrder = ['string', 'integer', 'number', 'boolean', 'object', 'array'];
                foreach ($primitiveOrder as $preferred) {
                    if (in_array($preferred, $type)) {
                        $type = $preferred;
                        break;
                    }
                }
                // If no match found, use the first type
                if (is_array($type)) {
                    $type = reset($type) ?: 'string';
                }
            } else {
                $type = reset($type) ?: 'string';
            }
        }

        // If there's an enum, use the appropriate enum value based on index
        if (isset($param['enum']) && is_array($param['enum']) && !empty($param['enum'])) {
            if ($itemIndex !== null && count($param['enum']) > 1) {
                // Use different enum values for different array items
                $enumIndex = ($itemIndex - 1) % count($param['enum']);
                return $param['enum'][$enumIndex];
            }
            return $param['enum'][0];
        }

        // Handle format-specific types
        $format = $param['format'] ?? null;
        if ($format !== null) {
            $suffix = $itemIndex !== null ? $itemIndex : '';
            return match ($format) {
                'date-time' => '2024-01-15T10:30:0' . ($itemIndex ?? 0),
                'date' => '2024-01-' . str_pad((string)($itemIndex ?? 15), 2, '0', STR_PAD_LEFT),
                'time' => '10:30:0' . ($itemIndex ?? 0),
                'email' => 'user' . $suffix . '@example.com',
                'uri', 'url' => 'https://example.com/' . ($itemIndex ?? ''),
                'uuid' => '550e8400-e29b-41d4-a716-44665544000' . ($itemIndex ?? 0),
                'ip' => '192.168.1.' . ($itemIndex ?? 1),
                'ipv4' => '192.168.1.' . ($itemIndex ?? 1),
                'ipv6' => '::' . ($itemIndex ?? 1),
                default => 'value' . $suffix,
            };
        }

        return match ($type) {
            'integer', 'number' => $this->generateNumericSample($param, $name, $itemIndex),
            'boolean' => true,
            'array' => $this->generateArraySample($param, $name),
            'object' => $this->generateObjectSample($param, $name),
            default => $this->generateStringSample($param, $name, $itemIndex),
        };
    }

    /**
     * Generate a sample numeric value.
     */
    private function generateNumericSample(array $param, string $name, ?int $itemIndex = null): int|float
    {
        $min = $param['minimum'] ?? null;
        $max = $param['maximum'] ?? null;
        $exclusiveMin = $param['exclusiveMinimum'] ?? false;
        $exclusiveMax = $param['exclusiveMaximum'] ?? false;

        // Determine the default value based on context
        $default = 12345;
        if (str_contains($name, 'quantity') || str_contains($name, 'count')) {
            $default = 1;
        } elseif (str_contains($name, 'price') || str_contains($name, 'amount') || str_contains($name, 'total')) {
            $default = 19.99;
        } elseif (str_contains($name, 'percent') || str_contains($name, 'rate')) {
            $default = 10;
        }

        // Add item index for unique values in arrays
        $indexOffset = $itemIndex !== null ? ($itemIndex - 1) : 0;

        // Apply constraints
        if ($min !== null && $max !== null) {
            // Use a value in the middle of the range, offset by index
            $actualMin = $exclusiveMin ? $min + 1 : $min;
            $actualMax = $exclusiveMax ? $max - 1 : $max;
            $base = (int) (($actualMin + $actualMax) / 2);
            return min($actualMax, $base + $indexOffset);
        } elseif ($min !== null) {
            $actualMin = $exclusiveMin ? $min + 1 : $min;
            return max($default, (int) $actualMin) + $indexOffset;
        } elseif ($max !== null) {
            $actualMax = $exclusiveMax ? $max - 1 : $max;
            return min($default + $indexOffset, (int) $actualMax);
        }

        return $default + $indexOffset;
    }

    /**
     * Generate a sample string value.
     */
    private function generateStringSample(array $param, string $name, ?int $itemIndex = null): string
    {
        $minLength = $param['minLength'] ?? null;
        $maxLength = $param['maxLength'] ?? null;
        $suffix = $itemIndex !== null ? $itemIndex : '';

        // Special cases for common field names
        if ($name === 'key') {
            return $this->adjustStringLength('sample_key' . $suffix, $minLength, $maxLength);
        }
        if ($name === 'value') {
            return $this->adjustStringLength('sample_value' . $suffix, $minLength, $maxLength);
        }

        // Context-based defaults
        $value = 'value' . $suffix;
        if (str_contains($name, 'email')) {
            $value = 'user' . $suffix . '@example.com';
        } elseif (str_contains($name, 'url') || str_contains($name, 'link') || str_contains($name, 'src')) {
            $value = 'https://example.com/image' . $suffix . '.jpg';
        } elseif (str_contains($name, 'phone')) {
            $value = '+1-555-123-456' . ($itemIndex ?? 7);
        } elseif (str_contains($name, 'name')) {
            $value = 'Sample Name' . ($suffix ? ' ' . $suffix : '');
        } elseif (str_contains($name, 'description') || str_contains($name, 'note')) {
            $value = 'Sample description text' . ($suffix ? ' ' . $suffix : '');
        } elseif (str_contains($name, 'code') || str_contains($name, 'sku')) {
            $value = 'ABC' . ($itemIndex !== null ? (100 + $itemIndex) : 123);
        } elseif (str_contains($name, 'slug')) {
            $value = 'sample-slug' . ($suffix ? '-' . $suffix : '');
        } elseif (str_contains($name, 'status')) {
            $value = 'publish';
        } elseif (str_contains($name, 'country')) {
            $value = 'US';
        } elseif (str_contains($name, 'state')) {
            $value = 'CA';
        } elseif (str_contains($name, 'postcode') || str_contains($name, 'zip')) {
            $value = '9021' . ($itemIndex ?? 0);
        } elseif (str_contains($name, 'city')) {
            $value = 'Los Angeles';
        } elseif (str_contains($name, 'address')) {
            $value = ($itemIndex !== null ? (100 + $itemIndex) : 123) . ' Main Street';
        }

        return $this->adjustStringLength($value, $minLength, $maxLength);
    }

    /**
     * Adjust string length to fit within constraints.
     */
    private function adjustStringLength(string $value, ?int $minLength, ?int $maxLength): string
    {
        $currentLength = strlen($value);

        // If too short, pad with repeating characters
        if ($minLength !== null && $currentLength < $minLength) {
            $value = str_pad($value, $minLength, '_');
        }

        // If too long, truncate
        if ($maxLength !== null && strlen($value) > $maxLength) {
            $value = substr($value, 0, $maxLength);
        }

        return $value;
    }

    /**
     * Generate a sample array value.
     */
    private function generateArraySample(array $param, string $name): array
    {
        if (!isset($param['items'])) {
            return [];
        }

        $items = $param['items'];
        $minItems = $param['minItems'] ?? null;
        $maxItems = $param['maxItems'] ?? null;
        $uniqueItems = $param['uniqueItems'] ?? false;

        // Determine how many items to generate
        $count = 1; // Default to 1 item
        if ($minItems !== null && $minItems > $count) {
            $count = $minItems;
        }
        if ($maxItems !== null && $count > $maxItems) {
            $count = $maxItems;
        }
        // Cap at a reasonable number for readability
        $count = min($count, 3);

        // Generate sample items
        $result = [];
        for ($i = 0; $i < $count; $i++) {
            // Pass index for unique item generation when uniqueItems is required or multiple items
            $itemIndex = ($uniqueItems || $count > 1) ? ($i + 1) : null;
            $result[] = $this->generateSampleValue($items, $name, $itemIndex);
        }

        return $result;
    }

    /**
     * Generate a sample object value.
     * @return array|object
     */
    private function generateObjectSample(array $param, string $name): array|object
    {
        if (!isset($param['properties'])) {
            // Return stdClass so it serializes as {} instead of []
            return new \stdClass();
        }

        $obj = [];
        foreach ($param['properties'] as $propName => $propSchema) {
            // Skip read-only properties
            if ($propSchema['readonly'] ?? false) {
                continue;
            }
            $obj[$propName] = $this->generateSampleValue($propSchema, $propName);
        }

        // If all properties were read-only, return empty object
        if (empty($obj)) {
            return new \stdClass();
        }

        return $obj;
    }

    /**
     * Convert a route to a URL-safe slug.
     *
     * @param string $route The route pattern
     * @return string The slug
     */
    private function slugifyRoute(string $route): string
    {
        // Remove parameter patterns, keeping param names
        $slug = preg_replace('/\(\?P<([^>]+)>[^)]+\)/', '$1', $route) ?? $route;

        // Replace non-alphanumeric with dashes
        $slug = preg_replace('/[^a-zA-Z0-9]+/', '-', $slug) ?? $slug;

        // Clean up
        $slug = trim($slug, '-');
        $slug = strtolower($slug);

        return $slug;
    }
}
