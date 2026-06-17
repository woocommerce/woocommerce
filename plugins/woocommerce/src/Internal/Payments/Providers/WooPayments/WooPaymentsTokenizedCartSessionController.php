<?php
/**
 * WooPaymentsTokenizedCartSessionController class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Payments\Providers\WooPayments;

use Automattic\WooCommerce\Internal\Payments\NativePaymentsRuntimeArbiter;
use Automattic\WooCommerce\Internal\RegisterHooksInterface;
use Automattic\WooCommerce\StoreApi\Utilities\JsonWebToken;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Native WooPayments product-page tokenized cart session hooks.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native payments runtime.
 */
class WooPaymentsTokenizedCartSessionController implements RegisterHooksInterface {

	private const SESSION_NONCE_HEADER = 'HTTP_X_WOOPAYMENTS_TOKENIZED_CART_SESSION_NONCE';

	private const SESSION_HEADER = 'HTTP_X_WOOPAYMENTS_TOKENIZED_CART_SESSION';

	private const RESPONSE_SESSION_HEADER = 'X-WooPayments-Tokenized-Cart-Session';

	private const EPHEMERAL_CART_HEADER = 'HTTP_X_WOOPAYMENTS_TOKENIZED_CART_IS_EPHEMERAL_CART';

	private const SESSION_NONCE_ACTION = 'woopayments_tokenized_cart_session_nonce';

	private const TOKEN_ISSUER = 'woopayments/product-page';

	private const RETURN_URL_MARKER = 'woopayments-custom-session';

	private const TOKENIZED_CART_NONCE_ACTION = 'woopayments_tokenized_cart_nonce';

	private const TOKENIZED_CART_HEADER = 'X-WooPayments-Tokenized-Cart';

	private const TOKENIZED_CART_NONCE_HEADER = 'X-WooPayments-Tokenized-Cart-Nonce';

	private const CART_SESSION_KEYS = array(
		'cart',
		'cart_totals',
		'applied_coupons',
		'coupon_discount_totals',
		'coupon_discount_tax_totals',
		'removed_cart_contents',
		'chosen_shipping_methods',
		'shipping_method_counts',
		'shipping_for_package_0',
		'shipping_for_package_1',
		'shipping_for_package_2',
		'shipping_for_package_3',
		'shipping_for_package_4',
	);

