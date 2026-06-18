<?php
/**
 * WooPaymentsDepositsListRequest class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Payments\Providers\WooPayments;

use WP_REST_Request;

/**
 * Compatibility request object for the preserved WooPayments deposits list filter.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native payments runtime.
 */
class WooPaymentsDepositsListRequest {

	private const DEFAULT_PARAMS = array(
		'page'      => 0,
		'pagesize'  => 25,
		'sort'      => 'created',
		'direction' => 'desc',
		'limit'     => 100,
	);

	/**
	 * Request params.
	 *
	 * @var array<string,mixed>
	 */
	private array $params = array();

	/**
	 * Register the legacy request FQCN as an alias when the WooPayments extension is absent.
	 */
	public static function register_legacy_alias(): void {
		$legacy_classes = array(
			'WCPay\Core\Server\Request',
			'WCPay\Core\Server\Request\Paginated',
			'WCPay\Core\Server\Request\List_Deposits',
		);

		foreach ( $legacy_classes as $legacy_class ) {
			if ( ! class_exists( $legacy_class, false ) ) {
				class_alias( self::class, $legacy_class );
			}
		}
	}

	/**
	 * Create a request from REST request data.
	 *
	 * @param WP_REST_Request $request REST request.
	 * @phpstan-param WP_REST_Request<array<string,mixed>> $request
	 * @return self
	 */
	public static function from_rest_request( WP_REST_Request $request ): self {
		$deposits_request = new self();
		$deposits_request->set_page( (int) $request->get_param( 'page' ) );
		$deposits_request->set_page_size( (int) ( $request->get_param( 'pagesize' ) ?? 25 ) );

		$sort = $request->get_param( 'sort' );
		if ( null !== $sort ) {
			$deposits_request->set_sort_by( (string) $sort );
		}

		$direction = $request->get_param( 'direction' );
		if ( null !== $direction ) {
			$deposits_request->set_sort_direction( (string) $direction );
		}

		$date_between = $request->get_param( 'date_between' );
		$deposits_request->set_filters(
			array(
				'match'             => $request->get_param( 'match' ),
				'store_currency_is' => $request->get_param( 'store_currency_is' ),
				'date_before'       => $request->get_param( 'date_before' ),
				'date_after'        => $request->get_param( 'date_after' ),
				'date_between'      => null === $date_between ? null : (array) $date_between,
				'status_is'         => $request->get_param( 'status_is' ),
				'status_is_not'     => $request->get_param( 'status_is_not' ),
			)
		);

		return $deposits_request;
	}

	/**
	 * Set filters.
	 *
	 * @param array<string,mixed> $filters Filters to set.
	 */
	public function set_filters( array $filters ): void {
		foreach ( $filters as $key => $value ) {
			if ( null === $value ) {
				continue;
			}

			$setter = 'set_' . $key;
			if ( method_exists( $this, $setter ) ) {
				$this->$setter( $value );
			} else {
				$this->set_param( $key, $value );
			}
		}
	}

	/**
	 * Set page.
	 *
	 * @param int $page Page.
	 */
	public function set_page( int $page ): void {
		$this->set_param( 'page', $page );
	}

	/**
	 * Set page size.
	 *
	 * @param int $page_size Page size.
	 */
	public function set_page_size( int $page_size ): void {
		$this->set_param( 'pagesize', $page_size );
	}

	/**
	 * Set sort field.
	 *
	 * @param string $sort Sort field.
	 */
	public function set_sort_by( string $sort ): void {
		$this->set_param( 'sort', $sort );
	}

	/**
	 * Set sort direction.
	 *
	 * @param string $direction Sort direction.
	 */
	public function set_sort_direction( string $direction ): void {
		$this->set_param( 'direction', $direction );
	}

	/**
	 * Set match filter.
	 *
	 * @param string $match_type Match type.
	 */
	public function set_match( string $match_type ): void {
		$this->set_param( 'match', $match_type );
	}

	/**
	 * Set store currency filter.
	 *
	 * @param string $store_currency Store currency.
	 */
	public function set_store_currency_is( string $store_currency ): void {
		$this->set_param( 'store_currency_is', $store_currency );
	}

	/**
	 * Set status filter.
	 *
	 * @param string $status Status.
	 */
	public function set_status_is( string $status ): void {
		$this->set_param( 'status_is', $status );
	}

	/**
	 * Set excluded status filter.
	 *
	 * @param string $status Status.
	 */
	public function set_status_is_not( string $status ): void {
		$this->set_param( 'status_is_not', $status );
	}

	/**
	 * Set date after filter.
	 *
	 * @param string $date_after Date after.
	 */
	public function set_date_after( string $date_after ): void {
		$this->set_param( 'date_after', $date_after );
	}

	/**
	 * Set date before filter.
	 *
	 * @param string $date_before Date before.
	 */
	public function set_date_before( string $date_before ): void {
		$this->set_param( 'date_before', $date_before );
	}

	/**
	 * Set date between filter.
	 *
	 * @param array<int,string> $date_between Date range.
	 */
	public function set_date_between( array $date_between ): void {
		if ( ! empty( $date_between ) ) {
			$this->set_param( 'date_between', $date_between );
		}
	}

	/**
	 * Returns the request's API.
	 *
	 * @return string
	 */
	public function get_api(): string {
		return 'deposits';
	}

	/**
	 * Returns the request's HTTP method.
	 *
	 * @return string
	 */
	public function get_method(): string {
		return 'GET';
	}

	/**
	 * Whether this request is site-specific.
	 *
	 * @return bool
	 */
	public function is_site_specific(): bool {
		return true;
	}

	/**
	 * Whether this request should use the user token.
	 *
	 * @return bool
	 */
	public function should_use_user_token(): bool {
		return false;
	}

	/**
	 * Whether this request should return the raw response.
	 *
	 * @return bool
	 */
	public function should_return_raw_response(): bool {
		return false;
	}

	/**
	 * Get a request param by key.
	 *
	 * @param string $key Param key.
	 * @return mixed
	 */
	public function get_param( string $key ) {
		$params = $this->get_params();

		if ( array_key_exists( $key, $params ) ) {
			return $params[ $key ];
		}

		return null;
	}

	/**
	 * Get request params.
	 *
	 * @return array<string,mixed>
	 */
	public function get_params(): array {
		$params = array_merge( self::DEFAULT_PARAMS, $this->params );

		foreach ( $params as $key => $value ) {
			if ( true === $value ) {
				$params[ $key ] = 'true';
			}
		}

		return $params;
	}

	/**
	 * Set a request param.
	 *
	 * @param string $key   Param key.
	 * @param mixed  $value Param value.
	 */
	private function set_param( string $key, $value ): void {
		$this->params[ $key ] = $value;
	}
}
