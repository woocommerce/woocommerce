<?php
/**
 * Getting a token for the GitHub App, from a laptop.
 *
 * Repo-agnostic apart from the client ID, which is passed in.
 */

namespace LocalCi;

/**
 * OAuth device flow, and the small amount of token bookkeeping it implies.
 *
 * The App's private key is never involved. Shipping it would let anyone holding
 * it forge a receipt for any job on any commit, which is the one thing this
 * design cannot allow. Device flow needs only the client ID, which is public,
 * so nothing secret is distributed and nothing secret sits in the repository.
 *
 * The token that comes back is user-to-server: it acts as the App, on behalf of
 * the person who authorised it. That matters twice over — only a GitHub App may
 * create a check run at all, and the human stays recoverable because their login
 * is written into the check run rather than inferred.
 */
final class DeviceFlow {

	private const DEVICE_CODE_URL  = 'https://github.com/login/device/code';
	private const ACCESS_TOKEN_URL = 'https://github.com/login/oauth/access_token';

	/**
	 * Treat a token as expired this many seconds early, so it cannot lapse
	 * between the check and the call that uses it.
	 */
	private const EXPIRY_MARGIN = 120;

	/**
	 * The App's client ID. Public by design.
	 *
	 * @var string
	 */
	private $client_id;

	/**
	 * @param string $client_id The App's client ID.
	 */
	public function __construct( string $client_id ) {
		$this->client_id = $client_id;
	}

	/**
	 * A usable access token: from disk, refreshed, or newly authorised.
	 *
	 * @return string|null Null when the person declined or the wait timed out.
	 */
	public function token(): ?string {
		$stored = $this->stored();

		if ( null !== $stored && time() < ( $stored['expires_at'] ?? 0 ) - self::EXPIRY_MARGIN ) {
			return $stored['access_token'];
		}

		if ( null !== $stored && '' !== ( $stored['refresh_token'] ?? '' ) ) {
			$refreshed = $this->refresh( $stored['refresh_token'] );

			if ( null !== $refreshed ) {
				return $refreshed;
			}
		}

		return $this->authorise();
	}

	/**
	 * Forget the stored token, so the next run authorises again.
	 */
	public function forget(): void {
		$path = self::token_path();

		if ( is_file( $path ) ) {
			@unlink( $path );
		}
	}

	/**
	 * Ask the person to authorise this device, then wait for them to do it.
	 *
	 * @return string|null Null when they declined or the code expired.
	 */
	private function authorise(): ?string {
		$start = self::post( self::DEVICE_CODE_URL, array( 'client_id' => $this->client_id ) );

		if ( ! isset( $start['user_code'], $start['device_code'] ) ) {
			return null;
		}

		Output::warn( 'This machine is not authorised yet.', 1 );
		Output::detail( sprintf( 'Open %s', $start['verification_uri'] ), 1 );
		Output::detail( sprintf( 'Enter code %s', Output::paint( $start['user_code'], 'bold' ) ), 1 );

		$interval = max( 5, (int) ( $start['interval'] ?? 5 ) );
		$deadline = time() + (int) ( $start['expires_in'] ?? 900 );

		while ( time() < $deadline ) {
			sleep( $interval );

			$response = self::post(
				self::ACCESS_TOKEN_URL,
				array(
					'client_id'   => $this->client_id,
					'device_code' => $start['device_code'],
					'grant_type'  => 'urn:ietf:params:oauth:grant-type:device_code',
				)
			);

			if ( isset( $response['access_token'] ) ) {
				$this->store( $response );

				return $response['access_token'];
			}

			$error = (string) ( $response['error'] ?? '' );

			if ( 'authorization_pending' === $error ) {
				continue;
			}

			// GitHub asks for a wider gap when polled too eagerly; anything else
			// is a refusal or an expiry, and waiting will not help.
			if ( 'slow_down' === $error ) {
				$interval += 5;
				continue;
			}

			return null;
		}

		return null;
	}

	/**
	 * Exchange a refresh token for a new access token.
	 *
	 * @param string $refresh_token Stored refresh token.
	 *
	 * @return string|null Null when the refresh token is spent or revoked.
	 */
	private function refresh( string $refresh_token ): ?string {
		$response = self::post(
			self::ACCESS_TOKEN_URL,
			array(
				'client_id'     => $this->client_id,
				'grant_type'    => 'refresh_token',
				'refresh_token' => $refresh_token,
			)
		);

		if ( ! isset( $response['access_token'] ) ) {
			return null;
		}

		$this->store( $response );

		return $response['access_token'];
	}

	/**
	 * The token on disk, or null when there is none worth reading.
	 *
	 * @return array<string, mixed>|null
	 */
	private function stored(): ?array {
		$path = self::token_path();

		if ( ! is_readable( $path ) ) {
			return null;
		}

		$decoded = json_decode( (string) file_get_contents( $path ), true );

		return isset( $decoded['access_token'] ) ? $decoded : null;
	}

	/**
	 * Write the token out, readable only by this user.
	 *
	 * @param array<string, mixed> $response Token response from GitHub.
	 */
	private function store( array $response ): void {
		$path      = self::token_path();
		$directory = dirname( $path );

		if ( ! is_dir( $directory ) && ! @mkdir( $directory, 0700, true ) ) {
			return;
		}

		$response['expires_at'] = time() + (int) ( $response['expires_in'] ?? 28800 );

		// Created empty first so the permissions are right before anything
		// sensitive is written into it.
		@touch( $path );
		@chmod( $path, 0600 );
		@file_put_contents( $path, (string) json_encode( $response ) );
	}

	/**
	 * Where the token lives. Outside the repository, so it cannot be committed.
	 */
	private static function token_path(): string {
		$base = (string) ( getenv( 'XDG_CONFIG_HOME' ) ?: getenv( 'HOME' ) . '/.config' );

		return $base . '/woo-local-ci/token.json';
	}

	/**
	 * A form-encoded POST that asks for JSON back.
	 *
	 * @param string               $url    Endpoint.
	 * @param array<string,string> $fields Body.
	 *
	 * @return array<string, mixed>
	 */
	private static function post( string $url, array $fields ): array {
		$curl = curl_init( $url );

		curl_setopt_array(
			$curl,
			array(
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_POST           => true,
				CURLOPT_POSTFIELDS     => http_build_query( $fields ),
				CURLOPT_CONNECTTIMEOUT => 10,
				CURLOPT_TIMEOUT        => 30,
				CURLOPT_USERAGENT      => 'local-ci',
				CURLOPT_HTTPHEADER     => array( 'Accept: application/json' ),
			)
		);

		$raw = curl_exec( $curl );
		curl_close( $curl );

		$decoded = json_decode( is_string( $raw ) ? $raw : '', true );

		return is_array( $decoded ) ? $decoded : array();
	}
}