	private const HONG_KONG_REGION_CANDIDATES = array(
		'HONG KONG'       => array(
			'hong kong',
			'hongkong',
			'hong kong island',
			'港島',
			'central and western',
			'中西區',
			'kennedy town',
			'shek tong tsui',
			'sai ying pun',
			'sheung wan',
			'central',
			'admiralty',
			'mid-levels',
			'peak',
			'堅尼地城',
			'石塘咀',
			'西營盤',
			'上環',
			'中環',
			'金鐘',
			'半山區',
			'山頂',
			'wan chai',
			'灣仔',
			'causeway bay',
			'happy valley',
			'tai hang',
			'so kon po',
			"jardine's lookout",
			'銅鑼灣',
			'跑馬地',
			'大坑',
			'掃桿埔',
			'渣甸山',
			'eastern',
			'東區',
			'tin hau',
			'braemar hill',
			'north point',
			'quarry bay',
			'sai wan ho',
			'shau kei wan',
			'chai wan',
			'siu sai wan',
			'天后',
			'寶馬山',
			'北角',
			'鰂魚涌',
			'西灣河',
			'筲箕灣',
			'柴灣',
			'小西灣',
			'southern',
			'南區',
			'pok fu lam',
			'aberdeen',
			'ap lei chau',
			'wong chuk hang',
			'shouson hill',
			'repulse bay',
			'chung hom kok',
			'stanley',
			'tai tam',
			'shek o',
			'薄扶林',
			'香港仔',
			'鴨脷洲',
			'黃竹坑',
			'壽臣山',
			'淺水灣',
			'舂磡角',
			'赤柱',
			'大潭',
			'石澳',
		),
		'KOWLOON'         => array(
			'kowloon',
			'九龍',
			'yau tsim mong',
			'油尖旺',
			'tsim sha tsui',
			'yau ma tei',
			'west kowloon reclamation',
			"king's park, mong kok",
			'tai kok tsui',
			'尖沙咀',
			'油麻地',
			'西九龍填海區',
			'京士柏',
			'mong kok',
			'旺角',
			'大角咀',
			'sham shui po',
			'深水埗',
			'mei foo',
			'lai chi kok',
			'cheung sha wan',
			'shek kip mei',
			'yau yat tsuen',
			'tai wo ping',
			'stonecutters island',
			'美孚',
			'荔枝角',
			'長沙灣',
			'石硤尾',
			'又一村',
			'大窩坪',
			'昂船洲',
			'kowloon city',
			'九龍城',
			'hung hom',
			'to kwa wan',
			'ma tau kok',
			'ma tau wai',
			'kai tak',
			'ho man tin',
			'kowloon tong',
			'beacon hill',
			'紅磡',
			'土瓜灣',
			'馬頭角',
			'馬頭圍',
			'啟德',
			'何文田',
			'九龍塘',
			'筆架山',
			'wong tai sin',
			'黃大仙',
			'san po kong',
			'tung tau',
			'wang tau hom',
			'lok fu',
			'diamond hill',
			'tsz wan shan',
			'ngau chi wan',
			'新蒲崗',
			'東頭',
			'橫頭磡',
			'樂富',
			'鑽石山',
			'慈雲山',
			'牛池灣',
			'kwun tong',
			'觀塘',
			'ping shek',
			'kowloon bay',
			'ngau tau kok',
			'jordan valley',
			'sau mau ping',
			'lam tin',
			'yau tong',
			'lei yue mun',
			'坪石',
			'九龍灣',
			'牛頭角',
			'佐敦谷',
			'秀茂坪',
			'藍田',
			'油塘',
			'鯉魚門',
		),
		'NEW TERRITORIES' => array(
			'new territories',
			'新界',
			'kwai tsing',
			'葵青',
			'kwai chung',
			'tsing yi',
			'葵涌',
			'青衣',
			'tsuen wan',
			'荃灣',
			'lei muk shue',
			'ting kau',
			'sham tseng',
			'tsing lung tau',
			'ma wan',
			'sunny bay',
			'梨木樹',
			'汀九',
			'深井',
			'青龍頭',
			'馬灣',
			'欣澳',
			'tuen mun',
			'屯門',
			'tai lam chung',
			'so kwun wat',
			'lam tei',
			'大欖涌',
			'掃管笏',
			'藍地',
			'yuen long',
			'元朗',
			'hung shui kiu',
			'ha tsuen',
			'lau fau shan',
			'tin shui wai',
			'san tin',
			'lok ma chau',
			'kam tin',
			'shek kong',
			'pat heung',
			'洪水橋',
			'廈村',
			'流浮山',
			'天水圍',
			'新田',
			'落馬洲',
			'錦田',
			'石崗',
			'八鄉',
			'north',
			'north district',
			'北區',
			'fanling',
			'luen wo hui',
			'sheung shui',
			'shek wu hui',
			'sha tau kok',
			'luk keng',
			'wu kau tang',
			'粉嶺',
			'聯和墟',
			'上水',
			'石湖墟',
			'沙頭角',
			'鹿頸',
			'烏蛟騰',
			'tai po',
			'大埔',
			'tai po market',
			'tai po kau',
			'tai mei tuk',
			'shuen wan',
			'cheung muk tau',
			'kei ling ha',
			'大埔墟',
			'大埔滘',
			'大尾篤',
			'船灣',
			'樟木頭',
			'企嶺下',
			'sha tin',
			'沙田',
			'tai wai',
			'fo tan',
			'ma liu shui',
			'wu kai sha',
			'ma on shan',
			'大圍',
			'火炭',
			'馬料水',
			'烏溪沙',
			'馬鞍山',
			'sai kung',
			'西貢',
			'clear water bay',
			'tai mong tsai',
			'tseung kwan o',
			'hang hau',
			'tiu keng leng',
			'ma yau tong',
			'清水灣',
			'大網仔',
			'將軍澳',
			'坑口',
			'調景嶺',
			'馬游塘',
			'islands',
			'離島',
			'cheung chau',
			'peng chau',
			'lantau island (including tung chung)',
			'lamma island',
			'長洲',
			'坪洲',
			'大嶼山(包括東涌)',
			'南丫島',
		),
	);

	/**
	 * Runtime owner arbiter.
	 *
	 * @var NativePaymentsRuntimeArbiter
	 */
	private NativePaymentsRuntimeArbiter $arbiter;

	/**
	 * Saved cart data for product-page order-received redirects.
	 *
	 * @var array<string,mixed>|null
	 */
	private ?array $saved_cart_data = null;

