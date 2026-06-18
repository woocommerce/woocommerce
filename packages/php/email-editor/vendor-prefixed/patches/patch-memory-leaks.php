<?php
/**
 * Post-install patch for memory leak fixes in vendor-prefixed dependencies.
 *
 * Applies three patches:
 * A) DeclarationBlockParser: adds clearCache() static method
 * B) CssInliner: calls DeclarationBlockParser::clearCache() in clearAllCaches()
 * C) CssSelectorConverter: replaces unbounded cache with LRU-capped version
 *
 * Based on upstream fixes:
 * - https://github.com/symfony/symfony/pull/63400
 * - https://github.com/MyIntervals/emogrifier/pull/1567
 *
 * @package WooCommerce\EmailEditor
 */

$base = __DIR__ . '/../packages';

$patches = array();

// --- Patch A: DeclarationBlockParser — add clearCache() method ---
$patches[] = array(
	'file'   => $base . '/Pelago/Emogrifier/Utilities/DeclarationBlockParser.php',
	'marker' => 'public static function clearCache(): void',
	'search' => '    private static $cache = [];

    /**
     * CSS custom properties (variables) have case-sensitive names, so their case must be preserved.',
	'replace' => '    private static $cache = [];

    /**
     * Clears the static declaration block cache.
     *
     * This should be called between processing separate HTML documents to prevent
     * unbounded memory growth in long-running processes.
     */
    public static function clearCache(): void
    {
        self::$cache = [];
    }

    /**
     * CSS custom properties (variables) have case-sensitive names, so their case must be preserved.',
);

// --- Patch B: CssInliner — call DeclarationBlockParser::clearCache() ---
$patches[] = array(
	'file'   => $base . '/Pelago/Emogrifier/CssInliner.php',
	'marker' => 'DeclarationBlockParser::clearCache();',
	'search' => '    private function clearAllCaches(): void
    {
        $this->caches = [
            self::CACHE_KEY_SELECTOR => [],
            self::CACHE_KEY_COMBINED_STYLES => [],
        ];
    }',
	'replace' => '    private function clearAllCaches(): void
    {
        $this->caches = [
            self::CACHE_KEY_SELECTOR => [],
            self::CACHE_KEY_COMBINED_STYLES => [],
        ];
        DeclarationBlockParser::clearCache();
    }',
);

// --- Patch C: CssSelectorConverter — LRU cache ---
$patches[] = array(
	'file'   => $base . '/Symfony/Component/CssSelector/CssSelectorConverter.php',
	'marker' => 'maxCachedItems',
	'search' => '    private $translator;
    private $cache;

    private static $xmlCache = [];
    private static $htmlCache = [];',
	'replace' => '    private $translator;
    private $cache;

    /**
     * Maximum number of cached items per prefix before LRU eviction kicks in.
     *
     * @var int
     */
    public static $maxCachedItems = 200;

    private static $xmlCache = [];
    private static $htmlCache = [];',
);

$patches[] = array(
	'file'   => $base . '/Symfony/Component/CssSelector/CssSelectorConverter.php',
	'marker' => 'array_key_first',
	'search' => '    public function toXPath(string $cssExpr, string $prefix = \'descendant-or-self::\')
    {
        return $this->cache[$prefix][$cssExpr] ?? $this->cache[$prefix][$cssExpr] = $this->translator->cssToXPath($cssExpr, $prefix);
    }',
	'replace' => '    public function toXPath(string $cssExpr, string $prefix = \'descendant-or-self::\')
    {
        if (isset($this->cache[$prefix][$cssExpr])) {
            // Promote to most-recently-used position.
            $value = $this->cache[$prefix][$cssExpr];
            unset($this->cache[$prefix][$cssExpr]);

            return $this->cache[$prefix][$cssExpr] = $value;
        }

        $value = $this->translator->cssToXPath($cssExpr, $prefix);

        if (\count($this->cache[$prefix] ?? []) >= self::$maxCachedItems) {
            // Evict least-recently-used entry.
            unset($this->cache[$prefix][\array_key_first($this->cache[$prefix])]);
        }

        return $this->cache[$prefix][$cssExpr] = $value;
    }',
);

$failed = false;

foreach ( $patches as $patch ) {
	$name = basename( $patch['file'] );

	if ( ! file_exists( $patch['file'] ) ) {
		echo "FAIL: File not found: {$patch['file']}\n";
		$failed = true;
		continue;
	}

	$content = file_get_contents( $patch['file'] );

	if ( strpos( $content, $patch['marker'] ) !== false ) {
		echo "SKIP: {$name} — already patched ({$patch['marker']})\n";
		continue;
	}

	if ( strpos( $content, $patch['search'] ) === false ) {
		echo "FAIL: {$name} — search string not found. File may have changed upstream.\n";
		$failed = true;
		continue;
	}

	$patched = str_replace( $patch['search'], $patch['replace'], $content );
	file_put_contents( $patch['file'], $patched );
	echo "OK:   {$name} — patch applied\n";
}

if ( $failed ) {
	echo "\nSome patches failed. Please check the output above.\n";
	exit( 1 );
}

echo "\nAll patches applied successfully.\n";
