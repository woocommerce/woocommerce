<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\PushNotifications\Notifications;

use InvalidArgumentException;
use WC_Product;

defined( 'ABSPATH' ) || exit;

/**
 * Notification for product stock events (low stock, out of stock, backorder).
 *
 * @since 10.9.0
 */
class StockNotification extends Notification {
	const TYPE = 'store_stock';

	const EVENT_LOW_STOCK    = 'low_stock';
	const EVENT_OUT_OF_STOCK = 'out_of_stock';
	const EVENT_ON_BACKORDER = 'on_backorder';

	const VALID_EVENT_TYPES = array(
		self::EVENT_LOW_STOCK,
		self::EVENT_OUT_OF_STOCK,
		self::EVENT_ON_BACKORDER,
	);

	/**
	 * Emoji appended to the notification title, one per stock event type.
	 */
	const EMOJI_OUT_OF_STOCK = '🚨';
	const EMOJI_ON_BACKORDER = '🕐';
	const EMOJI_LOW_STOCK    = '⚠️';

	/**
	 * The stock event that triggered this notification.
	 *
	 * @var string
	 */
	private string $event_type;

	/**
	 * Stock quantity captured at the moment the WC stock event fired.
	 *
	 * Captured at trigger time rather than read at dispatch time so the
	 * notification reflects the threshold-crossing moment, not whatever
	 * stock level the product happens to be at when the dispatcher (which
	 * runs in a separate process — internal REST endpoint or ActionScheduler
	 * safety net) eventually re-fetches the product. Avoids stale-cache reads
	 * and remains correct even if subsequent orders reduce stock further
	 * before dispatch.
	 *
	 * Only meaningful for the low_stock event today; null for the other two.
	 *
	 * @var int|null
	 */
	private ?int $stock_quantity_at_trigger;

	/**
	 * Creates a new StockNotification instance.
	 *
	 * @param int      $resource_id              The product ID.
	 * @param string   $event_type               One of the EVENT_* constants.
	 * @param int|null $stock_quantity_at_trigger Stock quantity captured when the WC stock event fired, or null if unknown.
	 *
	 * @throws InvalidArgumentException If the resource ID or event type is invalid.
	 *
	 * @since 10.9.0
	 */
	public function __construct( int $resource_id, string $event_type = self::EVENT_LOW_STOCK, ?int $stock_quantity_at_trigger = null ) {
		parent::__construct( $resource_id );

		if ( ! in_array( $event_type, self::VALID_EVENT_TYPES, true ) ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			throw new InvalidArgumentException( sprintf( 'Invalid stock notification event type: %s', $event_type ) );
		}

		$this->event_type                = $event_type;
		$this->stock_quantity_at_trigger = $stock_quantity_at_trigger;
	}