	/**
	 * Normalized postcodes allowed to bypass validation for the active tokenized update-customer request.
	 *
	 * @var array<string,array<string,bool>>
	 */
	private array $tokenized_postcode_validation_allowlist = array();

	/**
	 * Initialize the class instance.
	 *
	 * @internal
	 *
	 * @param NativePaymentsRuntimeArbiter $arbiter Runtime owner arbiter.
	 */
	final public function init( NativePaymentsRuntimeArbiter $arbiter ): void {
		$this->arbiter = $arbiter;
	}

	/**
	 * Register tokenized cart session hooks.
	 */
	public function register() {
		if ( ! $this->arbiter->should_native_register() ) {
			return;
		}

		if ( false === has_filter( 'woocommerce_session_handler', array( $this, 'handle_woocommerce_session_handler' ) ) ) {
			add_filter( 'woocommerce_session_handler', array( $this, 'handle_woocommerce_session_handler' ), 20 );
		}

		if ( false === has_filter( 'rest_pre_dispatch', array( $this, 'maybe_reject_invalid_tokenized_cart_session' ) ) ) {
			add_filter( 'rest_pre_dispatch', array( $this, 'maybe_reject_invalid_tokenized_cart_session' ), 10, 3 );
		}

		if ( $this->is_custom_session_order_received_request() ) {
			add_action( 'wp', array( $this, 'save_old_cart_data_for_restore' ), 1 );
			add_action( 'woocommerce_cart_emptied', array( $this, 'restore_old_cart_data' ) );
			add_filter( 'woocommerce_should_clear_cart_after_payment', array( $this, 'preserve_cart_after_tokenized_payment' ) );
		}
	}

	/**
	 * Swap the WooCommerce session handler for tokenized product-page Store API requests.
	 *
	 * @param string $session_handler Session handler class name.
	 * @return string
	 */
	public function handle_woocommerce_session_handler( string $session_handler ): string {
		if ( ! $this->is_store_api_request() || ! $this->has_valid_session_nonce() ) {
			return $session_handler;
		}

		$this->register_tokenized_session_filters();

		return WooPaymentsTokenizedCartSessionHandler::class;
	}

	/**
	 * Register request filters that keep tokenized sessions isolated.
	 */
	private function register_tokenized_session_filters(): void {
		if ( false === has_filter( 'woocommerce_persistent_cart_enabled', array( $this, 'disable_persistent_cart' ) ) ) {
			add_filter( 'woocommerce_persistent_cart_enabled', array( $this, 'disable_persistent_cart' ) );
		}

		if ( false === has_filter( 'woocommerce_get_return_url', array( $this, 'add_tokenized_cart_return_url_marker' ) ) ) {
			add_filter( 'woocommerce_get_return_url', array( $this, 'add_tokenized_cart_return_url_marker' ) );
		}

		if ( false === has_filter( 'rest_post_dispatch', array( $this, 'handle_store_api_response' ) ) ) {
			add_filter( 'rest_post_dispatch', array( $this, 'handle_store_api_response' ), 10, 3 );
		}
	}

	/**
	 * Disable persistent cart merges for isolated tokenized sessions.
	 *
	 * @return bool
	 */
	public function disable_persistent_cart(): bool {
		return false;
	}

	/**
	 * Add the custom-session marker to Store API order return URLs.
	 *
	 * @param string $return_url Return URL.
	 * @return string
	 */
	public function add_tokenized_cart_return_url_marker( string $return_url ): string {
		return add_query_arg( self::RETURN_URL_MARKER, '1', $return_url );
	}

	/**
	 * Maybe reject Store API requests that carry an invalid tokenized product cart session.
	 *
	 * @param mixed           $result  Response to replace the requested endpoint response with.
	 * @param mixed           $server  REST server.
	 * @param WP_REST_Request $request REST request.
	 * @phpstan-param WP_REST_Request<array<string,mixed>> $request
	 * @return mixed
	 */
	public function maybe_reject_invalid_tokenized_cart_session( $result, $server, WP_REST_Request $request ) {
		if ( null !== $result || ! $this->is_store_api_request() ) {
			return $result;
		}

		if ( ! $this->has_valid_session_nonce() ) {
			return $this->has_tokenized_cart_marker() ? $this->reject_invalid_tokenized_cart_session( $result, $server, $request ) : $result;
		}

		if ( ! $this->has_valid_incoming_session_token() ) {
			return $this->reject_invalid_tokenized_cart_session( $result, $server, $request );
		}

		$this->normalize_tokenized_cart_store_api_addresses( $request );

		return $result;
	}

