<?php
declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\StockNotifications\Frontend;

use Automattic\WooCommerce\Internal\StockNotifications\Notification;

/**
 * A class for representing the result of a signup.
 *
 * @internal
 */
class SignupResult {

	/**
	 * The signup code.
	 *
	 * @var string
	 */
	private string $code;

	/**
	 * The notification.
	 *
	 * @var Notification|null
	 */
	private ?Notification $notification;

	/**
	 * Constructor.
	 *
	 * @param string            $code The signup code.
	 * @param Notification|null $notification The notification.
	 */
	public function __construct( string $code, ?Notification $notification = null ) {
		$this->code         = $code;
		$this->notification = $notification;
	}

	/**
	 * Get the signup code.
	 *
	 * @return string
	 */
	public function get_code(): string {
		return $this->code;
	}

	/**
	 * Get the notification.
	 *
	 * @return Notification|null
	 */
	public function get_notification(): ?Notification {
		return $this->notification;
	}
}
