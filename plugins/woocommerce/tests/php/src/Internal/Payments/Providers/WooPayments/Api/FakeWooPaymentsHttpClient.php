<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Payments\Providers\WooPayments\Api;

use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\Api\WooPaymentsHttpClient;

/**
 * Fake WooPayments HTTP client for provider transport tests.
 */
class FakeWooPaymentsHttpClient extends WooPaymentsHttpClient {

	/**
	 * Whether the site is connected.
	 *
	 * @var bool
	 */
	public bool $connected = true;

	/**
	 * Blog ID returned by the client.
	 *
	 * @var int|null
	 */
	public ?int $blog_id = 123;

	/**
	 * Response returned by the client.
	 *
	 * @var mixed
	 */
	public $response = null;

	/**
	 * Last HTTP method.
	 *
	 * @var string
	 */
	public string $last_method = '';

	/**
	 * Last request path.
	 *
	 * @var string
	 */
	public string $last_path = '';

	/**
	 * Last request headers.
	 *
	 * @var array<string,string>
	 */
	public array $last_headers = array();

	/**
	 * Last request body.
	 *
	 * @var string|null
	 */
	public ?string $last_body = null;

	/**
	 * Last timeout.
	 *
	 * @var int
	 */
	public int $last_timeout = 0;

	/**
	 * Whether the last request used the connection-owner user token.
	 *
	 * @var bool
	 */
	public bool $last_use_user_token = false;

	/**
	 * Tell whether the client is connected.
	 *
	 * @return bool
	 */
	public function is_connected(): bool {
		return $this->connected;
	}

	/**
	 * Get the connected blog ID.
	 *
	 * @return int|null
	 */
	public function get_blog_id(): ?int {
		return $this->blog_id;
	}

	/**
	 * Record and return a fake transport request.
	 *
	 * @param string      $method  HTTP method.
	 * @param string      $path    WPCOM path.
	 * @param string[]    $headers Request headers.
	 * @param string|null $body    Request body.
	 * @param int         $timeout        Request timeout.
	 * @param bool        $use_user_token Whether to sign with the connection-owner user token.
	 * @return mixed
	 */
	public function request( string $method, string $path, array $headers = array(), ?string $body = null, int $timeout = 70, bool $use_user_token = false ) {
		$this->last_method         = $method;
		$this->last_path           = $path;
		$this->last_headers        = $headers;
		$this->last_body           = $body;
		$this->last_timeout        = $timeout;
		$this->last_use_user_token = $use_user_token;

		return $this->response;
	}
}
