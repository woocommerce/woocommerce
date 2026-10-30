<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\StoreApi\Schemas\V1\Ucp;

use Automattic\WooCommerce\StoreApi\Schemas\ExtendSchema;
use Automattic\WooCommerce\StoreApi\SchemaController;
use Automattic\WooCommerce\StoreApi\Schemas\V1\CartSchema;
use Automattic\WooCommerce\StoreApi\Utilities\CartController;

/**
 * Cart schema the UCP cart routes read from.
 *
 * The Store API cart response already applies cart tax display, minor units,
 * image filters and stock validation; the UCP cart mapper reshapes it. Strings
 * stay raw, and items come from {@see UcpCartItemSchema} for the same reason.
 *
 * @since 11.3.0
 */
class UcpCartSchema extends CartSchema {
	/**
	 * The schema item name.
	 *
	 * @var string
	 */
	protected $title = 'ucp-cart';

	/**
	 * The schema item identifier.
	 *
	 * @var string
	 */
	const IDENTIFIER = 'ucp-cart';

	/**
	 * Constructor.
	 *
	 * @param ExtendSchema     $extend Rest Extending instance.
	 * @param SchemaController $controller Schema Controller instance.
	 */
	public function __construct( ExtendSchema $extend, SchemaController $controller ) {
		parent::__construct( $extend, $controller );
		/**
		 * The controller returns the registered subclass; typed for static analysis.
		 *
		 * @var UcpCartItemSchema $item_schema
		 */
		$item_schema       = $this->controller->get( UcpCartItemSchema::IDENTIFIER );
		$this->item_schema = $item_schema;
	}

	/**
	 * Return content unformatted.
	 *
	 * @param string|array $response Data to format.
	 * @return string|array
	 */
	protected function prepare_html_response( $response ) {
		return $response;
	}

	/**
	 * Get cart validation errors, with messages left raw.
	 *
	 * The parent runs each error through {@see \Automattic\WooCommerce\StoreApi\Schemas\V1\ErrorSchema},
	 * which texturizes the message; agents get the plain wording.
	 *
	 * @param \WC_Cart $cart Cart class instance.
	 * @return array Each `{ code, message }`.
	 */
	protected function get_cart_errors( $cart ) {
		$cart_errors = array();

		foreach ( (array) ( new CartController() )->get_cart_errors()->errors as $code => $messages ) {
			foreach ( (array) $messages as $message ) {
				$cart_errors[] = array(
					'code'    => (string) $code,
					'message' => (string) $message,
				);
			}
		}

		return $cart_errors;
	}
}
