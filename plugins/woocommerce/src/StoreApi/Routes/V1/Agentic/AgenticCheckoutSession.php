<?php
declare(strict_types=1);
namespace Automattic\WooCommerce\StoreApi\Routes\V1\Agentic;

use Automattic\WooCommerce\StoreApi\Routes\V1\Agentic\Messages\Messages;
use Automattic\WooCommerce\StoreApi\Routes\V1\Agentic\Enums\SessionKey;
use Automattic\WooCommerce\StoreApi\Utilities\CartTokenUtils;
use WC_Cart;

/**
 * AgenticCheckoutSession class.
 *
 * Wrapper for all things, associated with an agentic checkout session.
 * This class manages the cart and error handling for agentic checkout processes.
 */
final class AgenticCheckoutSession {
	/**
	 * The WooCommerce cart instance.
	 *
	 * @var WC_Cart
	 */
	private $cart;

	/**
	 * Error messages handler for the checkout session.
	 *
	 * @var Messages
	 */
	private $messages;

	/**
	 * The checkout session ID.
	 *
	 * @var string
	 */
	private $id;

	/**
	 * Constructor.
	 *
	 * @param WC_Cart $cart The WooCommerce cart instance.
	 */
	public function __construct( WC_Cart $cart ) {
		$this->cart     = $cart;
		$this->messages = new Messages();
		$this->id       = $this->get_or_set_checkout_session_id();
	}

	/**
	 * Gets the cart instance.
	 *
	 * @return WC_Cart The WooCommerce cart instance.
	 */
	public function get_cart(): WC_Cart {
		return $this->cart;
	}

	/**
	 * Gets the messages collection.
	 *
	 * @return Messages The messages handler instance.
	 */
	public function get_messages(): Messages {
		return $this->messages;
	}

	/**
	 * Gets the checkout session ID.
	 *
	 * @return string The checkout session ID.
	 */
	public function get_id(): string {
		return $this->id;
	}

	/**
	 * Get the checkout session ID. If it does not exist, generate a cart token for it and save to the current session.
	 *
	 * @return string Checkout Session ID stored in the current session.
	 */
	private function get_or_set_checkout_session_id(): string {
		$wc_session = WC()->session;
		if ( null === $wc_session ) {
			return '';
		}

		$session_id = $wc_session->get( SessionKey::AGENTIC_CHECKOUT_SESSION_ID );
		if ( null === $session_id ) {
			$session_id = CartTokenUtils::get_cart_token( (string) $wc_session->get_customer_id() );
			$wc_session->set( SessionKey::AGENTIC_CHECKOUT_SESSION_ID, $session_id );
		}

		return $session_id;
	}
}