	/**
	 * Allow redacted postcodes that were padded for tokenized express-checkout shipping lookups.
	 *
	 * @param bool   $valid    Whether the postcode is valid.
	 * @param string $postcode Postcode value.
	 * @param string $country  Country code.
	 * @return bool
	 */
	public function maybe_skip_postcode_validation( $valid, $postcode, $country ): bool {
		$country = strtoupper( (string) $country );
		if ( ! in_array( $country, array( 'GB', 'CA' ), true ) ) {
			return (bool) $valid;
		}

		$postcode = $this->normalize_postcode_for_validation_allowlist( (string) $postcode );

		return isset( $this->tokenized_postcode_validation_allowlist[ $country ][ $postcode ] ) ? true : (bool) $valid;
	}

	/**
	 * Clear tokenized postcode validation state after the current Store API dispatch.
	 *
	 * @internal
	 *
	 * @param mixed                $response REST response.
	 * @param mixed                $server   REST server.
	 * @param WP_REST_Request|null $request  REST request.
	 * @phpstan-param WP_REST_Request<array<string,mixed>>|null $request
	 * @return mixed
	 */
	public function clear_tokenized_postcode_validation( $response, $server = null, ?WP_REST_Request $request = null ) {
		remove_filter( 'woocommerce_validate_postcode', array( $this, 'maybe_skip_postcode_validation' ), 10 );
		remove_filter( 'rest_post_dispatch', array( $this, 'clear_tokenized_postcode_validation' ), 10 );
		$this->tokenized_postcode_validation_allowlist = array();

		return $response;
	}

	/**
	 * Reject a Store API request that carries an invalid tokenized product cart session.
	 *
	 * @param mixed           $result  Response to replace the requested endpoint response with.
	 * @param mixed           $server  REST server.
	 * @param WP_REST_Request $request REST request.
	 * @phpstan-param WP_REST_Request<array<string,mixed>> $request
	 * @return WP_Error
	 */
	public function reject_invalid_tokenized_cart_session( $result, $server, WP_REST_Request $request ): WP_Error {
		return new WP_Error(
			'woocommerce_rest_invalid_tokenized_cart_session',
			__( 'Invalid tokenized cart session.', 'woocommerce' ),
			array( 'status' => 400 )
		);
	}

	/**
	 * Add the next tokenized session header and clean ephemeral sessions after Store API dispatch.
	 *
	 * @param mixed                $response REST response.
	 * @param mixed                $server   REST server.
	 * @param WP_REST_Request|null $request  REST request.
	 * @phpstan-param WP_REST_Request<array<string,mixed>>|null $request
	 * @return mixed
	 */
	public function handle_store_api_response( $response, $server = null, ?WP_REST_Request $request = null ) {
		if ( ! $response instanceof WP_REST_Response || ! function_exists( 'WC' ) || ! WC() || ! WC()->session || ! is_callable( array( WC()->session, 'get_customer_id' ) ) ) {
			return $response;
		}

		$session_id = (string) WC()->session->get_customer_id();
		if ( '' !== $session_id ) {
			$response->header( self::RESPONSE_SESSION_HEADER, $this->create_session_token( $session_id ) );
		}

		if ( $this->is_ephemeral_request() && is_callable( array( WC()->session, 'delete_session' ) ) ) {
			WC()->session->delete_session( $session_id );
		}

		return $response;
	}

	/**
	 * Save the real cart data before WooCommerce empties the checkout cart on order-received.
	 */
	public function save_old_cart_data_for_restore(): void {
		if ( ! function_exists( 'WC' ) || ! WC() || ! WC()->cart ) {
			return;
		}

		$this->saved_cart_data = array(
			'cart_contents'              => WC()->cart->get_cart_contents(),
			'removed_cart_contents'      => WC()->cart->get_removed_cart_contents(),
			'applied_coupons'            => WC()->cart->get_applied_coupons(),
			'coupon_discount_totals'     => WC()->cart->get_coupon_discount_totals(),
			'coupon_discount_tax_totals' => WC()->cart->get_coupon_discount_tax_totals(),
			'totals'                     => WC()->cart->get_totals(),
			'session_data'               => $this->get_cart_session_snapshot(),
		);
	}

