<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Payments\Providers\WooPayments;

use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\Api\WooPaymentsApiClient;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\Api\WooPaymentsApiException;

/**
 * Recording API client for native WooPayments Reports REST tests.
 */
class RecordingReportsApiClient extends WooPaymentsApiClient {

	/**
	 * Optional exception thrown by the next call.
	 *
	 * @var WooPaymentsApiException|null
	 */
	public ?WooPaymentsApiException $exception = null;

	/**
	 * Recorded last call.
	 *
	 * @var array<string,mixed>
	 */
	public array $last_call = array();

	/**
	 * Generic API response.
	 *
	 * @var array<string,mixed>
	 */
	public array $response = array();

	/**
	 * Get reporting balance summary.
	 *
	 * @param array<string,mixed> $query Query params.
	 * @return array<string,mixed>
	 */
	public function get_reporting_balance_summary( array $query = array() ): array {
		$this->record( 'get_reporting_balance_summary', array( 'query' => $query ) );

		return $this->response;
	}

	/**
	 * Get transactions.
	 *
	 * @param array<string,mixed> $query Query params.
	 * @return array<string,mixed>
	 */
	public function get_transactions( array $query = array() ): array {
		$this->record( 'get_transactions', array( 'query' => $query ) );

		return $this->response;
	}

	/**
	 * Get transactions summary.
	 *
	 * @param array<string,mixed> $filters Filters.
	 * @param string|null         $deposit_id Deposit ID.
	 * @return array<string,mixed>
	 */
	public function get_transactions_summary( array $filters = array(), ?string $deposit_id = null ): array {
		$this->record(
			'get_transactions_summary',
			array(
				'filters'    => $filters,
				'deposit_id' => $deposit_id,
			)
		);

		return $this->response;
	}

	/**
	 * Export transactions.
	 *
	 * @param array<string,mixed> $filters Filters.
	 * @param string              $user_email User email.
	 * @param string|null         $deposit_id Deposit ID.
	 * @param string|null         $locale Locale.
	 * @return array<string,mixed>
	 */
	public function get_transactions_export( array $filters = array(), string $user_email = '', ?string $deposit_id = null, ?string $locale = null ): array {
		$this->record(
			'get_transactions_export',
			array(
				'filters'    => $filters,
				'user_email' => $user_email,
				'deposit_id' => $deposit_id,
				'locale'     => $locale,
			)
		);

		return $this->response;
	}

	/**
	 * Get transactions export URL.
	 *
	 * @param string $export_id Export ID.
	 * @return array<string,mixed>
	 */
	public function get_transactions_export_url( string $export_id ): array {
		$this->record( 'get_transactions_export_url', array( 'export_id' => $export_id ) );

		return $this->response;
	}

	/**
	 * Record a call.
	 *
	 * @param string              $method Method.
	 * @param array<string,mixed> $data   Call data.
	 * @throws WooPaymentsApiException When configured.
	 */
	private function record( string $method, array $data ): void {
		$this->last_call = array_merge( array( 'method' => $method ), $data );

		if ( null !== $this->exception ) {
			throw $this->exception;
		}
	}
}
