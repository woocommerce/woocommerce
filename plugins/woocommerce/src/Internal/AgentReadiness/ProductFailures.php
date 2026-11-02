<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\AgentReadiness;

defined( 'ABSPATH' ) || exit;

/**
 * What each product fails, kept in product meta so the Products list can filter on it.
 * One row per value: a check id, or a status with a channel such as not_listable:chatgpt,
 * plus `checked` on every product the checks cover, so the product total stays live too.
 * A product with no rows is one the checks leave out.
 *
 * @since 11.4.0
 */
class ProductFailures {

	public const META_KEY = '_wc_agent_readiness_failed';

	/**
	 * The value every checked product has, passing or not.
	 */
	public const CHECKED = 'checked';

	/**
	 * Counts read in this request; cleared on every write.
	 *
	 * @var array<string, int>|null
	 */
	private static ?array $counts = null;

	/**
	 * The value for a status on a channel.
	 *
	 * @param string $status  ProductScan status.
	 * @param string $channel Channel id.
	 */
	public static function channel_value( string $status, string $channel ): string {
		return $status . ':' . $channel;
	}

	/**
	 * Store what a product fails, writing only what changed.
	 *
	 * @param int                                                             $product_id Product id.
	 * @param array<string, array<string, array{field:string,effect:string}>> $failures   Channel map of each failed check, by check id.
	 */
	public static function store( int $product_id, array $failures ): void {
		$values = array_merge( array( self::CHECKED ), array_map( 'strval', array_keys( $failures ) ) );
		foreach ( ProductScan::statuses( array_values( $failures ) ) as $channel => $status ) {
			if ( ProductScan::COMPLETE !== $status ) {
				$values[] = self::channel_value( $status, $channel );
			}
		}
		self::$counts = null;
		$stored       = self::stored( $product_id );
		foreach ( array_diff( $stored, $values ) as $value ) {
			delete_post_meta( $product_id, self::META_KEY, $value );
		}
		foreach ( array_diff( $values, $stored ) as $value ) {
			add_post_meta( $product_id, self::META_KEY, $value );
		}
	}

	/**
	 * What a product fails.
	 *
	 * @param int $product_id Product id.
	 * @return array<int, string>
	 */
	public static function for_product( int $product_id ): array {
		return array_values( array_diff( self::stored( $product_id ), array( self::CHECKED ) ) );
	}

	/**
	 * Remove a product's rows, for a product the checks no longer cover.
	 *
	 * @param int $product_id Product id.
	 */
	public static function forget( int $product_id ): void {
		self::$counts = null;
		delete_post_meta( $product_id, self::META_KEY );
	}

	/**
	 * Every stored value of a product, `checked` included.
	 *
	 * @param int $product_id Product id.
	 * @return array<int, string>
	 */
	private static function stored( int $product_id ): array {
		return array_map( 'strval', (array) get_post_meta( $product_id, self::META_KEY, false ) );
	}

	/**
	 * Remove the rows of products a scan didn't test, such as those now hidden from the catalog.
	 *
	 * @param array<int, int> $tested Product ids the scan tested.
	 */
	public static function keep_only( array $tested ): void {
		global $wpdb;
		self::$counts = null;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Only this query finds rows of products the scan didn't visit.
		$stored = array_map( 'intval', (array) $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT post_id FROM {$wpdb->postmeta} WHERE meta_key = %s", self::META_KEY ) ) );
		foreach ( array_diff( $stored, $tested ) as $product_id ) {
			self::forget( $product_id );
		}
	}

	/**
	 * How many products have each value. Read once per request: the settings page and the Products filter both ask.
	 *
	 * @return array<string, int>
	 */
	public static function counts(): array {
		global $wpdb;
		if ( null !== self::$counts ) {
			return self::$counts;
		}
		$rows   = (array) $wpdb->get_results( $wpdb->prepare( "SELECT meta_value, COUNT(*) AS products FROM {$wpdb->postmeta} WHERE meta_key = %s GROUP BY meta_value", self::META_KEY ), ARRAY_A );
		$counts = array();
		foreach ( $rows as $row ) {
			$counts[ (string) $row['meta_value'] ] = (int) $row['products'];
		}
		self::$counts = $counts;
		return $counts;
	}
}
