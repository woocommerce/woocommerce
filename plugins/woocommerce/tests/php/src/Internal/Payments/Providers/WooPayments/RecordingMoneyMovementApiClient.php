<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Payments\Providers\WooPayments;

use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\Api\WooPaymentsApiClient;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\Api\WooPaymentsApiException;

/**
 * Recording API client for native WooPayments money movement REST tests.
 */
class RecordingMoneyMovementApiClient extends WooPaymentsApiClient {

	/**
	 * Optional exception thrown by the next call.
	 *
	 * @var WooPaymentsApiException|null
	 */
	public ?WooPaymentsApiException $exception = null;

	/**
	 * Recorded calls.
	 *
	 * @var array<string,mixed>
	 */
	public array $last_call = array();

	/**
	 * Recorded calls in order.
	 *
	 * @var array<int,array<string,mixed>>
	 */
	public array $calls = array();

	/**
	 * Generic response.
	 *
	 * @var array<string,mixed>
	 */
	public array $response = array();

	/**
	 * Method-specific responses.
	 *
	 * @var array<string,array<string,mixed>>
	 */
	public array $responses = array();

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
	 * Get transaction.
	 *
	 * @param string $transaction_id Transaction ID.
	 * @return array<string,mixed>
	 */
	public function get_transaction( string $transaction_id ): array {
		$this->record( 'get_transaction', array( 'transaction_id' => $transaction_id ) );

		return $this->response;
	}

	/**
	 * Get authorizations.
	 *
	 * @param array<string,mixed> $query                  Query params.
	 * @param bool                $preserve_legacy_filter Whether to preserve legacy request filters.
	 * @return array<string,mixed>
	 */
	public function get_authorizations( array $query = array(), bool $preserve_legacy_filter = true ): array {
		$this->record(
			'get_authorizations',
			array(
				'query'                  => $query,
				'preserve_legacy_filter' => $preserve_legacy_filter,
			)
		);

		return $this->response;
	}

	/**
	 * Get authorizations summary.
	 *
	 * @param array<string,mixed> $query Query params.
	 * @return array<string,mixed>
	 */
	public function get_authorizations_summary( array $query = array() ): array {
		$this->record( 'get_authorizations_summary', array( 'query' => $query ) );

		return $this->response;
	}

	/**
	 * Get authorization.
	 *
	 * @param string $payment_intent_id Payment intent ID.
	 * @return array<string,mixed>
	 */
	public function get_authorization( string $payment_intent_id ): array {
		$this->record( 'get_authorization', array( 'payment_intent_id' => $payment_intent_id ) );

		return $this->response;
	}

	/**
	 * Get charge.
	 *
	 * @param string $charge_id Charge ID.
	 * @return array<string,mixed>
	 */
	public function get_charge( string $charge_id ): array {
		$this->record( 'get_charge', array( 'charge_id' => $charge_id ) );

		return $this->response;
	}

	/**
	 * Get payment intent.
	 *
	 * @param string $intent_id Intent ID.
	 * @return array<string,mixed>
	 */
	public function get_payment_intention( string $intent_id ): array {
		$this->record( 'get_payment_intention', array( 'intent_id' => $intent_id ) );

		return $this->get_response( 'get_payment_intention' );
	}

	/**
	 * Get timeline events.
	 *
	 * @param string $id Payment intent ID or order ID.
	 * @return array<string,mixed>
	 */
	public function get_timeline( string $id ): array {
		$this->record( 'get_timeline', array( 'intention_id' => $id ) );

		return $this->get_response( 'get_timeline' );
	}

	/**
	 * Search transactions.
	 *
	 * @param string $search_term Search term.
	 * @return array<int,array<string,string>>
	 */
	public function get_transactions_search_autocomplete( string $search_term ): array {
		$this->record( 'get_transactions_search_autocomplete', array( 'search_term' => $search_term ) );

		return array( array( 'label' => 'Ada Lovelace (ada@example.com)' ) );
	}

