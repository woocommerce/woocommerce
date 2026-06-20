<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Payments\Providers\WooPayments;

use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\Api\WooPaymentsApiException;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\Api\WooPaymentsApiClient;
use WP_REST_Request;

/**
 * Recording API client for native WooPayments settings tests.
 */
class RecordingSettingsApiClient extends WooPaymentsApiClient {

	/**
	 * Last account settings payload.
	 *
	 * @var array<string,mixed>|null
	 */
	public ?array $last_account_settings = null;

	/**
	 * Last fraud ruleset payload.
	 *
	 * @var array<int|string,mixed>|null
	 */
	public ?array $last_fraud_ruleset = null;

	/**
	 * Latest fraud ruleset response.
	 *
	 * @var array<string,mixed>|null
	 */
	public ?array $latest_fraud_ruleset_response = null;

	/**
	 * Latest fraud ruleset exception.
	 *
	 * @var WooPaymentsApiException|null
	 */
	public ?WooPaymentsApiException $latest_fraud_ruleset_exception = null;

	/**
	 * Latest fraud ruleset request count.
	 *
	 * @var int
	 */
	public int $latest_fraud_ruleset_requests = 0;

	/**
	 * Last upload request.
	 *
	 * @var WP_REST_Request|null
	 */
	public ?WP_REST_Request $last_upload_request = null;

	/**
	 * Last fetched file ID.
	 *
	 * @var string|null
	 */
	public ?string $last_file_id = null;

	/**
	 * Whether the last fetched file used account scope.
	 *
	 * @var bool|null
	 */
	public ?bool $last_file_as_account = null;

	/**
	 * Last fetched file contents ID.
	 *
	 * @var string|null
	 */
	public ?string $last_file_contents_id = null;

	/**
	 * Whether the last fetched file contents used account scope.
	 *
	 * @var bool|null
	 */
	public ?bool $last_file_contents_as_account = null;

	/**
	 * Capability requests.
	 *
	 * @var array<int,array<string,mixed>>
	 */
	public array $capability_requests = array();

	/**
	 * File upload response.
	 *
	 * @var array<string,mixed>
	 */
	public array $upload_response = array( 'id' => 'file_test_logo' );

	/**
	 * File details response.
	 *
	 * @var array<string,mixed>
	 */
	public array $file_response = array(
		'id'      => 'file_test_logo',
		'purpose' => 'business_logo',
	);

	/**
	 * File contents response.
	 *
	 * @var array<string,mixed>
	 */
	public array $file_contents_response = array(
		'content_type' => 'image/png',
		'file_content' => 'TE9HTw==',
	);

	/**
	 * Update account settings.
	 *
	 * @param array<string,mixed> $account_settings Account settings.
	 * @return array<string,mixed>
	 */
	public function update_account( array $account_settings ): array {
		$this->last_account_settings = $account_settings;

		return $account_settings;
	}

	/**
	 * Request a payment method capability.
	 *
	 * @param string $capability_id Capability ID.
	 * @param bool   $requested Whether the capability is requested.
	 * @return array<string,mixed>
	 */
	public function request_capability( string $capability_id, bool $requested ): array {
		$this->capability_requests[] = array(
			'capability_id' => $capability_id,
			'requested'     => $requested,
		);

		return array( 'status' => 'active' );
	}

	/**
	 * Save fraud ruleset.
	 *
	 * @param array<int|string,mixed> $ruleset_config Ruleset config.
	 * @return array<string,mixed>
	 */
	public function save_fraud_ruleset( array $ruleset_config ): array {
		$this->last_fraud_ruleset = $ruleset_config;

		return array( 'success' => true );
	}

	/**
	 * Get latest fraud ruleset.
	 *
	 * @return array<string,mixed>
	 * @throws WooPaymentsApiException When the test fixture is configured to fail.
	 */
	public function get_latest_fraud_ruleset(): array {
		++$this->latest_fraud_ruleset_requests;

		if ( $this->latest_fraud_ruleset_exception instanceof WooPaymentsApiException ) {
			throw $this->latest_fraud_ruleset_exception;
		}

		return $this->latest_fraud_ruleset_response ?? array();
	}

	/**
	 * Upload settings file.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return array<string,mixed>
	 */
	public function upload_file( WP_REST_Request $request ): array {
		$this->last_upload_request = $request;

		return $this->upload_response;
	}

	/**
	 * Get settings file details.
	 *
	 * @param string $file_id    Provider file ID.
	 * @param bool   $as_account Whether to fetch the file as the connected account.
	 * @return array<string,mixed>
	 */
	public function get_file( string $file_id, bool $as_account = true ): array {
		$this->last_file_id         = $file_id;
		$this->last_file_as_account = $as_account;

		return $this->file_response;
	}

	/**
	 * Get settings file contents.
	 *
	 * @param string $file_id    Provider file ID.
	 * @param bool   $as_account Whether to fetch the file as the connected account.
	 * @return array<string,mixed>
	 */
	public function get_file_contents( string $file_id, bool $as_account = true ): array {
		$this->last_file_contents_id         = $file_id;
		$this->last_file_contents_as_account = $as_account;

		return $this->file_contents_response;
	}
}
