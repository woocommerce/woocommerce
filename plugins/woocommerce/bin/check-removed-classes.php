<?php
/**
 * WooCommerce Removed Classes Checker
 *
 * Detects PHP classes, interfaces, traits and enums that exist in a baseline tree
 * but are missing from the current one. Removing a shipped class causes update-time
 * fatal errors (stale code/classmaps from the previous version can still reference
 * it during an in-place update), so shipped classes should be deprecated and kept
 * as stubs for at least one release before removal.
 *
 * Usage:
 *   php check-removed-classes.php scan <plugin_root> <output.json>
 *   php check-removed-classes.php compare <old.json> <new.json> [<allowlist_file>]
 *
 * 'scan' writes a JSON map of fully qualified type name => file. 'compare' exits 1
 * if any type in <old.json> is missing from <new.json> and not in the optional
 * <allowlist_file> (one name per line, '#' comments allowed).
 *
 * CI (class-removal-check.yml) compares a PR against its base branch. The script
 * also works against arbitrary baselines, e.g. auditing a release against the
 * previous shipped version: scan both checkouts, then compare.
 *
 * @package WooCommerce
 */

// This is a CLI-only script: it writes plain text to stdout and reads local files.
// WordPress's web-oriented escaping, filesystem and file-naming sniffs therefore don't apply here.
// phpcs:disable WordPress.Security.EscapeOutput, WordPress.WP.AlternativeFunctions, WordPress.Files.FileName

/**
 * Scans PHP source trees for type declarations and diffs them between versions.
 */
class WC_Removed_Classes_Checker {

	/**
	 * Directories scanned inside the plugin root, relative to it.
	 *
	 * @var string[]
	 */
	const SCAN_DIRS = array( 'src', 'includes' );

	/**
	 * Path fragments that exclude a file from scanning (bundled/generated/dev code).
	 *
	 * @var string[]
	 */
	const EXCLUDED_PATH_FRAGMENTS = array(
		'/vendor/',
		'/lib/',
		'/tests/',
		'/node_modules/',
		'/DesignTime/',
	);

	/**
	 * Scan a plugin root for PHP type declarations.
	 *
	 * @param string $plugin_root Path to the root of the WooCommerce plugin.
	 * @return array Map of fully qualified type name => file path relative to the plugin root, sorted by name.
	 * @throws \InvalidArgumentException If the plugin root doesn't look like a WooCommerce checkout.
	 */
	public function scan( string $plugin_root ): array {
		$plugin_root = rtrim( $plugin_root, '/' );
		if ( ! is_dir( $plugin_root . '/' . self::SCAN_DIRS[0] ) ) {
			throw new \InvalidArgumentException( "'$plugin_root' does not contain a '" . self::SCAN_DIRS[0] . "' directory; is it a WooCommerce plugin root?" );
		}

		$types = array();
		foreach ( self::SCAN_DIRS as $dir ) {
			$base = $plugin_root . '/' . $dir;
			if ( ! is_dir( $base ) ) {
				continue;
			}

			$iterator = new \RecursiveIteratorIterator(
				new \RecursiveDirectoryIterator( $base, \FilesystemIterator::SKIP_DOTS )
			);
			foreach ( $iterator as $file ) {
				if ( 'php' !== strtolower( $file->getExtension() ) ) {
					continue;
				}
				$path = str_replace( '\\', '/', $file->getPathname() );
				if ( $this->is_excluded( $path ) ) {
					continue;
				}
				$relative_path = ltrim( substr( $path, strlen( $plugin_root ) ), '/' );
				foreach ( $this->extract_declarations( (string) file_get_contents( $file->getPathname() ) ) as $type_name ) {
					$types[ $type_name ] = $relative_path;
				}
			}
		}

		ksort( $types, SORT_STRING );
		return $types;
	}

