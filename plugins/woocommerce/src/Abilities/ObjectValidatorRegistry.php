<?php
/**
 * Validators extensions run on a fully applied object, after every change is
 * applied in memory and before the single save.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Abilities;

use WC_Data;

defined( 'ABSPATH' ) || exit;

/**
 * Object validator registry.
 */
class ObjectValidatorRegistry {

	/**
	 * Shared instance.
	 *
	 * @var self|null
	 */
	private static $instance = null;

	/**
	 * Whether registrations were collected.
	 *
	 * @var bool
	 */
	private $collected = false;

	/**
	 * Whether registrations are being collected.
	 *
	 * @var bool
	 */
	private $collecting = false;

	/**
	 * Validators keyed by object kind, then namespace.
	 *
	 * @var array<string, array<string, callable>>
	 */
	private $validators = array();

	/**
	 * The shared registry.
	 */
	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		self::$instance->collect();
		return self::$instance;
	}

	/**
	 * Collect registrations on first use, once all plugins have loaded.
	 */
	private function collect(): void {
		if ( $this->collected || $this->collecting ) {
			return;
		}

		if ( ! did_action( 'plugins_loaded' ) || doing_action( 'plugins_loaded' ) ) {
			_doing_it_wrong(
				__CLASS__ . '::instance',
				esc_html__( 'Use this registry after all plugins have loaded.', 'woocommerce' ),
				'11.3.0'
			);
			return;
		}

		$this->collecting = true;

		/**
		 * Register validators that check a fully applied object before it is saved. Fires once, the first time the registry is used after all plugins have loaded.
		 *
		 * @since 11.3.0
		 *
		 * @param ObjectValidatorRegistry $registry Registry.
		 */
		do_action( 'woocommerce_register_object_validators', $this );
		$this->collecting = false;
		$this->collected  = true;
	}

	/**
	 * Register a validator. It receives the object and its changed order items,
	 * returns true or a WP_Error, and never changes data. A subscription does not
	 * inherit order validators. The first registration of a namespace wins.
	 *
	 * @param string   $kind                Object kind: `order`, `subscription`, `product`, `coupon`, another WC_Data object type, or an in-memory write's subject_type().
	 * @param string   $extension_namespace Extension namespace.
	 * @param callable $validator           Validator.
	 */
	public function register( string $kind, string $extension_namespace, callable $validator ): void {
		if ( $this->collected ) {
			_doing_it_wrong(
				__METHOD__,
				/* translators: %s: action name. */
				sprintf( esc_html__( 'Register object validators on the %s action.', 'woocommerce' ), 'woocommerce_register_object_validators' ),
				'11.3.0'
			);
			return;
		}

		if ( ! isset( $this->validators[ $kind ][ $extension_namespace ] ) ) {
			$this->validators[ $kind ][ $extension_namespace ] = $validator;
		}
	}

	/**
	 * Run every validator for the object's kind.
	 *
	 * @param WC_Data     $subject Fully applied, unsaved object.
	 * @param string|null $kind    Object kind, or null to derive it from the object.
	 * @return string|null The first rejection, or null when every validator accepts.
	 */
	public function validate( WC_Data $subject, ?string $kind = null ): ?string {
		$kind    = $kind ?? self::kind_of( $subject );
		$changed = $subject instanceof \WC_Order ? self::changed_items( $subject ) : array();

		foreach ( $this->validators[ $kind ] ?? array() as $validator ) {
			$result = call_user_func( $validator, $subject, $changed );
			if ( is_wp_error( $result ) ) {
				return $result->get_error_message();
			}
		}
		return null;
	}

	/**
	 * Object kind used to look up validators: `product` for products, the order
	 * type without `shop_` for orders (`order`, `subscription`), `coupon` for
	 * coupons, otherwise the object type.
	 *
	 * @param WC_Data $subject Object.
	 */
	private static function kind_of( WC_Data $subject ): string {
		if ( $subject instanceof \WC_Product ) {
			return 'product';
		}
		if ( $subject instanceof \WC_Abstract_Order ) {
			return (string) preg_replace( '/^shop_/', '', $subject->get_type() );
		}
		if ( $subject instanceof \WC_Coupon ) {
			return 'coupon';
		}
		return \Closure::bind(
			function () {
				return $this->object_type;
			},
			$subject,
			WC_Data::class
		)();
	}

	/**
	 * Order items with unsaved prop or meta changes.
	 *
	 * @param \WC_Order $order Order.
	 * @return array<int, \WC_Order_Item>
	 */
	private static function changed_items( \WC_Order $order ): array {
		$changed = array();
		foreach ( $order->get_items() as $item ) {
			$meta_changed = false;
			foreach ( $item->get_meta_data() as $meta ) {
				$meta_changed = $meta_changed || ! empty( $meta->get_changes() );
			}
			if ( $meta_changed || ! empty( $item->get_changes() ) ) {
				$changed[] = $item;
			}
		}
		return $changed;
	}
}
