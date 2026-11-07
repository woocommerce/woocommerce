<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Admin\ImportExport\Product;

use InvalidArgumentException;

/**
 * Resolves CSV header names to WooCommerce product importer schema keys.
 *
 * Wraps the auto-mapping and validation logic that
 * `WC_Product_CSV_Importer_Controller` uses for the admin CSV importer UI, so
 * runners outside the admin UI (currently the WP-CLI product import command) can
 * build a `mapping` array for `WC_Product_CSV_Importer`.
 *
 * @since 11.4.0
 */
final class MappingResolver {

	/**
	 * Unknown headers are skipped (mapped to an empty target).
	 *
	 * @since 11.4.0
	 */
	public const UNKNOWN_SKIP = 'skip';

	/**
	 * Unknown headers are imported as product meta.
	 *
	 * @since 11.4.0
	 */
	public const UNKNOWN_META = 'meta';

	/**
	 * Regex for the numbered attribute special columns produced by the importer.
	 */
	private const ATTRIBUTE_TARGET_REGEX = '/^attributes:(name|value|taxonomy|visible|default)\d+$/';

	/**
	 * Regex for the numbered download special columns produced by the importer.
	 */
	private const DOWNLOAD_TARGET_REGEX = '/^downloads:(id|name|url)\d+$/';

	/**
	 * Regex for meta targets, which accept any non-empty meta key.
	 */
	private const META_TARGET_REGEX = '/^meta:.+/';

	/**
	 * Cache of the flattened set of valid plain mapping targets, keyed by target name.
	 *
	 * @var array<string, bool>|null
	 */
	private ?array $valid_targets = null;

	/**
	 * Build a mapping array for `WC_Product_CSV_Importer` from CSV headers.
	 *
	 * Auto-maps the given headers the same way the admin CSV importer does,
	 * applies overrides on top, and resolves headers that neither auto-map
	 * nor have an override according to `$unknown_mode`.
	 *
	 * @since 11.4.0
	 *
	 * @param string[]              $raw_headers  Raw CSV header row.
	 * @param array<string, string> $overrides    Header => target overrides. `''` or `'-'` skips the column.
	 * @param string                $unknown_mode One of `self::UNKNOWN_SKIP` or `self::UNKNOWN_META`.
	 *
	 * @return array{mapping: array<string, string>, unknown: string[]}
	 *
	 * @throws InvalidArgumentException When an override matches no header, or a header or override resolves to an invalid target.
	 */
	public function resolve( array $raw_headers, array $overrides = array(), string $unknown_mode = self::UNKNOWN_SKIP ): array {
		$unmatched = array_diff( array_keys( $overrides ), $raw_headers );
		if ( ! empty( $unmatched ) ) {
			throw new InvalidArgumentException( esc_html( 'Mapping overrides must match a CSV column exactly, including case. No match for: ' . implode( ', ', $unmatched ) ) );
		}

		$this->load_importer();

		$controller  = new CsvImporterController();
		$auto_mapped = $controller->get_auto_mapped_columns( $raw_headers, false );

		$mapping = array();
		$unknown = array();

		foreach ( $raw_headers as $header ) {
			$target = isset( $auto_mapped[ $header ] ) ? $auto_mapped[ $header ] : '';

			// get_auto_mapped_columns() leaves an unrecognised header as its own lowercased text, unless that text happens to be a valid key such as `sku`.
			// An empty header, such as from a trailing comma, has no name to report or map to meta, so it stays unmapped.
			if ( '' !== $header && ! isset( $overrides[ $header ] ) && strtolower( $header ) === $target && ! $this->is_valid_target( $target ) ) {
				$unknown[] = $header;
				$target    = self::UNKNOWN_META === $unknown_mode ? 'meta:' . $header : '';
			}

			$mapping[ $header ] = $target;
		}

		foreach ( $overrides as $header => $target ) {
			$mapping[ $header ] = ( '' === $target || '-' === $target ) ? '' : $target;
		}

		$invalid = array();
		foreach ( $mapping as $header => $target ) {
			if ( '' !== $target && ! $this->is_valid_target( $target ) ) {
				$invalid[] = $header . ' => ' . $target;
			}
		}

		if ( ! empty( $invalid ) ) {
			throw new InvalidArgumentException( esc_html( 'Invalid mapping target(s): ' . implode( ', ', $invalid ) ) );
		}

		return array(
			'mapping' => $mapping,
			'unknown' => $unknown,
		);
	}