	/**
	 * Restore the real cart data after WooCommerce empties the checkout cart on order-received.
	 */
	public function restore_old_cart_data(): void {
		if ( null === $this->saved_cart_data || ! function_exists( 'WC' ) || ! WC() || ! WC()->cart ) {
			return;
		}

		WC()->cart->set_cart_contents( $this->saved_cart_data['cart_contents'] ?? array() );
		WC()->cart->set_removed_cart_contents( $this->saved_cart_data['removed_cart_contents'] ?? array() );
		WC()->cart->set_applied_coupons( $this->saved_cart_data['applied_coupons'] ?? array() );
		WC()->cart->set_coupon_discount_totals( $this->saved_cart_data['coupon_discount_totals'] ?? array() );
		WC()->cart->set_coupon_discount_tax_totals( $this->saved_cart_data['coupon_discount_tax_totals'] ?? array() );
		WC()->cart->set_totals( $this->saved_cart_data['totals'] ?? array() );
		$this->restore_cart_session_snapshot( $this->saved_cart_data['session_data'] ?? array() );
		$this->saved_cart_data = null;
	}

	/**
	 * Prevent the tokenized product-page return URL from clearing the shopper's real cart.
	 *
	 * @return bool
	 */
	public function preserve_cart_after_tokenized_payment(): bool {
		return false;
	}

	/**
	 * Snapshot cart-related session state that direct order-received empty-cart calls clear.
	 *
	 * @return array<string,mixed>
	 */
	private function get_cart_session_snapshot(): array {
		if ( ! function_exists( 'WC' ) || ! WC() || ! WC()->session ) {
			return array();
		}

		$session  = WC()->session;
		$snapshot = array();
		if ( is_callable( array( $session, 'get_session_data' ) ) ) {
			foreach ( (array) $session->get_session_data() as $key => $value ) {
				$snapshot[ (string) $key ] = maybe_unserialize( $value );
			}
		}

		foreach ( self::CART_SESSION_KEYS as $key ) {
			if ( is_callable( array( $session, 'get' ) ) ) {
				$value = $session->get( $key, null );
				if ( null !== $value ) {
					$snapshot[ $key ] = $value;
				}
			}
		}

		return $snapshot;
	}

	/**
	 * Restore a cart-related session snapshot.
	 *
	 * @param array<string,mixed> $snapshot Session values to restore.
	 */
	private function restore_cart_session_snapshot( array $snapshot ): void {
		if ( ! function_exists( 'WC' ) || ! WC() || ! WC()->session || ! is_callable( array( WC()->session, 'set' ) ) ) {
			return;
		}

		foreach ( $snapshot as $key => $value ) {
			WC()->session->set( (string) $key, $value );
		}
	}

	/**
	 * Normalize tokenized Store API address values before Store API validation.
	 *
	 * @param WP_REST_Request $request REST request.
	 * @phpstan-param WP_REST_Request<array<string,mixed>> $request
	 */
	private function normalize_tokenized_cart_store_api_addresses( WP_REST_Request $request ): void {
		if ( ! $this->should_normalize_tokenized_cart_address( $request ) ) {
			return;
		}

		$is_update_customer_route = '/wc/store/v1/cart/update-customer' === $request->get_route();
		if ( $is_update_customer_route ) {
			$this->tokenized_postcode_validation_allowlist = array();
			if ( false === has_filter( 'woocommerce_validate_postcode', array( $this, 'maybe_skip_postcode_validation' ) ) ) {
				add_filter( 'woocommerce_validate_postcode', array( $this, 'maybe_skip_postcode_validation' ), 10, 3 );
			}
			if ( false === has_filter( 'rest_post_dispatch', array( $this, 'clear_tokenized_postcode_validation' ) ) ) {
				add_filter( 'rest_post_dispatch', array( $this, 'clear_tokenized_postcode_validation' ), 10, 3 );
			}
		}

		foreach ( array( 'shipping_address', 'billing_address' ) as $address_key ) {
			$address = $request->get_param( $address_key );
			if ( is_array( $address ) ) {
				$address = $this->normalize_ece_address( $address, $is_update_customer_route );
				if ( $is_update_customer_route ) {
					$this->add_tokenized_postcode_validation_exception( $address );
				}
				$request->set_param( $address_key, $address );
			}
		}
	}

