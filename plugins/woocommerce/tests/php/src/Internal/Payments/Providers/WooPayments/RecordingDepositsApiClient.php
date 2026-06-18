<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Payments\Providers\WooPayments;

use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\Api\WooPaymentsApiClient;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\Api\WooPaymentsApiException;

/**
 * Recording API client for native WooPayments deposits REST tests.
 */
class RecordingDepositsApiClient extends WooPaymentsApiClient {

	/**
	 * Last deposits query.
	 *
	 * @var array<string,mixed>|null
	 */
	public ?array $last_deposits_query = null;

	/**
	 * Last deposits summary query.
	 *
	 * @var array<string,mixed>|null
	 */
	public ?array $last_deposits_summary_query = null;

	/**
	 * Last deposit ID.
	 *
	 * @var string|null
	 */
	public ?string $last_deposit_id = null;

	/**
	 * Last export query.
	 *
	 * @var array<string,mixed>|null
	 */
	public ?array $last_export_query = null;

	/**
	 * Last export user email.
	 *
	 * @var string|null
	 */
	public ?string $last_export_user_email = null;

	/**
	 * Last export locale.
	 *
	 * @var string|null
	 */
	public ?string $last_export_locale = null;

	/**
	 * Last export ID.
	 *
	 * @var string|null
	 */
	public ?string $last_export_id = null;

	/**
	 * Last manual payout payload.
	 *
	 * @var array<string,string>|null
	 */
	public ?array $last_manual_deposit = null;

	/**
	 * Deposits overview response.
	 *
	 * @var array<string,mixed>
	 */
	public array $deposits_overview_response = array();

	/**
	 * Deposits list response.
	 *
	 * @var array<string,mixed>
	 */
	public array $deposits_response = array(
		'data'        => array(),
		'total_count' => 0,
	);

	/**
	 * Deposits summary response.
	 *
	 * @var array<string,mixed>
	 */
	public array $deposits_summary_response = array();

	/**
	 * Deposit detail response.
	 *
	 * @var array<string,mixed>
	 */
	public array $deposit_response = array();

	/**
	 * Deposits export response.
	 *
	 * @var array<string,mixed>
	 */
	public array $deposits_export_response = array(
		'exported_deposits' => 0,
	);

	/**
	 * Payout export URL response.
	 *
	 * @var array<string,mixed>
	 */
	public array $payouts_export_url_response = array();

	/**
	 * Manual payout response.
	 *
	 * @var array<string,mixed>
	 */
	public array $manual_deposit_response = array();

	/**
	 * Optional exception thrown by the next read call.
	 *
	 * @var WooPaymentsApiException|null
	 */
	public ?WooPaymentsApiException $exception = null;

	/**
	 * Get payout overviews.
	 *
	 * @return array<string,mixed>
	 */
	public function get_deposits_overview(): array {
		$this->throw_if_configured();

		return $this->deposits_overview_response;
	}

	/**
	 * Get payouts.
	 *
	 * @param array<string,mixed> $query Query params.
	 * @return array<string,mixed>
	 */
	public function get_deposits( array $query = array() ): array {
		$this->last_deposits_query = $query;
		$this->throw_if_configured();

		return $this->deposits_response;
	}

	/**
	 * Get payout summary.
	 *
	 * @param array<string,mixed> $query Query params.
	 * @return array<string,mixed>
	 */
	public function get_deposits_summary( array $query = array() ): array {
		$this->last_deposits_summary_query = $query;
		$this->throw_if_configured();

		return $this->deposits_summary_response;
	}

	/**
	 * Get payout detail.
	 *
	 * @param string $deposit_id Deposit ID.
	 * @return array<string,mixed>
	 */
	public function get_deposit( string $deposit_id ): array {
		$this->last_deposit_id = $deposit_id;
		$this->throw_if_configured();

		return $this->deposit_response;
	}

	/**
	 * Initiate payout export.
	 *
	 * @param array<string,mixed> $query      Query params.
	 * @param string              $user_email User email.
	 * @param string|null         $locale     Locale.
	 * @return array<string,mixed>
	 */
	public function get_deposits_export( array $query = array(), string $user_email = '', ?string $locale = null ): array {
		$this->last_export_query      = $query;
		$this->last_export_user_email = $user_email;
		$this->last_export_locale     = $locale;
		$this->throw_if_configured();

		return $this->deposits_export_response;
	}

	/**
	 * Get payout export URL.
	 *
	 * @param string $export_id Export ID.
	 * @return array<string,mixed>
	 */
	public function get_payouts_export_url( string $export_id ): array {
		$this->last_export_id = $export_id;
		$this->throw_if_configured();

		return $this->payouts_export_url_response;
	}

	/**
	 * Trigger manual payout.
	 *
	 * @param string $type     Payout type.
	 * @param string $currency Currency.
	 * @return array<string,mixed>
	 */
	public function manual_deposit( string $type, string $currency ): array {
		$this->last_manual_deposit = array(
			'type'     => $type,
			'currency' => $currency,
		);
		$this->throw_if_configured();

		return $this->manual_deposit_response;
	}

	/**
	 * Throw configured API exception.
	 *
	 * @throws WooPaymentsApiException When configured for the test.
	 */
	private function throw_if_configured(): void {
		if ( null !== $this->exception ) {
			throw $this->exception;
		}
	}
}