	/**
	 * Restores extra state from a serialized notification array.
	 *
	 * Called by {@see Notification::from_array()} after construction to
	 * restore the event type that the default constructor cannot receive.
	 *
	 * Throws when `event_type` is present but unrecognized so the safety-net
	 * caller (which wraps reconstruction in a try/catch) drops the corrupt
	 * job rather than silently dispatching the wrong notification subtype.
	 * A missing `event_type` is allowed — the default set by the constructor
	 * survives, which preserves backward compatibility with any in-flight
	 * scheduled actions that pre-date this field.
	 *
	 * @param array $data The serialized notification data.
	 *
	 * @throws InvalidArgumentException If `event_type` is present but not a known value.
	 *
	 * @since 10.9.0
	 */
	public function hydrate( array $data ): void {
		if ( array_key_exists( 'event_type', $data ) ) {
			$event_type = $data['event_type'];

			if ( ! in_array( $event_type, self::VALID_EVENT_TYPES, true ) ) {
				// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
				throw new InvalidArgumentException( sprintf( 'Invalid stock notification event type during hydrate: %s', is_scalar( $event_type ) ? (string) $event_type : gettype( $event_type ) ) );
			}

			$this->event_type = $event_type;
		}

		if ( array_key_exists( 'stock_quantity_at_trigger', $data ) ) {
			$stock                           = $data['stock_quantity_at_trigger'];
			$this->stock_quantity_at_trigger = is_int( $stock ) ? $stock : null;
		}
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_type(): string {
		return self::TYPE;
	}

	/**
	 * Returns the stock event type.
	 *
	 * @return string
	 *
	 * @since 10.9.0
	 */
	public function get_event_type(): string {
		return $this->event_type;
	}

	/**
	 * {@inheritDoc}
	 *
	 * Includes `event_type` so the same product can have distinct
	 * notifications for different stock events in-flight simultaneously.
	 */
	public function get_identifier(): string {
		return sprintf(
			'%s_%s_%s_%s',
			get_current_blog_id(),
			$this->get_type(),
			$this->event_type,
			$this->get_resource_id()
		);
	}

	/**
	 * {@inheritDoc}
	 *
	 * Appends `event_type` because it is part of this notification's identity
	 * (see {@see self::get_identifier()}): the same product can have distinct
	 * low_stock / out_of_stock / on_backorder safety nets pending at once, and
	 * the callback needs it to reconstruct the correct subtype.
	 *
	 * `stock_quantity_at_trigger` is deliberately omitted — it is volatile
	 * payload data, not identity, and does not round-trip through every cancel
	 * path, so including it in the match key would risk breaking cancellation.
	 * The safety-net fallback message reads current product stock when it is
	 * absent (see {@see self::build_message()}).
	 *
	 * @return array<int, mixed>
	 *
	 * @since 10.9.0
	 */
	public function get_safety_net_args(): array {
		return array(
			$this->get_type(),
			$this->get_resource_id(),
			array( 'event_type' => $this->event_type ),
		);
	}

	/**
	 * {@inheritDoc}
	 *
	 * Extends the parent array with `event_type` and the trigger-time stock
	 * snapshot so both fields survive serialization through the safety-net
	 * scheduler and the internal-REST round-trip.
	 *
	 * @return array{type: string, resource_id: int, event_type: string, stock_quantity_at_trigger: int|null}
	 *
	 * @since 10.9.0
	 */
	public function to_array(): array {
		return array_merge(
			parent::to_array(),
			array(
				'event_type'                => $this->event_type,
				'stock_quantity_at_trigger' => $this->stock_quantity_at_trigger,
			)
		);
	}

	/**
	 * Returns the WPCOM-ready payload for this notification.
	 *
	 * Returns null if the product no longer exists.
	 *
	 * @return array|null
	 *
	 * @since 10.9.0
	 */
	public function to_payload(): ?array {
		$product = WC()->call_function( 'wc_get_product', $this->get_resource_id() );

		if ( ! $product || ! $product instanceof WC_Product ) {
			return null;
		}

		$product_name = wp_strip_all_tags( $product->get_name() );
		$site_title   = wp_strip_all_tags( get_bloginfo( 'name' ) );

		// For variations, `meta.product_id` is the parent product ID so the mobile app
		// can always navigate to the product details screen. `resource_id` keeps the
		// actual entity ID (variation or simple product) for identification and dedup.
		$is_variation = $product->is_type( 'variation' );
		$product_id   = $is_variation ? $product->get_parent_id() : $product->get_id();

		return array(
			'type'        => $this->get_type(),
			'timestamp'   => gmdate( 'c' ),
			'resource_id' => $this->get_resource_id(),
			'title'       => $this->build_title( $product_name ),
			'message'     => $this->build_message( $product_name, $site_title, $product ),
			'meta'        => array(
				'product_id' => $product_id,
				'event_type' => $this->event_type,
			),
		);
	}

	/**
	 * {@inheritDoc}
	 *
	 * Extends the base enabled-toggle check with per-event sub-flag filtering.
	 *
	 * @param mixed $pref_value The user's stored preference value, or null.
	 * @return bool
	 *
	 * @since 10.9.0
	 */
	public function should_send_to_user( $pref_value ): bool {
		if ( ! parent::should_send_to_user( $pref_value ) ) {
			return false;
		}

		if ( ! is_array( $pref_value ) || ! array_key_exists( $this->event_type, $pref_value ) ) {
			return true;
		}

		return (bool) $pref_value[ $this->event_type ];
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param string $key The meta key.
	 */
	public function has_meta( string $key ): bool {
		$product = WC()->call_function( 'wc_get_product', $this->get_resource_id() );
		return $product instanceof WC_Product && $product->meta_exists( $key . '_' . $this->event_type );
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param string $key The meta key.
	 */
	public function write_meta( string $key ): void {
		$product = WC()->call_function( 'wc_get_product', $this->get_resource_id() );

		if ( $product instanceof WC_Product ) {
			$product->update_meta_data( $key . '_' . $this->event_type, (string) time() );
			$product->save_meta_data();
		}
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param string $key The meta key.
	 */
	public function delete_meta( string $key ): void {
		$product = WC()->call_function( 'wc_get_product', $this->get_resource_id() );

		if ( $product instanceof WC_Product ) {
			$product->delete_meta_data( $key . '_' . $this->event_type );
			$product->save_meta_data();
		}
	}

	/**
	 * Builds the title payload for the notification.
	 *
	 * @param string $product_name The sanitized product name.
	 * @return array{format: string, args: string[]}
	 */
	private function build_title( string $product_name ): array {
		switch ( $this->event_type ) {
			case self::EVENT_OUT_OF_STOCK:
				return array(
					'format' => 'Out of stock: %1$s %2$s',
					'args'   => array( $product_name, self::EMOJI_OUT_OF_STOCK ),
				);

			case self::EVENT_ON_BACKORDER:
				return array(
					'format' => 'Backordered: %1$s %2$s',
					'args'   => array( $product_name, self::EMOJI_ON_BACKORDER ),
				);

			default:
				return array(
					'format' => 'Low stock: %1$s %2$s',
					'args'   => array( $product_name, self::EMOJI_LOW_STOCK ),
				);
		}
	}

	/**
	 * Builds the message payload for the notification.
	 *
	 * @param string     $product_name The sanitized product name.
	 * @param string     $site_title   The sanitized site title.
	 * @param WC_Product $product      The product object (used as a fallback when no trigger-time stock was captured).
	 * @return array{format: string, args: string[]}
	 */
	private function build_message( string $product_name, string $site_title, WC_Product $product ): array {
		switch ( $this->event_type ) {
			case self::EVENT_OUT_OF_STOCK:
				return array(
					'format' => '%1$s is out of stock on %2$s',
					'args'   => array( $product_name, $site_title ),
				);

			case self::EVENT_ON_BACKORDER:
				return array(
					'format' => '%1$s has been backordered on %2$s',
					'args'   => array( $product_name, $site_title ),
				);

			default:
				$stock = null !== $this->stock_quantity_at_trigger
					? $this->stock_quantity_at_trigger
					: $product->get_stock_quantity();

				return array(
					'format' => '%1$s is running low (%2$s remaining) on %3$s',
					'args'   => array(
						$product_name,
						(string) $stock,
						$site_title,
					),
				);
		}//end switch
	}
}