	/**
	 * Get fraud outcomes.
	 *
	 * @param array<string,mixed> $query Query params.
	 * @return array<string|int,mixed>
	 */
	public function get_fraud_outcomes( array $query = array() ): array {
		$this->record( 'get_fraud_outcomes', array( 'query' => $query ) );

		$status = isset( $query['status'] ) && is_scalar( $query['status'] ) ? (string) $query['status'] : '';
		if ( ! in_array( $status, array( 'allow', 'block', 'review' ), true ) ) {
			throw new WooPaymentsApiException( 'Invalid fraud outcome status provided.', 'invalid_fraud_outcome_status', 400 );
		}

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
	 * Get disputes.
	 *
	 * @param array<string,mixed> $filters Filters.
	 * @return array<string,mixed>
	 */
	public function get_disputes( array $filters = array() ): array {
		$this->record( 'get_disputes', array( 'filters' => $filters ) );

		return $this->response;
	}

	/**
	 * Get disputes summary.
	 *
	 * @param array<string,mixed> $filters Filters.
	 * @return array<string,mixed>
	 */
	public function get_disputes_summary( array $filters = array() ): array {
		$this->record( 'get_disputes_summary', array( 'filters' => $filters ) );

		return $this->response;
	}

	/**
	 * Get dispute.
	 *
	 * @param string $dispute_id Dispute ID.
	 * @return array<string,mixed>
	 */
	public function get_dispute( string $dispute_id ): array {
		$this->record( 'get_dispute', array( 'dispute_id' => $dispute_id ) );

		return $this->response;
	}

	/**
	 * Update dispute.
	 *
	 * @param string              $dispute_id Dispute ID.
	 * @param array<string,mixed> $evidence Evidence.
	 * @param bool                $submit Submit.
	 * @param array<string,mixed> $metadata Metadata.
	 * @return array<string,mixed>
	 */
	public function update_dispute( string $dispute_id, array $evidence, bool $submit, array $metadata ): array {
		$this->record(
			'update_dispute',
			array(
				'dispute_id' => $dispute_id,
				'evidence'   => $evidence,
				'submit'     => $submit,
				'metadata'   => $metadata,
			)
		);

		return $this->response;
	}

	/**
	 * Close dispute.
	 *
	 * @param string $dispute_id Dispute ID.
	 * @return array<string,mixed>
	 */
	public function close_dispute( string $dispute_id ): array {
		$this->record( 'close_dispute', array( 'dispute_id' => $dispute_id ) );

		return $this->response;
	}

	/**
	 * Export disputes.
	 *
	 * @param array<string,mixed> $filters Filters.
	 * @param string              $user_email User email.
	 * @param string|null         $locale Locale.
	 * @return array<string,mixed>
	 */
	public function get_disputes_export( array $filters = array(), string $user_email = '', ?string $locale = null ): array {
		$this->record(
			'get_disputes_export',
			array(
				'filters'    => $filters,
				'user_email' => $user_email,
				'locale'     => $locale,
			)
		);

		return $this->response;
	}

	/**
	 * Get disputes export URL.
	 *
	 * @param string $export_id Export ID.
	 * @return array<string,mixed>
	 */
	public function get_disputes_export_url( string $export_id ): array {
		$this->record( 'get_disputes_export_url', array( 'export_id' => $export_id ) );

		return $this->response;
	}

	/**
	 * Record a call.
	 *
	 * @param string              $method Method.
	 * @param array<string,mixed> $data Data.
	 * @throws WooPaymentsApiException When configured.
	 */
	private function record( string $method, array $data ): void {
		$this->last_call = array_merge( array( 'method' => $method ), $data );
		$this->calls[]   = $this->last_call;

		if ( null !== $this->exception ) {
			throw $this->exception;
		}
	}

	/**
	 * Get the configured response for a method.
	 *
	 * @param string $method Method.
	 * @return array<string,mixed>
	 */
	private function get_response( string $method ): array {
		return $this->responses[ $method ] ?? $this->response;
	}
}
