<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Admin\Payments\Finance\Mocks;

use Automattic\WooCommerce\Admin\Payments\Finance\FinanceDataProviderInterface;

/**
 * Fake finance data provider that declares data types without implementing any per-type interface.
 */
class FakeBaseFinanceDataProvider implements FinanceDataProviderInterface {

	/**
	 * The payment gateway id.
	 *
	 * @var string
	 */
	private string $gateway_id;

	/**
	 * The declared data types and versions.
	 *
	 * @var array<string, int>
	 */
	private array $supported_data_types;

	/**
	 * The title of the payment provider.
	 *
	 * @var string
	 */
	private string $title;

	/**
	 * Constructor.
	 *
	 * @param string $gateway_id           The payment gateway id.
	 * @param array  $supported_data_types The declared data types.
	 * @param string $title                The title of the payment provider.
	 */
	public function __construct( string $gateway_id, array $supported_data_types, string $title ) {
		$this->gateway_id           = $gateway_id;
		$this->supported_data_types = $supported_data_types;
		$this->title                = $title;
	}

	/**
	 * Get the payment gateway id.
	 *
	 * @return string
	 */
	public function get_payment_gateway_id(): string {
		return $this->gateway_id;
	}

	/**
	 * Get the declared data types.
	 *
	 * @return array<string, int>
	 */
	public function get_supported_data_types(): array {
		return $this->supported_data_types;
	}

	/**
	 * Get the icon URL.
	 *
	 * @return string
	 */
	public function get_icon_url(): string {
		return '';
	}

	/**
	 * Get the title of the payment provider.
	 *
	 * @return string
	 */
	public function get_title(): string {
		return $this->title;
	}
}
