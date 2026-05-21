<?php
/**
 * StarRating control class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\OrderReviews;

/**
 * Server-side renderer for the accessible 5-star rating control used on the
 * Review Order page.
 *
 * The control degrades to native `<input type="radio">` elements without
 * JavaScript and is enhanced by `client/legacy/js/frontend/order-review.js`
 * for keyboard navigation, a visible focus ring, and the dynamic caption
 * underneath the stars.
 *
 * @internal Just for internal use.
 *
 * @since 10.8.0
 */
class StarRating {

	/**
	 * Render the rating control. Returns the HTML; does not echo.
	 *
	 * Required keys in `$args`:
	 *  - `name` (string): form field name (one per item).
	 *  - `id_prefix` (string): prefix used to build unique radio ids.
	 *  - `label_id` (string): id of the existing label element that describes
	 *    the group via `aria-labelledby`.
	 *
	 * Optional:
	 *  - `selected` (int): pre-selected value 0-5; pass `0` (the default)
	 *    for no pre-selection. Values outside 0-5 are treated as no selection.
	 *
	 * @since 10.8.0
	 *
	 * @param array $args Render arguments. See description for required and optional keys.
	 * @return string
	 */
	public static function render( array $args ): string {
		$name      = (string) ( $args['name'] ?? '' );
		$id_prefix = (string) ( $args['id_prefix'] ?? '' );
		$label_id  = (string) ( $args['label_id'] ?? '' );

		if ( '' === $name || '' === $id_prefix || '' === $label_id ) {
			return '';
		}

		$selected = (int) ( $args['selected'] ?? 0 );
		if ( $selected < 0 || $selected > 5 ) {
			$selected = 0;
		}

		ob_start();
		wc_get_template(
			'order/star-rating.php',
			array(
				'name'      => $name,
				'id_prefix' => $id_prefix,
				'label_id'  => $label_id,
				'selected'  => $selected,
				'labels'    => self::get_labels(),
			)
		);
		return (string) ob_get_clean();
	}

	/**
	 * Default rating labels, indexed 1-5, after the customer-facing filter.
	 *
	 * Defaults match the long-standing WooCommerce product-review labels in
	 * `templates/single-product-reviews.php` so customers see consistent
	 * wording across the two review entry points.
	 *
	 * @since 10.8.0
	 *
	 * @return array<int, string>
	 */
	public static function get_labels(): array {
		$labels = array(
			1 => __( 'Very poor', 'woocommerce' ),
			2 => __( 'Not that bad', 'woocommerce' ),
			3 => __( 'Average', 'woocommerce' ),
			4 => __( 'Good', 'woocommerce' ),
			5 => __( 'Perfect', 'woocommerce' ),
		);

		/**
		 * Filter the labels shown under the star-rating control.
		 *
		 * @since 10.8.0
		 *
		 * @param array<int, string> $labels Map of rating value (1-5) to label.
		 */
		$filtered = (array) apply_filters( 'woocommerce_review_order_rating_labels', $labels );

		// Keep only known 1-5 keys; fall back to defaults for any the filter dropped.
		return array_replace( $labels, array_intersect_key( $filtered, $labels ) );
	}
}