	/**
	 * Tell whether tokenized Store API address values may be normalized.
	 *
	 * @param WP_REST_Request $request REST request.
	 * @phpstan-param WP_REST_Request<array<string,mixed>> $request
	 * @return bool
	 */
	private function should_normalize_tokenized_cart_address( WP_REST_Request $request ): bool {
		if ( 'true' !== $request->get_header( self::TOKENIZED_CART_HEADER ) ) {
			return false;
		}

		$nonce = (string) $request->get_header( self::TOKENIZED_CART_NONCE_HEADER );

		return '' !== $nonce && function_exists( 'wp_verify_nonce' ) && (bool) \wp_verify_nonce( $nonce, self::TOKENIZED_CART_NONCE_ACTION );
	}

	/**
	 * Normalize express-checkout address fields that Stripe wallet payloads can redact or reshape.
	 *
	 * @param array<string,mixed> $address            Address values.
	 * @param bool                $normalize_postcode Whether to normalize redacted postcode values.
	 * @return array<string,mixed>
	 */
	private function normalize_ece_address( array $address, bool $normalize_postcode ): array {
		$address = $this->normalize_ece_address_state( $address );
		$address = $this->normalize_ece_address_lines( $address );

		return $normalize_postcode ? $this->normalize_ece_address_postcode( $address ) : $address;
	}

	/**
	 * Normalize address state values to WooCommerce state keys when possible.
	 *
	 * @param array<string,mixed> $address Address values.
	 * @return array<string,mixed>
	 */
	private function normalize_ece_address_state( array $address ): array {
		$country = $this->sanitize_address_value( $address['country'] ?? '' );
		if ( '' === $country ) {
			return $address;
		}

		if ( 'HK' === $country ) {
			foreach ( array( $address['state'] ?? '', $address['postcode'] ?? '', $address['city'] ?? '' ) as $candidate ) {
				$region = $this->get_hong_kong_region_for_candidate( $this->sanitize_address_value( $candidate ) );
				if ( '' !== $region ) {
					$address['state'] = $region;
					break;
				}
			}
		}

		$state = $this->sanitize_address_value( $address['state'] ?? '' );
		if ( '' !== $state ) {
			$address['state'] = $this->get_normalized_state( $state, $country );
		}

		return $address;
	}

	/**
	 * Shift populated address lines so address_1 is present when any line is.
	 *
	 * @param array<string,mixed> $address Address values.
	 * @return array<string,mixed>
	 */
	private function normalize_ece_address_lines( array $address ): array {
		$lines = array_values(
			array_filter(
				array(
					trim( $this->sanitize_address_value( $address['address_1'] ?? '' ) ),
					trim( $this->sanitize_address_value( $address['address_2'] ?? '' ) ),
				),
				static function ( string $line ): bool {
					return '' !== $line;
				}
			)
		);

		if ( empty( $lines ) ) {
			return $address;
		}

		$address['address_1'] = $lines[0];
		$address['address_2'] = $lines[1] ?? '';

		return $address;
	}

	/**
	 * Normalize redacted postcode values used for tokenized shipping-rate lookup.
	 *
	 * @param array<string,mixed> $address Address values.
	 * @return array<string,mixed>
	 */
	private function normalize_ece_address_postcode( array $address ): array {
		$country  = $this->sanitize_address_value( $address['country'] ?? '' );
		$postcode = $this->sanitize_address_value( $address['postcode'] ?? '' );
		if ( '' !== $country && '' !== $postcode ) {
			$address['postcode'] = $this->get_normalized_postal_code( $postcode, $country );
		}

		return $address;
	}

	/**
	 * Normalize redacted postal-code values for countries where wallets can send partial values.
	 *
	 * @param string $postcode Postcode value.
	 * @param string $country  Country code.
	 * @return string
	 */
	private function get_normalized_postal_code( string $postcode, string $country ): string {
		$country = strtoupper( $country );

		if ( 'GB' === $country ) {
			$cleaned_postcode = preg_replace( '/[^A-Za-z0-9]/', '', $postcode );
			$cleaned_postcode = substr( is_string( $cleaned_postcode ) ? $cleaned_postcode : '', 0, 7 );

			return strlen( $cleaned_postcode ) >= 5 ? $cleaned_postcode : $cleaned_postcode . '000';
		}

		if ( 'CA' === $country ) {
			$cleaned_postcode = preg_replace( '/\s+/', '', $postcode );

			return str_pad( is_string( $cleaned_postcode ) ? $cleaned_postcode : '', 6, '0' );
		}

		return $postcode;
	}