	/**
	 * Parse a mapping override spec into a header => target array.
	 *
	 * Accepts a `@path/to/file.json` reference to a JSON file, an inline JSON
	 * object starting with `{`, or a comma-separated list of `Header=target`
	 * pairs.
	 *
	 * @since 11.4.0
	 *
	 * @param string $spec The override spec.
	 *
	 * @return array<string, string>
	 *
	 * @throws InvalidArgumentException When the spec references a file that cannot be read, or is malformed.
	 */
	public function parse_overrides( string $spec ): array {
		if ( '' === $spec ) {
			return array();
		}

		if ( '@' === $spec[0] ) {
			$path = substr( $spec, 1 );

			if ( ! is_readable( $path ) ) {
				throw new InvalidArgumentException( esc_html( "Could not read mapping overrides file: {$path}" ) );
			}

			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local file read for a CLI-provided mapping override file, already checked for readability above.
			$contents = file_get_contents( $path );

			if ( false === $contents ) {
				throw new InvalidArgumentException( esc_html( "Could not read mapping overrides file: {$path}" ) );
			}

			return $this->decode_json_overrides( $contents );
		}

		if ( '{' === $spec[0] ) {
			return $this->decode_json_overrides( $spec );
		}

		$overrides = array();

		foreach ( explode( ',', $spec ) as $pair ) {
			$pair = trim( $pair );

			if ( '' === $pair ) {
				continue;
			}

			$parts = explode( '=', $pair, 2 );

			if ( 2 !== count( $parts ) ) {
				throw new InvalidArgumentException( esc_html( "Malformed mapping override pair: {$pair}" ) );
			}

			$overrides[ trim( $parts[0] ) ] = trim( $parts[1] );
		}

		return $overrides;
	}

	/**
	 * Check whether a mapping target is a valid `WC_Product_CSV_Importer` schema key.
	 *
	 * @since 11.4.0
	 *
	 * @param string $target The mapping target to check.
	 *
	 * @return bool
	 */
	public function is_valid_target( string $target ): bool {
		if ( isset( $this->get_valid_targets()[ $target ] ) ) {
			return true;
		}

		return (bool) preg_match( self::META_TARGET_REGEX, $target )
			|| (bool) preg_match( self::ATTRIBUTE_TARGET_REGEX, $target )
			|| (bool) preg_match( self::DOWNLOAD_TARGET_REGEX, $target );
	}

	/**
	 * Decode a JSON overrides string into a flat header => target array.
	 *
	 * @param string $json JSON string, either from a file or given inline.
	 *
	 * @return array<string, string>
	 *
	 * @throws InvalidArgumentException When the JSON is invalid or not a flat string map.
	 */
	private function decode_json_overrides( string $json ): array {
		$decoded = json_decode( $json, true );

		if ( JSON_ERROR_NONE !== json_last_error() || ! is_array( $decoded ) ) {
			throw new InvalidArgumentException( 'Mapping overrides JSON is invalid.' );
		}

		foreach ( $decoded as $key => $value ) {
			if ( ! is_string( $key ) || ! is_string( $value ) ) {
				throw new InvalidArgumentException( 'Mapping overrides JSON must be a flat object of string keys to string values.' );
			}
		}

		return $decoded;
	}

	/**
	 * Get the flattened set of valid plain mapping targets, keyed by target name.
	 *
	 * @return array<string, bool>
	 */
	private function get_valid_targets(): array {
		if ( null !== $this->valid_targets ) {
			return $this->valid_targets;
		}

		$this->load_importer();

		$options = ( new CsvImporterController() )->get_column_mapping_options();
		$flat    = array();

		foreach ( $options as $key => $value ) {
			if ( is_array( $value ) ) {
				if ( isset( $value['options'] ) && is_array( $value['options'] ) ) {
					foreach ( array_keys( $value['options'] ) as $sub_key ) {
						$flat[ $sub_key ] = true;
					}
				}
				continue;
			}

			$flat[ $key ] = true;
		}

		// These are numbered special-column templates (attributes:name, downloads:id, meta:...)
		// with no number appended; real targets are validated via the special-prefix regexes instead.
		foreach ( array_keys( $flat ) as $key ) {
			if ( 0 === strpos( $key, 'attributes:' ) || 0 === strpos( $key, 'downloads:' ) || 0 === strpos( $key, 'meta:' ) ) {
				unset( $flat[ $key ] );
			}
		}

		$this->valid_targets = $flat;

		return $this->valid_targets;
	}

	/**
	 * Load the admin-only importer controller class, which is not included outside admin requests.
	 */
	private function load_importer(): void {
		if ( ! class_exists( 'WC_Product_CSV_Importer_Controller' ) ) {
			include_once WC_ABSPATH . 'includes/admin/importers/class-wc-product-csv-importer-controller.php';
		}
	}
}