	/**
	 * Extract the fully qualified names of all named classes, interfaces, traits and enums
	 * declared in a piece of PHP code. Anonymous classes and '::class' references are ignored.
	 *
	 * @param string $code PHP source code.
	 * @return string[] Fully qualified type names.
	 */
	public function extract_declarations( string $code ): array {
		$tokens       = token_get_all( $code );
		$count        = count( $tokens );
		$namespace    = '';
		$declarations = array();
		$type_tokens  = array( T_CLASS, T_INTERFACE, T_TRAIT );
		if ( defined( 'T_ENUM' ) ) {
			// phpcs:ignore PHPCompatibility.Constants.NewConstants.t_enumFound -- guarded by defined().
			$type_tokens[] = T_ENUM;
		}

		for ( $i = 0; $i < $count; $i++ ) {
			$token = $tokens[ $i ];
			if ( ! is_array( $token ) ) {
				continue;
			}

			if ( T_NAMESPACE === $token[0] ) {
				$namespace = $this->read_namespace( $tokens, $i, $count );
				continue;
			}

			if ( ! in_array( $token[0], $type_tokens, true ) ) {
				continue;
			}

			// 'Foo::class' constant references and 'new class' anonymous classes are not declarations.
			$previous = $this->significant_token( $tokens, $i, -1 );
			if ( is_array( $previous ) && in_array( $previous[0], array( T_DOUBLE_COLON, T_NEW ), true ) ) {
				continue;
			}

			$next = $this->significant_token( $tokens, $i, 1 );
			if ( ! is_array( $next ) || T_STRING !== $next[0] ) {
				continue;
			}

			$declarations[] = ( '' === $namespace ? '' : $namespace . '\\' ) . $next[1];
		}

		return $declarations;
	}

	/**
	 * Determine which types were removed between two scans and are not allowlisted.
	 *
	 * @param array    $old_types Map of type name => file from the previous version.
	 * @param array    $new_types Map of type name => file from the current version.
	 * @param string[] $allowlist Type names that are allowed to be removed.
	 * @return array Map of type name => old file path for each unallowed removal.
	 */
	public function find_unallowed_removals( array $old_types, array $new_types, array $allowlist ): array {
		$removed = array_diff_key( $old_types, $new_types );
		return array_diff_key( $removed, array_flip( $allowlist ) );
	}

	/**
	 * Parse an allowlist file: one fully qualified type name per line,
	 * blank lines and lines starting with '#' are ignored.
	 *
	 * @param string $contents Contents of the allowlist file.
	 * @return string[] Allowlisted type names.
	 */
	public function parse_allowlist( string $contents ): array {
		$entries = array();
		foreach ( preg_split( '/\R/', $contents ) as $line ) {
			$line = trim( $line );
			if ( '' === $line || str_starts_with( $line, '#' ) ) {
				continue;
			}
			$entries[] = ltrim( $line, '\\' );
		}
		return $entries;
	}

	/**
	 * Read a namespace declaration starting at the T_NAMESPACE token.
	 *
	 * @param array $tokens Token list from token_get_all.
	 * @param int   $index Index of the T_NAMESPACE token.
	 * @param int   $count Total token count.
	 * @return string The namespace name, '' for the global namespace.
	 */
	private function read_namespace( array $tokens, int $index, int $count ): string {
		$namespace = '';
		for ( $i = $index + 1; $i < $count; $i++ ) {
			$token = $tokens[ $i ];
			if ( is_array( $token ) ) {
				if ( in_array( $token[0], array( T_WHITESPACE, T_COMMENT, T_DOC_COMMENT ), true ) ) {
					continue;
				}
				$is_name_token = in_array( $token[0], array( T_STRING, T_NS_SEPARATOR ), true )
					// phpcs:ignore PHPCompatibility.Constants.NewConstants.t_name_qualifiedFound -- guarded by defined().
					|| ( defined( 'T_NAME_QUALIFIED' ) && T_NAME_QUALIFIED === $token[0] );
				if ( $is_name_token ) {
					$namespace .= $token[1];
					continue;
				}
			}
			break;
		}
		return $namespace;
	}

