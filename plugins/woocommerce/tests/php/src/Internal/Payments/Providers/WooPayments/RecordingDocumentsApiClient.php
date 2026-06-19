<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Payments\Providers\WooPayments;

use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\Api\WooPaymentsApiClient;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\Api\WooPaymentsApiException;

/**
 * Recording API client for native WooPayments Documents REST tests.
 */
class RecordingDocumentsApiClient extends WooPaymentsApiClient {

	/**
	 * Last documents query.
	 *
	 * @var array<string,mixed>|null
	 */
	public ?array $last_documents_query = null;

	/**
	 * Last documents summary query.
	 *
	 * @var array<string,mixed>|null
	 */
	public ?array $last_documents_summary_query = null;

	/**
	 * Last document ID.
	 *
	 * @var string|null
	 */
	public ?string $last_document_id = null;

	/**
	 * Last VAT number.
	 *
	 * @var string|null
	 */
	public ?string $last_vat_number = null;

	/**
	 * Last saved VAT details.
	 *
	 * @var array<string,string|null>|null
	 */
	public ?array $last_saved_vat_details = null;

	/**
	 * Documents list response.
	 *
	 * @var array<string,mixed>
	 */
	public array $documents_response = array(
		'data'        => array(),
		'total_count' => 0,
	);

	/**
	 * Documents summary response.
	 *
	 * @var array<string,mixed>
	 */
	public array $documents_summary_response = array();

	/**
	 * Raw document response.
	 *
	 * @var array<string,mixed>
	 */
	public array $document_response = array(
		'response' => array(
			'code'    => 200,
			'message' => 'OK',
		),
		'headers'  => array(
			'content-type'        => 'application/pdf',
			'content-disposition' => 'attachment; filename="document.pdf"',
		),
		'body'     => '%PDF document',
	);

	/**
	 * VAT validation response.
	 *
	 * @var array<string,mixed>
	 */
	public array $validate_vat_response = array(
		'is_valid' => true,
	);

	/**
	 * VAT save response.
	 *
	 * @var array<string,mixed>
	 */
	public array $save_vat_response = array(
		'success' => true,
	);

	/**
	 * Optional exception thrown by the next call.
	 *
	 * @var WooPaymentsApiException|null
	 */
	public ?WooPaymentsApiException $exception = null;

	/**
	 * Get documents.
	 *
	 * @param array<string,mixed> $query Query params.
	 * @return array<string,mixed>
	 */
	public function get_documents( array $query = array() ): array {
		$this->last_documents_query = $query;
		$this->throw_if_configured();

		return $this->documents_response;
	}

	/**
	 * Get documents summary.
	 *
	 * @param array<string,mixed> $query Query params.
	 * @return array<string,mixed>
	 */
	public function get_documents_summary( array $query = array() ): array {
		$this->last_documents_summary_query = $query;
		$this->throw_if_configured();

		return $this->documents_summary_response;
	}

	/**
	 * Get a document.
	 *
	 * @param string $document_id Document ID.
	 * @return array<string,mixed>
	 */
	public function get_document( string $document_id ): array {
		$this->last_document_id = $document_id;
		$this->throw_if_configured();

		return $this->document_response;
	}

	/**
	 * Validate VAT number.
	 *
	 * @param string $vat_number VAT number.
	 * @return array<string,mixed>
	 */
	public function validate_vat( string $vat_number ): array {
		$this->last_vat_number = $vat_number;
		$this->throw_if_configured();

		return $this->validate_vat_response;
	}

	/**
	 * Save VAT details.
	 *
	 * @param string|null $vat_number VAT number.
	 * @param string      $name       Name.
	 * @param string      $address    Address.
	 * @return array<string,mixed>
	 */
	public function save_vat_details( ?string $vat_number, string $name, string $address ): array {
		$this->last_saved_vat_details = array(
			'vat_number' => $vat_number,
			'name'       => $name,
			'address'    => $address,
		);
		$this->throw_if_configured();

		return $this->save_vat_response;
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