	/**
	 * Add a normalized postcode to the request-scoped validation allowlist.
	 *
	 * @param array<string,mixed> $address Address values.
	 */
	private function add_tokenized_postcode_validation_exception( array $address ): void {
		$country  = strtoupper( $this->sanitize_address_value( $address['country'] ?? '' ) );
		$postcode = $this->normalize_postcode_for_validation_allowlist( $this->sanitize_address_value( $address['postcode'] ?? '' ) );
		if ( '' === $postcode || ! in_array( $country, array( 'GB', 'CA' ), true ) ) {
			return;
		}

		$this->tokenized_postcode_validation_allowlist[ $country ][ $postcode ] = true;
	}

	/**
	 * Normalize postcode strings for request-scoped validation comparisons.
	 *
	 * @param string $postcode Postcode value.
	 * @return string
	 */
	private function normalize_postcode_for_validation_allowlist( string $postcode ): string {
		$postcode = preg_replace( '/[^A-Za-z0-9]/', '', strtoupper( $postcode ) );

		return is_string( $postcode ) ? $postcode : '';
	}

	/**
	 * Normalize a wallet state value to a WooCommerce state key.
	 *
	 * @param string $state   State value.
	 * @param string $country Country code.
	 * @return string
	 */
	private function get_normalized_state( string $state, string $country ): string {
		if ( '' === $state || $this->is_normalized_state( $state, $country ) ) {
			return $state;
		}

		return $this->get_normalized_state_from_wc_states( $state, $country );
	}

	/**
	 * Tell whether a state value is already a WooCommerce state key.
	 *
	 * @param string $state   State value.
	 * @param string $country Country code.
	 * @return bool
	 */
	private function is_normalized_state( string $state, string $country ): bool {
		if ( ! function_exists( 'WC' ) || ! WC() || ! WC()->countries ) {
			return false;
		}

		$wc_states = WC()->countries->get_states( $country );

		return is_array( $wc_states ) && array_key_exists( $state, $wc_states );
	}

	/**
	 * Match a state value against WooCommerce's translated state labels.
	 *
	 * @param string $state   State value.
	 * @param string $country Country code.
	 * @return string
	 */
	private function get_normalized_state_from_wc_states( string $state, string $country ): string {
		if ( ! function_exists( 'WC' ) || ! WC() || ! WC()->countries ) {
			return $state;
		}

		$wc_states        = WC()->countries->get_states( $country );
		$normalized_state = $this->normalize_address_candidate( $state );
		if ( ! is_array( $wc_states ) || '' === $normalized_state ) {
			return $state;
		}

		foreach ( $wc_states as $wc_state_abbr => $wc_state_value ) {
			$normalized_state_abbr  = $this->normalize_address_candidate( (string) $wc_state_abbr );
			$normalized_state_value = $this->normalize_address_candidate( (string) $wc_state_value );

			if (
				$normalized_state === $normalized_state_abbr ||
				$normalized_state === $normalized_state_value ||
				( '' !== $normalized_state_value && false !== strpos( $normalized_state, $normalized_state_value ) )
			) {
				return (string) $wc_state_abbr;
			}
		}

		return $state;
	}

	/**
	 * Resolve a Hong Kong region from wallet state, postcode, or city values.
	 *
	 * @param string $candidate Address candidate value.
	 * @return string
	 */
	private function get_hong_kong_region_for_candidate( string $candidate ): string {
		$normalized_candidate = $this->normalize_address_candidate( $candidate );
		if ( 'hongkong' === $normalized_candidate ) {
			$normalized_candidate = 'hong kong';
		}

		foreach ( self::HONG_KONG_REGION_CANDIDATES as $region => $region_candidates ) {
			foreach ( $region_candidates as $region_candidate ) {
				$normalized_region_candidate = $this->normalize_address_candidate( $region_candidate );
				if ( $normalized_candidate === $normalized_region_candidate ) {
					return $region;
				}
			}
		}

		return '';
	}

	/**
	 * Normalize an address value for loose matching.
	 *
	 * @param string $value Address value.
	 * @return string
	 */
	private function normalize_address_candidate( string $value ): string {
		$value = function_exists( 'remove_accents' ) ? remove_accents( $value ) : $value;
		$value = strtolower( trim( $value ) );
		$value = preg_replace( '/\s+/', ' ', $value );

		return is_string( $value ) ? $value : '';
	}

	/**
	 * Sanitize a scalar address value.
	 *
	 * @param mixed $value Address value.
	 * @return string
	 */
	private function sanitize_address_value( $value ): string {
		$sanitized_value = is_scalar( $value ) ? wc_clean( (string) $value ) : '';

		return is_scalar( $sanitized_value ) ? (string) $sanitized_value : '';
	}