	/**
	 * Get the nearest non-whitespace, non-comment token in the given direction.
	 *
	 * @param array $tokens Token list from token_get_all.
	 * @param int   $index Index to start from (exclusive).
	 * @param int   $direction 1 for forwards, -1 for backwards.
	 * @return array|string|null The token, or null if none found.
	 */
	private function significant_token( array $tokens, int $index, int $direction ) {
		$count = count( $tokens );
		for ( $i = $index + $direction; $i >= 0 && $i < $count; $i += $direction ) {
			$token = $tokens[ $i ];
			if ( is_array( $token ) && in_array( $token[0], array( T_WHITESPACE, T_COMMENT, T_DOC_COMMENT ), true ) ) {
				continue;
			}
			return $token;
		}
		return null;
	}

	/**
	 * Whether a file path is excluded from scanning.
	 *
	 * @param string $path Normalized (forward-slash) absolute file path.
	 * @return bool True if the file must not be scanned.
	 */
	private function is_excluded( string $path ): bool {
		foreach ( self::EXCLUDED_PATH_FRAGMENTS as $fragment ) {
			if ( false !== strpos( $path, $fragment ) ) {
				return true;
			}
		}
		return false;
	}
}

// Run the CLI only when executed directly, so that tests can require this file as a library.
if ( 'cli' === PHP_SAPI && isset( $argv[0] ) && realpath( $argv[0] ) === __FILE__ ) {
	$checker = new WC_Removed_Classes_Checker();
	$command = $argv[1] ?? '';

	if ( 'scan' === $command && isset( $argv[2], $argv[3] ) ) {
		try {
			$types = $checker->scan( $argv[2] );
		} catch ( \InvalidArgumentException $e ) {
			echo 'Error: ' . $e->getMessage() . "\n";
			exit( 1 );
		}
		file_put_contents( $argv[3], json_encode( $types, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) . "\n" );
		echo 'Scanned ' . count( $types ) . " types from {$argv[2]} into {$argv[3]}\n";
		exit( 0 );
	}

	if ( 'compare' === $command && isset( $argv[2], $argv[3] ) ) {
		$old_types = json_decode( (string) file_get_contents( $argv[2] ), true );
		$new_types = json_decode( (string) file_get_contents( $argv[3] ), true );
		if ( ! is_array( $old_types ) || ! is_array( $new_types ) ) {
			echo "Error: could not read the scan result files.\n";
			exit( 1 );
		}

		$allowlist = array();
		if ( isset( $argv[4] ) && file_exists( $argv[4] ) ) {
			$allowlist = $checker->parse_allowlist( (string) file_get_contents( $argv[4] ) );
		}

		$removals = $checker->find_unallowed_removals( $old_types, $new_types, $allowlist );
		if ( empty( $removals ) ) {
			echo "No unallowed class removals detected.\n";
			exit( 0 );
		}

		echo count( $removals ) . " type(s) present in the baseline are missing from the compared tree:\n\n";
		foreach ( $removals as $removed_type => $removed_file ) {
			echo "  - $removed_type (was in $removed_file)\n";
		}
		echo "\nRemoving a shipped class causes fatal errors during in-place updates: code from the\n";
		echo "previous version can still reference it while the files are being swapped on disk.\n";
		echo "Deprecate the class and keep it as a stub for at least one release instead. If the\n";
		echo "removal is intentional and update-safe, acknowledge it by checking this box in the\n";
		echo "PR description (part of the PR template) and explaining why:\n";
		echo "- [x] This Pull Request intentionally removes PHP classes. (Comment required in the description)\n";
		exit( 1 );
	}

	echo "Usage:\n";
	echo "  php check-removed-classes.php scan <plugin_root> <output.json>\n";
	echo "  php check-removed-classes.php compare <old.json> <new.json> [<allowlist_file>]\n";
	exit( 1 );
}