	/**
	 * Tell whether the current request is a Store API request.
	 *
	 * @return bool
	 */
	private function is_store_api_request(): bool {
		if ( function_exists( 'rest_get_url_prefix' ) ) {
			$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( (string) $_SERVER['REQUEST_URI'] ) ) : '';
			if ( '' !== $request_uri && false !== strpos( $request_uri, trailingslashit( rest_get_url_prefix() ) . 'wc/store/' ) ) {
				return true;
			}
		}

		if ( ! isset( $_GET['rest_route'] ) || ! is_string( $_GET['rest_route'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return false;
		}

		$rest_route = rawurldecode( esc_url_raw( wp_unslash( $_GET['rest_route'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		return 0 === strpos( $rest_route, '/wc/store/' );
	}

	/**
	 * Tell whether the product-page session nonce is valid.
	 *
	 * @return bool
	 */
	private function has_valid_session_nonce(): bool {
		$nonce = $this->get_server_header( self::SESSION_NONCE_HEADER );

		return '' !== $nonce && function_exists( 'wp_verify_nonce' ) && (bool) \wp_verify_nonce( $nonce, self::SESSION_NONCE_ACTION );
	}

	/**
	 * Tell whether the request carries WooPayments tokenized-cart markers.
	 *
	 * @return bool
	 */
	private function has_tokenized_cart_marker(): bool {
		return isset( $_SERVER[ self::SESSION_NONCE_HEADER ] ) ||
			isset( $_SERVER[ self::SESSION_HEADER ] ) ||
			isset( $_SERVER['HTTP_X_WOOPAYMENTS_TOKENIZED_CART'] ) ||
			isset( $_SERVER[ self::EPHEMERAL_CART_HEADER ] );
	}

	/**
	 * Tell whether the incoming tokenized session header is empty or valid.
	 *
	 * @return bool
	 */
	private function has_valid_incoming_session_token(): bool {
		$token = $this->get_server_header( self::SESSION_HEADER );
		if ( '' === $token ) {
			return true;
		}

		if ( ! JsonWebToken::validate( $token, WooPaymentsTokenizedCartSessionHandler::get_token_secret() ) ) {
			return false;
		}

		$parts = JsonWebToken::get_parts( $token );
		$payload = is_object( $parts ) && isset( $parts->payload ) ? $parts->payload : null;

		if ( ! is_object( $payload ) || ! isset( $payload->session_id, $payload->iss ) || self::TOKEN_ISSUER !== $payload->iss ) {
			return false;
		}

		$session_id = is_scalar( $payload->session_id ) ? sanitize_text_field( (string) $payload->session_id ) : '';

		return 0 === strpos( $session_id, 't_' );
	}

	/**
	 * Get a sanitized HTTP request header from the server environment.
	 *
	 * @param string $key Server header key.
	 * @return string
	 */
	private function get_server_header( string $key ): string {
		if ( ! isset( $_SERVER[ $key ] ) || ! is_scalar( $_SERVER[ $key ] ) ) {
			return '';
		}

		$value = wc_clean( wp_unslash( (string) $_SERVER[ $key ] ) );

		return is_scalar( $value ) ? (string) $value : '';
	}

	/**
	 * Create a signed token for the current tokenized cart session.
	 *
	 * @param string $session_id Tokenized session ID.
	 * @return string
	 */
	private function create_session_token( string $session_id ): string {
		return JsonWebToken::create(
			array(
				'session_id' => $session_id,
				'exp'        => time() + DAY_IN_SECONDS,
				'iss'        => self::TOKEN_ISSUER,
			),
			WooPaymentsTokenizedCartSessionHandler::get_token_secret()
		);
	}

	/**
	 * Tell whether this Store API request should delete the isolated cart after dispatch.
	 *
	 * @return bool
	 */
	private function is_ephemeral_request(): bool {
		return '1' === ( isset( $_SERVER[ self::EPHEMERAL_CART_HEADER ] ) ? (string) $_SERVER[ self::EPHEMERAL_CART_HEADER ] : '' );
	}

	/**
	 * Tell whether the current order-received request originated from a tokenized product cart session.
	 *
	 * @return bool
	 */
	private function is_custom_session_order_received_request(): bool {
		$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) $_SERVER['REQUEST_URI'] : '';

		return false !== strpos( $request_uri, self::RETURN_URL_MARKER . '=1' );
	}
}
