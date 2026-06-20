<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Admin\Settings\PaymentsProviders\WooPayments;

use Automattic\WooCommerce\Internal\Admin\Settings\Exceptions\ApiException;
use Automattic\WooCommerce\Internal\Admin\Settings\Payments;
use Automattic\WooCommerce\Internal\RestApiControllerBase;
use Automattic\WooCommerce\Internal\Utilities\ArrayUtil;
use Automattic\WooCommerce\Internal\Payments\NativePaymentsRuntimeArbiter;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsPmPromotionsService;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsSettingsService;
use Exception;
use WP_Error;
use WP_Http;
use WP_HTTP_Response;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Controller for the WooPayments-specific REST endpoints to service the Payments settings page.
 *
 * @internal
 */
class WooPaymentsRestController extends RestApiControllerBase {

	/**
	 * Public file purposes that may be served without payment gateway management permissions.
	 */
	private const PUBLIC_FILE_PURPOSES = array(
		'business_logo',
		'business_icon',
	);

	/**
	 * Prefix for cached provider file purposes.
	 */
	private const FILE_PURPOSE_CACHE_PREFIX = 'woocommerce_native_woopayments_file_purpose_';

	/**
	 * The root namespace for the JSON REST API endpoints.
	 *
	 * @var string
	 */
	protected string $route_namespace = 'wc-admin';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected string $rest_base = 'settings/payments/woopayments';

	/**
	 * The payments settings page service.
	 *
	 * @var Payments
	 */
	private Payments $payments;

	/**
	 * The WooPayments-specific Payments settings page service.
	 *
	 * @var WooPaymentsService
	 */
	private WooPaymentsService $woopayments;

	/**
	 * The native WooPayments settings contract service.
	 *
	 * @var WooPaymentsSettingsService|null
	 */
	private ?WooPaymentsSettingsService $settings_service = null;

	/**
	 * The native WooPayments payment method promotions service.
	 *
	 * @var WooPaymentsPmPromotionsService|null
	 */
	private ?WooPaymentsPmPromotionsService $pm_promotions_service = null;

	/**
	 * The native WooPayments Overview projection service.
	 *
	 * @var WooPaymentsOverviewService|null
	 */
	private ?WooPaymentsOverviewService $overview_service = null;

	/**
	 * Native payments runtime arbiter.
	 *
	 * @var NativePaymentsRuntimeArbiter|null
	 */
	private ?NativePaymentsRuntimeArbiter $runtime_arbiter = null;

	/**
	 * Get the WooCommerce REST API namespace for the class.
	 *
	 * @return string
	 */
	protected function get_rest_api_namespace(): string {
		return 'wc-admin-settings-payments-woopayments';
	}

	/**
	 * Register the REST API endpoints handled by this controller.
	 *
	 * @param bool $override Whether to override the existing routes. Useful for testing.
	 */
	public function register_routes( bool $override = false ) {
		if ( $this->should_register_native_settings_routes() ) {
			$this->register_native_settings_routes( $override );
		}

		register_rest_route(
			$this->route_namespace,
			'/' . $this->rest_base . '/onboarding',
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => fn( $request ) => $this->run( $request, 'get_onboarding_details' ),
					'validation_callback' => 'rest_validate_request_arg',
					'permission_callback' => fn( $request ) => $this->check_permissions( $request ),
					'args'                => array(
						'location' => array(
							'description'       => esc_html__( 'ISO3166 alpha-2 country code. Defaults to the stored providers business location country code.', 'woocommerce' ),
							'type'              => 'string',
							'pattern'           => '[a-zA-Z]{2}',
							// Two alpha characters.
							'required'          => false,
							'validate_callback' => fn( $value, $request ) => $this->check_location_arg( $value, $request ),
						),
						'source'   => array(
							'description'       => esc_html__( 'The upmost entry point from where the merchant entered the onboarding flow.', 'woocommerce' ),
							'type'              => 'string',
							'required'          => false,
							'sanitize_callback' => 'sanitize_text_field',
						),
					),
				),
				'schema' => fn() => $this->get_schema_for_get_onboarding_details(),
			),
			$override
		);
		register_rest_route(
			$this->route_namespace,
			'/' . $this->rest_base . '/onboarding/step/(?P<step>[a-zA-Z0-9_-]+)/start',
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => fn( $request ) => $this->run( $request, 'handle_onboarding_step_start' ),
					'permission_callback' => fn( $request ) => $this->check_permissions( $request ),
					'args'                => array(
						'location' => array(
							'description'       => esc_html__( 'ISO3166 alpha-2 country code. Defaults to the stored providers business location country code.', 'woocommerce' ),
							'type'              => 'string',
							'pattern'           => '[a-zA-Z]{2}',
							// Two alpha characters.
							'required'          => false,
							'validate_callback' => fn( $value, $request ) => $this->check_location_arg( $value, $request ),
						),
						'source'   => array(
							'description'       => esc_html__( 'The upmost entry point from where the merchant entered the onboarding flow.', 'woocommerce' ),
							'type'              => 'string',
							'required'          => false,
							'sanitize_callback' => 'sanitize_text_field',
						),
					),
				),
			),
			$override
		);
		register_rest_route(
			$this->route_namespace,
			'/' . $this->rest_base . '/onboarding/step/(?P<step>[a-zA-Z0-9_-]+)/save',
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => fn( $request ) => $this->run( $request, 'handle_onboarding_step_save' ),
					'permission_callback' => fn( $request ) => $this->check_permissions( $request ),
					'args'                => array(
						'location' => array(
							'description'       => esc_html__( 'ISO3166 alpha-2 country code. Defaults to the stored providers business location country code.', 'woocommerce' ),
							'type'              => 'string',
							'pattern'           => '[a-zA-Z]{2}',
							// Two alpha characters.
							'required'          => false,
							'validate_callback' => fn( $value, $request ) => $this->check_location_arg( $value, $request ),
						),
						'source'   => array(
							'description'       => esc_html__( 'The upmost entry point from where the merchant entered the onboarding flow.', 'woocommerce' ),
							'type'              => 'string',
							'required'          => false,
							'sanitize_callback' => 'sanitize_text_field',
						),
					),
				),
			),
			$override
		);
		register_rest_route(
			$this->route_namespace,
			'/' . $this->rest_base . '/onboarding/step/(?P<step>[a-zA-Z0-9_-]+)/check',
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => fn( $request ) => $this->run( $request, 'handle_onboarding_step_check' ),
					'permission_callback' => fn( $request ) => $this->check_permissions( $request ),
					'args'                => array(
						'location' => array(
							'description'       => esc_html__( 'ISO3166 alpha-2 country code. Defaults to the stored providers business location country code.', 'woocommerce' ),
							'type'              => 'string',
							'pattern'           => '[a-zA-Z]{2}',
							// Two alpha characters.
							'required'          => false,
							'validate_callback' => fn( $value, $request ) => $this->check_location_arg( $value, $request ),
						),
					),
				),
			),
			$override
		);
		register_rest_route(
			$this->route_namespace,
			'/' . $this->rest_base . '/onboarding/step/(?P<step>[a-zA-Z0-9_-]+)/finish',
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => fn( $request ) => $this->run( $request, 'handle_onboarding_step_finish' ),
					'permission_callback' => fn( $request ) => $this->check_permissions( $request ),
					'args'                => array(
						'location' => array(
							'description'       => esc_html__( 'ISO3166 alpha-2 country code. Defaults to the stored providers business location country code.', 'woocommerce' ),
							'type'              => 'string',
							'pattern'           => '[a-zA-Z]{2}',
							// Two alpha characters.
							'required'          => false,
							'validate_callback' => fn( $value, $request ) => $this->check_location_arg( $value, $request ),
						),
						'source'   => array(
							'description'       => esc_html__( 'The upmost entry point from where the merchant entered the onboarding flow.', 'woocommerce' ),
							'type'              => 'string',
							'required'          => false,
							'sanitize_callback' => 'sanitize_text_field',
						),
					),
				),
			),
			$override
		);
		register_rest_route(
			$this->route_namespace,
			'/' . $this->rest_base . '/onboarding/step/(?P<step>[a-zA-Z0-9_-]+)/clean',
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => fn( $request ) => $this->run( $request, 'handle_onboarding_step_clean' ),
					'permission_callback' => fn( $request ) => $this->check_permissions( $request ),
					'args'                => array(
						'location' => array(
							'description'       => esc_html__( 'ISO3166 alpha-2 country code. Defaults to the stored providers business location country code.', 'woocommerce' ),
							'type'              => 'string',
							'pattern'           => '[a-zA-Z]{2}',
							// Two alpha characters.
							'required'          => false,
							'validate_callback' => fn( $value, $request ) => $this->check_location_arg( $value, $request ),
						),
					),
				),
			),
			$override
		);
		// Onboarding step specific routes.
		register_rest_route(
			$this->route_namespace,
			'/' . $this->rest_base . '/onboarding/step/' . WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT . '/init',
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => fn( $request ) => $this->run( $request, 'handle_onboarding_test_account_init' ),
					'permission_callback' => fn( $request ) => $this->check_permissions( $request ),
					'args'                => array(
						'location' => array(
							'description'       => esc_html__( 'ISO3166 alpha-2 country code. Defaults to the stored providers business location country code.', 'woocommerce' ),
							'type'              => 'string',
							'pattern'           => '[a-zA-Z]{2}',
							// Two alpha characters.
							'required'          => false,
							'validate_callback' => fn( $value, $request ) => $this->check_location_arg( $value, $request ),
						),
						'source'   => array(
							'description'       => esc_html__( 'The upmost entry point from where the merchant entered the onboarding flow.', 'woocommerce' ),
							'type'              => 'string',
							'required'          => false,
							'sanitize_callback' => 'sanitize_text_field',
						),
					),
				),
			),
			$override
		);
		register_rest_route(
			$this->route_namespace,
			'/' . $this->rest_base . '/onboarding/step/' . WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT . '/reset',
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => fn( $request ) => $this->run( $request, 'handle_onboarding_test_account_reset' ),
					'permission_callback' => fn( $request ) => $this->check_permissions( $request ),
					'args'                => array(
						'location' => array(
							'description'       => esc_html__( 'ISO3166 alpha-2 country code. Defaults to the stored providers business location country code.', 'woocommerce' ),
							'type'              => 'string',
							'pattern'           => '[a-zA-Z]{2}',
							// Two alpha characters.
							'required'          => false,
							'validate_callback' => fn( $value, $request ) => $this->check_location_arg( $value, $request ),
						),
						'source'   => array(
							'description'       => esc_html__( 'The upmost entry point from where the merchant entered the onboarding flow.', 'woocommerce' ),
							'type'              => 'string',
							'required'          => false,
							'sanitize_callback' => 'sanitize_text_field',
						),
					),
				),
			),
			$override
		);
		register_rest_route(
			$this->route_namespace,
			'/' . $this->rest_base . '/onboarding/step/' . WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION . '/kyc_session',
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => fn( $request ) => $this->run( $request, 'handle_onboarding_business_verification_kyc_session_init' ),
					'permission_callback' => fn( $request ) => $this->check_permissions( $request ),
					'args'                => array(
						'location' => array(
							'description'       => esc_html__( 'ISO3166 alpha-2 country code. Defaults to the stored providers business location country code.', 'woocommerce' ),
							'type'              => 'string',
							'pattern'           => '[a-zA-Z]{2}',
							// Two alpha characters.
							'required'          => false,
							'validate_callback' => fn( $value, $request ) => $this->check_location_arg( $value, $request ),
						),
						'source'   => array(
							'description'       => esc_html__( 'The upmost entry point from where the merchant entered the onboarding flow.', 'woocommerce' ),
							'type'              => 'string',
							'required'          => false,
							'sanitize_callback' => 'sanitize_text_field',
						),
					),
				),
			),
			$override
		);
		register_rest_route(
			$this->route_namespace,
			'/' . $this->rest_base . '/onboarding/step/' . WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION . '/kyc_session/finish',
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => fn( $request ) => $this->run( $request, 'handle_onboarding_business_verification_kyc_session_finish' ),
					'permission_callback' => fn( $request ) => $this->check_permissions( $request ),
					'args'                => array(
						'location' => array(
							'description'       => esc_html__( 'ISO3166 alpha-2 country code. Defaults to the stored providers business location country code.', 'woocommerce' ),
							'type'              => 'string',
							'pattern'           => '[a-zA-Z]{2}',
							// Two alpha characters.
							'required'          => false,
							'validate_callback' => fn( $value, $request ) => $this->check_location_arg( $value, $request ),
						),
						'source'   => array(
							'description'       => esc_html__( 'The upmost entry point from where the merchant entered the onboarding flow.', 'woocommerce' ),
							'type'              => 'string',
							'required'          => false,
							'sanitize_callback' => 'sanitize_text_field',
						),
					),
				),
			),
			$override
		);
		// This is a route to disable test accounts for the native onboarding UX.
		// The handler is the same as the one for the non-native onboarding UX.
		register_rest_route(
			$this->route_namespace,
			'/' . $this->rest_base . '/onboarding/step/' . WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION . '/test_account/disable',
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => fn( $request ) => $this->run( $request, 'handle_test_account_disable' ),
					'permission_callback' => fn( $request ) => $this->check_permissions( $request ),
					'args'                => array(
						'location' => array(
							'description'       => esc_html__( 'ISO3166 alpha-2 country code. Defaults to the stored providers business location country code.', 'woocommerce' ),
							'type'              => 'string',
							'pattern'           => '[a-zA-Z]{2}',
							// Two alpha characters.
							'required'          => false,
							'validate_callback' => fn( $value, $request ) => $this->check_location_arg( $value, $request ),
						),
						'from'     => array(
							'description'       => esc_html__( 'Where from in the onboarding flow this request was triggered.', 'woocommerce' ),
							'type'              => 'string',
							'required'          => false,
							'sanitize_callback' => 'sanitize_text_field',
						),
						'source'   => array(
							'description'       => esc_html__( 'The upmost entry point from where the merchant entered the onboarding flow.', 'woocommerce' ),
							'type'              => 'string',
							'required'          => false,
							'sanitize_callback' => 'sanitize_text_field',
						),
					),
				),
			),
			$override
		);
		register_rest_route(
			$this->route_namespace,
			'/' . $this->rest_base . '/onboarding/preload',
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => fn( $request ) => $this->run( $request, 'handle_onboarding_preload' ),
					'validation_callback' => 'rest_validate_request_arg',
					'permission_callback' => fn( $request ) => $this->check_permissions( $request ),
					'args'                => array(
						'location' => array(
							'description'       => esc_html__( 'ISO3166 alpha-2 country code. Defaults to the stored providers business location country code.', 'woocommerce' ),
							'type'              => 'string',
							'pattern'           => '[a-zA-Z]{2}',
							// Two alpha characters.
							'required'          => false,
							'validate_callback' => fn( $value, $request ) => $this->check_location_arg( $value, $request ),
						),
					),
				),
			),
			$override
		);
		register_rest_route(
			$this->route_namespace,
			'/' . $this->rest_base . '/onboarding/reset',
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => fn( $request ) => $this->run( $request, 'reset_onboarding' ),
					'validation_callback' => 'rest_validate_request_arg',
					'permission_callback' => fn( $request ) => $this->check_permissions( $request ),
					'args'                => array(
						'location' => array(
							'description'       => esc_html__( 'ISO3166 alpha-2 country code. Defaults to the stored providers business location country code.', 'woocommerce' ),
							'type'              => 'string',
							'pattern'           => '[a-zA-Z]{2}',
							// Two alpha characters.
							'required'          => false,
							'validate_callback' => fn( $value, $request ) => $this->check_location_arg( $value, $request ),
						),
						'from'     => array(
							'description'       => esc_html__( 'Where from in the onboarding flow this request was triggered.', 'woocommerce' ),
							'type'              => 'string',
							'required'          => false,
							'sanitize_callback' => 'sanitize_text_field',
						),
						'source'   => array(
							'description'       => esc_html__( 'The upmost entry point from where the merchant entered the onboarding flow.', 'woocommerce' ),
							'type'              => 'string',
							'required'          => false,
							'sanitize_callback' => 'sanitize_text_field',
						),
					),
				),
			),
			$override
		);
		register_rest_route(
			$this->route_namespace,
			'/' . $this->rest_base . '/account',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => fn( $request ) => $this->run( $request, 'get_account_summary' ),
					'validation_callback' => 'rest_validate_request_arg',
					'permission_callback' => fn( $request ) => $this->check_permissions( $request ),
				),
			),
			$override
		);
		register_rest_route(
			$this->route_namespace,
			'/' . $this->rest_base . '/overview',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => fn( $request ) => $this->run( $request, 'get_overview' ),
					'validation_callback' => 'rest_validate_request_arg',
					'permission_callback' => fn( $request ) => $this->check_permissions( $request ),
				),
			),
			$override
		);
		register_rest_route(
			$this->route_namespace,
			'/' . $this->rest_base . '/woopay-eligibility',
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => fn( $request ) => $this->run( $request, 'get_woopay_eligibility' ),
					'permission_callback' => fn( $request ) => $this->check_permissions( $request ),
				),
			),
			$override
		);
		// This is the route to disable test accounts when not in a native in-context UX.
		register_rest_route(
			$this->route_namespace,
			'/' . $this->rest_base . '/onboarding/test_account/disable',
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => fn( $request ) => $this->run( $request, 'handle_test_account_disable' ),
					'validation_callback' => 'rest_validate_request_arg',
					'permission_callback' => fn( $request ) => $this->check_permissions( $request ),
					'args'                => array(
						'location' => array(
							'description'       => esc_html__( 'ISO3166 alpha-2 country code. Defaults to the stored providers business location country code.', 'woocommerce' ),
							'type'              => 'string',
							'pattern'           => '[a-zA-Z]{2}',
							// Two alpha characters.
							'required'          => false,
							'validate_callback' => fn( $value, $request ) => $this->check_location_arg( $value, $request ),
						),
						'from'     => array(
							'description'       => esc_html__( 'Where from in the onboarding flow this request was triggered.', 'woocommerce' ),
							'type'              => 'string',
							'required'          => false,
							'sanitize_callback' => 'sanitize_text_field',
						),
						'source'   => array(
							'description'       => esc_html__( 'The upmost entry point from where the merchant entered the onboarding flow.', 'woocommerce' ),
							'type'              => 'string',
							'required'          => false,
							'sanitize_callback' => 'sanitize_text_field',
						),
					),
				),
			),
			$override
		);
	}

	/**
	 * Get the controller's REST URL path.
	 *
	 * @param string $relative_path Optional. Relative path to append to the REST URL.
	 *
	 * @return string The REST URL path.
	 */
	public function get_rest_url_path( string $relative_path = '' ): string {
		$path = '/' . trim( $this->route_namespace, '/' ) . '/' . trim( $this->rest_base, '/' );
		if ( ! empty( $relative_path ) ) {
			$path .= '/' . ltrim( $relative_path, '/' );
		}

		return $path;
	}

	/**
	 * Initialize the class instance.
	 *
	 * @param Payments                            $payments        The general payments settings page service.
	 * @param WooPaymentsService                  $woopayments     The WooPayments-specific Payments settings page service.
	 * @param WooPaymentsSettingsService|null     $settings_service Optional native WooPayments settings service.
	 * @param NativePaymentsRuntimeArbiter|null   $runtime_arbiter Optional native payments runtime arbiter.
	 * @param WooPaymentsPmPromotionsService|null $pm_promotions_service Optional native WooPayments PM promotions service.
	 * @param WooPaymentsOverviewService|null     $overview_service Optional native WooPayments Overview projection service.
	 *
	 * @internal
	 */
	final public function init( Payments $payments, WooPaymentsService $woopayments, ?WooPaymentsSettingsService $settings_service = null, ?NativePaymentsRuntimeArbiter $runtime_arbiter = null, ?WooPaymentsPmPromotionsService $pm_promotions_service = null, ?WooPaymentsOverviewService $overview_service = null ): void {
		$this->payments              = $payments;
		$this->woopayments           = $woopayments;
		$this->settings_service      = $settings_service;
		$this->runtime_arbiter       = $runtime_arbiter;
		$this->pm_promotions_service = $pm_promotions_service;
		$this->overview_service      = $overview_service;
	}

	/**
	 * Register the legacy-compatible native WooPayments settings endpoints.
	 *
	 * @param bool $override Whether to override existing routes.
	 * @return void
	 */
	private function register_native_settings_routes( bool $override ): void {
		register_rest_route(
			'wc/v3',
			'/payments/settings',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => fn( $request ) => $this->run( $request, 'get_native_settings' ),
					'permission_callback' => fn( $request ) => $this->check_permissions( $request ),
				),
				array(
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => fn( $request ) => $this->run( $request, 'update_native_settings' ),
					'permission_callback' => fn( $request ) => $this->check_permissions( $request ),
					'args'                => $this->get_native_settings_update_args(),
				),
			),
			$override
		);

		register_rest_route(
			'wc/v3',
			'/payments/settings/(?P<option_name>[a-zA-Z0-9_-]+)',
			array(
				array(
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => fn( $request ) => $this->run( $request, 'update_native_settings_option' ),
					'permission_callback' => fn( $request ) => $this->check_permissions( $request ),
					'args'                => array(
						'option_name' => array(
							'required'          => true,
							'sanitize_callback' => 'sanitize_key',
						),
						'value'       => array(
							'required' => true,
						),
					),
				),
			),
			$override
		);

		register_rest_route(
			'wc/v3',
			'/payments/pm-promotions',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => fn( $request ) => $this->run( $request, 'get_native_pm_promotions' ),
					'permission_callback' => fn( $request ) => $this->check_permissions( $request ),
				),
			),
			$override
		);

		register_rest_route(
			'wc/v3',
			'/payments/pm-promotions/(?P<promotion_id>[^/]+)/activate',
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => fn( $request ) => $this->run( $request, 'activate_native_pm_promotion' ),
					'permission_callback' => fn( $request ) => $this->check_permissions( $request ),
					'args'                => $this->get_pm_promotion_route_args(),
				),
			),
			$override
		);

		register_rest_route(
			'wc/v3',
			'/payments/pm-promotions/(?P<promotion_id>[^/]+)/dismiss',
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => fn( $request ) => $this->run( $request, 'dismiss_native_pm_promotion' ),
					'permission_callback' => fn( $request ) => $this->check_permissions( $request ),
					'args'                => $this->get_pm_promotion_route_args(),
				),
			),
			$override
		);

		register_rest_route(
			'wc/v3',
			'/payments/file',
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => fn( $request ) => $this->run( $request, 'upload_native_settings_file' ),
					'permission_callback' => fn( $request ) => $this->check_permissions( $request ),
				),
			),
			$override
		);

		register_rest_route(
			'wc/v3',
			'/payments/file/(?P<file_id>\w+)/details',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => fn( $request ) => $this->run( $request, 'get_native_settings_file_details' ),
					'permission_callback' => fn( $request ) => $this->check_permissions( $request ),
					'args'                => $this->get_file_route_args(),
				),
			),
			$override
		);

		register_rest_route(
			'wc/v3',
			'/payments/file/(?P<file_id>\w+)/content',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => fn( $request ) => $this->run( $request, 'get_native_settings_file_contents' ),
					'permission_callback' => fn( $request ) => $this->check_permissions( $request ),
					'args'                => $this->get_file_route_args(),
				),
			),
			$override
		);

		register_rest_route(
			'wc/v3',
			'/payments/file/(?P<file_id>\w+)',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => fn( $request ) => $this->run( $request, 'get_native_public_settings_file' ),
					'permission_callback' => '__return_true',
					'args'                => $this->get_file_route_args(),
				),
			),
			$override
		);
	}

	/**
	 * Tell whether native WooPayments settings routes may register for this request.
	 *
	 * @return bool
	 */
	private function should_register_native_settings_routes(): bool {
		return null === $this->runtime_arbiter || $this->runtime_arbiter->should_native_register();
	}

	/**
	 * Get validation args for the legacy-compatible WooPayments settings update route.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	private function get_native_settings_update_args(): array {
		$payment_method_ids = WooPaymentsSettingsService::get_supported_payment_method_ids();
		$express_method_ids = WooPaymentsSettingsService::get_express_checkout_method_ids();
		$args               = array(
			'enabled_payment_method_ids'           => $this->get_string_array_arg( $payment_method_ids ),
			'express_checkout_product_methods'     => $this->get_string_array_arg( $express_method_ids ),
			'express_checkout_cart_methods'        => $this->get_string_array_arg( $express_method_ids ),
			'express_checkout_checkout_methods'    => $this->get_string_array_arg( $express_method_ids ),
			'payment_request_button_border_radius' => $this->get_typed_arg( 'integer' ),
			'deposit_schedule_monthly_anchor'      => $this->get_typed_arg( array( 'integer', 'null' ) ),
			'advanced_fraud_protection_settings'   => $this->get_advanced_fraud_protection_settings_arg(),
			'account_business_support_address'     => $this->get_typed_arg( 'object' ),
		);

		foreach (
			array(
				'is_wcpay_enabled',
				'is_manual_capture_enabled',
				'is_test_mode_enabled',
				'is_debug_log_enabled',
				'is_saved_cards_enabled',
				'is_payment_request_enabled',
				'is_express_checkout_in_payment_methods_enabled',
				'is_woopay_enabled',
				'is_woopay_global_theme_support_enabled',
				'is_multi_currency_enabled',
				'is_wcpay_subscriptions_enabled',
			) as $key
		) {
			$args[ $key ] = $this->get_typed_arg( 'boolean' );
		}

		foreach (
			array(
				'payment_request_button_size',
				'payment_request_button_type',
				'payment_request_button_theme',
				'woopay_custom_message',
				'woopay_store_logo',
				'account_statement_descriptor',
				'account_statement_descriptor_kanji',
				'account_statement_descriptor_kana',
				'account_business_name',
				'account_business_url',
				'account_business_support_email',
				'account_business_support_phone',
				'account_branding_logo',
				'account_branding_icon',
				'account_branding_primary_color',
				'account_branding_secondary_color',
				'account_communications_email',
				'deposit_schedule_interval',
				'deposit_schedule_weekly_anchor',
				'current_protection_level',
			) as $key
		) {
			$args[ $key ] = $this->get_typed_arg( 'string' );
		}

		return $args;
	}

	/**
	 * Get a REST arg schema for typed scalar or array settings.
	 *
	 * @param string|string[] $type JSON schema type.
	 * @return array<string,mixed>
	 */
	private function get_typed_arg( $type ): array {
		return array(
			'type'              => $type,
			'required'          => false,
			'validate_callback' => 'rest_validate_request_arg',
		);
	}

	/**
	 * Get a REST arg schema for advanced fraud protection settings.
	 *
	 * The settings GET contract can expose the string "error" sentinel when the platform ruleset is unavailable. The POST route must accept that sentinel for round-trips without accepting arbitrary strings as fraud rulesets.
	 *
	 * @return array<string,mixed>
	 */
	private function get_advanced_fraud_protection_settings_arg(): array {
		return array(
			'type'              => array( 'string', 'array' ),
			'required'          => false,
			'validate_callback' => static function ( $value, WP_REST_Request $request, string $param ) {
				unset( $request );

				$validation = rest_validate_value_from_schema(
					$value,
					array(
						'type' => array( 'string', 'array' ),
					),
					$param
				);
				if ( is_wp_error( $validation ) ) {
					return $validation;
				}

				if ( is_string( $value ) && 'error' !== $value ) {
					return new WP_Error(
						'rest_invalid_param',
						esc_html__( 'The advanced fraud protection settings field accepts only the error sentinel or a ruleset array.', 'woocommerce' ),
						array( 'status' => 400 )
					);
				}

				return true;
			},
		);
	}

	/**
	 * Get a REST arg schema for string arrays.
	 *
	 * @param string[] $allowed_values Allowed values.
	 * @return array<string,mixed>
	 */
	private function get_string_array_arg( array $allowed_values ): array {
		return array(
			'type'              => 'array',
			'required'          => false,
			'uniqueItems'       => true,
			'maxItems'          => count( $allowed_values ),
			'items'             => array(
				'type' => 'string',
				'enum' => $allowed_values,
			),
			'validate_callback' => 'rest_validate_request_arg',
		);
	}

	/**
	 * Get validation args for file routes.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	private function get_file_route_args(): array {
		return array(
			'file_id'    => array(
				'required'          => true,
				'type'              => 'string',
				'pattern'           => '\w+',
				'validate_callback' => 'rest_validate_request_arg',
			),
			'as_account' => array(
				'required'          => false,
				'type'              => 'boolean',
				'validate_callback' => 'rest_validate_request_arg',
			),
		);
	}

	/**
	 * Get validation args for payment method promotion action routes.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	private function get_pm_promotion_route_args(): array {
		return array(
			'promotion_id' => array(
				'required'          => true,
				'type'              => 'string',
				'validate_callback' => fn( $value ) => $this->validate_pm_promotion_id( $value ),
			),
		);
	}

	/**
	 * Validate a payment method promotion ID.
	 *
	 * @param mixed $value Promotion ID.
	 * @return WP_Error|true
	 */
	private function validate_pm_promotion_id( $value ) {
		if ( ! is_string( $value ) || '' === $value || ! preg_match( '/^[A-Za-z0-9_-]+$/', $value ) ) {
			return new WP_Error(
				'rest_invalid_param',
				esc_html__( 'Invalid payment method promotion ID.', 'woocommerce' ),
				array( 'status' => 400 )
			);
		}

		return true;
	}

	/**
	 * Get the onboarding details for the given location.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return WP_Error|WP_REST_Response The response or error.
	 */
	protected function get_onboarding_details( WP_REST_Request $request ) {
		$location = $request->get_param( 'location' );
		if ( empty( $location ) ) {
			// Fall back to the providers country if no location is provided.
			$location = $this->payments->get_country();
		}

		$source = $request->get_param( 'source' );

		try {
			$onboarding_details = $this->woopayments->get_onboarding_details( $location, $this->get_rest_url_path( 'onboarding' ), $source );
		} catch ( ApiException $e ) {
			return new WP_Error( $e->getErrorCode(), $e->getMessage(), array( 'status' => $e->getCode() ) );
		} catch ( Exception $e ) {
			return new WP_Error( 'woocommerce_rest_woopayments_onboarding_error', $e->getMessage(), array( 'status' => WP_Http::INTERNAL_SERVER_ERROR ) );
		}

		return rest_ensure_response( $this->prepare_onboarding_details_response( $onboarding_details ) );
	}

	/**
	 * Handle the onboarding step start action.
	 *
	 * @param WP_REST_Request $request The request object.
	 *
	 * @return WP_Error|WP_REST_Response The response or error.
	 */
	protected function handle_onboarding_step_start( WP_REST_Request $request ) {
		$step_id = $request->get_param( 'step' ) ?? '';

		$location = $request->get_param( 'location' );
		if ( empty( $location ) ) {
			// Fall back to the providers country if no location is provided.
			$location = $this->payments->get_country();
		}

		$source = $request->get_param( 'source' );

		try {
			$previous_status = $this->woopayments->get_onboarding_step_status( $step_id, $location );

			$this->woopayments->mark_onboarding_step_started( $step_id, $location, false, $source );

			$response = array(
				'success'         => true,
				'previous_status' => $previous_status,
				'current_status'  => $this->woopayments->get_onboarding_step_status( $step_id, $location ),
			);
		} catch ( ApiException $e ) {
			return new WP_Error( $e->getErrorCode(), $e->getMessage(), array( 'status' => $e->getCode() ) );
		}

		return rest_ensure_response( $response );
	}

	/**
	 * Handle the onboarding step save action.
	 *
	 * @param WP_REST_Request $request The request object.
	 *
	 * @return WP_Error|WP_REST_Response The response.
	 */
	protected function handle_onboarding_step_save( WP_REST_Request $request ) {
		$step_id = $request->get_param( 'step' ) ?? '';

		$location = $request->get_param( 'location' );
		if ( empty( $location ) ) {
			// Fall back to the providers country if no location is provided.
			$location = $this->payments->get_country();
		}

		$source = $request->get_param( 'source' );

		try {
			$this->woopayments->onboarding_step_save( $step_id, $location, $request->get_params() );

			// If some step data was saved, we also ensure that the step is marked as started, if not already.
			// This way we maintain onboarding state consistency if the frontend does not call the start endpoint.
			$this->woopayments->mark_onboarding_step_started( $step_id, $location, false, $source );
		} catch ( ApiException $e ) {
			return new WP_Error( $e->getErrorCode(), $e->getMessage(), array( 'status' => $e->getCode() ) );
		}

		return rest_ensure_response( array( 'success' => true ) );
	}

	/**
	 * Handle the onboarding step check action.
	 *
	 * @param WP_REST_Request $request The request object.
	 *
	 * @return WP_Error|WP_REST_Response The response or error.
	 */
	protected function handle_onboarding_step_check( WP_REST_Request $request ) {
		$step_id = $request->get_param( 'step' ) ?? '';

		$location = $request->get_param( 'location' );
		if ( empty( $location ) ) {
			// Fall back to the providers country if no location is provided.
			$location = $this->payments->get_country();
		}

		try {
			$result = $this->woopayments->onboarding_step_check( $step_id, $location );
		} catch ( ApiException $e ) {
			return new WP_Error( $e->getErrorCode(), $e->getMessage(), array( 'status' => $e->getCode() ) );
		}

		// Merge the result with the success flag.
		$response = array_merge( array( 'success' => true ), $result );

		return rest_ensure_response( $response );
	}

	/**
	 * Handle the onboarding step finish action.
	 *
	 * @param WP_REST_Request $request The request object.
	 *
	 * @return WP_Error|WP_REST_Response The response or error.
	 */
	protected function handle_onboarding_step_finish( WP_REST_Request $request ) {
		$step_id = $request->get_param( 'step' ) ?? '';

		$location = $request->get_param( 'location' );
		if ( empty( $location ) ) {
			// Fall back to the providers country if no location is provided.
			$location = $this->payments->get_country();
		}

		$source = $request->get_param( 'source' );

		try {
			$previous_status = $this->woopayments->get_onboarding_step_status( $step_id, $location );

			$this->woopayments->mark_onboarding_step_completed( $step_id, $location, false, $source );

			$response = array(
				'success'         => true,
				'previous_status' => $previous_status,
				'current_status'  => $this->woopayments->get_onboarding_step_status( $step_id, $location ),
			);
		} catch ( ApiException $e ) {
			return new WP_Error( $e->getErrorCode(), $e->getMessage(), array( 'status' => $e->getCode() ) );
		}

		return rest_ensure_response( $response );
	}

	/**
	 * Handle the onboarding step clean action.
	 *
	 * @param WP_REST_Request $request The request object.
	 *
	 * @return WP_Error|WP_REST_Response The response or error.
	 */
	protected function handle_onboarding_step_clean( WP_REST_Request $request ) {
		$step_id = $request->get_param( 'step' ) ?? '';

		$location = $request->get_param( 'location' );
		if ( empty( $location ) ) {
			// Fall back to the providers country if no location is provided.
			$location = $this->payments->get_country();
		}

		try {
			$previous_status = $this->woopayments->get_onboarding_step_status( $step_id, $location );

			$this->woopayments->clean_onboarding_step_progress( $step_id, $location );

			$response = array(
				'success'         => true,
				'previous_status' => $previous_status,
				'current_status'  => $this->woopayments->get_onboarding_step_status( $step_id, $location ),
			);
		} catch ( ApiException $e ) {
			return new WP_Error( $e->getErrorCode(), $e->getMessage(), array( 'status' => $e->getCode() ) );
		}

		return rest_ensure_response( $response );
	}

	/**
	 * Handle the onboarding test account initialize action.
	 *
	 * @param WP_REST_Request $request The request object.
	 *
	 * @return WP_Error|WP_REST_Response The response or error.
	 */
	protected function handle_onboarding_test_account_init( WP_REST_Request $request ) {
		$location = $request->get_param( 'location' );
		if ( empty( $location ) ) {
			// Fall back to the providers country if no location is provided.
			$location = $this->payments->get_country();
		}

		$source = $request->get_param( 'source' );

		try {
			// Mark the step as started, if not already.
			$this->woopayments->mark_onboarding_step_started( WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT, $location, false, $source );

			$result = $this->woopayments->onboarding_test_account_init( $location, $source );
		} catch ( ApiException $e ) {
			return new WP_Error( $e->getErrorCode(), $e->getMessage(), array( 'status' => $e->getCode() ) );
		}

		return rest_ensure_response(
			array_merge(
				array( 'success' => true ),
				$result
			)
		);
	}

	/**
	 * Handle the onboarding test account reset action.
	 *
	 * @param WP_REST_Request $request The request object.
	 *
	 * @return WP_Error|WP_REST_Response The response or error.
	 */
	protected function handle_onboarding_test_account_reset( WP_REST_Request $request ) {
		$location = $request->get_param( 'location' );
		if ( empty( $location ) ) {
			// Fall back to the providers country if no location is provided.
			$location = $this->payments->get_country();
		}

		$source = $request->get_param( 'source' );

		// For now, just "forward" the request to the generic onboarding reset endpoint.
		$request->set_param( 'location', $location );
		$request->set_param( 'from', WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT );
		$request->set_param( 'source', $source );
		return $this->reset_onboarding( $request );
	}

	/**
	 * Handle the onboarding business verification step KYC session initialization action.
	 *
	 * @param WP_REST_Request $request The request object.
	 *
	 * @return WP_Error|WP_REST_Response The response or error.
	 */
	protected function handle_onboarding_business_verification_kyc_session_init( WP_REST_Request $request ) {
		// If we receive self-assessment data with the request, we will use it.
		$self_assessment = ! empty( $request->get_param( 'self_assessment' ) ) ? wc_clean( wp_unslash( $request->get_param( 'self_assessment' ) ) ) : array();

		$location = $request->get_param( 'location' );
		if ( empty( $location ) ) {
			// Fall back to the providers country if no location is provided.
			$location = $this->payments->get_country();
		}

		$source = $request->get_param( 'source' );

		try {
			$account_session = $this->woopayments->get_onboarding_kyc_session( $location, $self_assessment, $source );
		} catch ( ApiException $e ) {
			return new WP_Error( $e->getErrorCode(), $e->getMessage(), array( 'status' => $e->getCode() ) );
		}

		return rest_ensure_response(
			array(
				'success' => true,
				'session' => $account_session,
			)
		);
	}

	/**
	 * Handle the onboarding business verification step KYC session finish action.
	 *
	 * @param WP_REST_Request $request The request object.
	 *
	 * @return WP_Error|WP_REST_Response The response or error.
	 */
	protected function handle_onboarding_business_verification_kyc_session_finish( WP_REST_Request $request ) {
		$location = $request->get_param( 'location' );
		if ( empty( $location ) ) {
			// Fall back to the providers country if no location is provided.
			$location = $this->payments->get_country();
		}

		$source = $request->get_param( 'source' );

		try {
			$response = $this->woopayments->finish_onboarding_kyc_session( $location, $source );
		} catch ( ApiException $e ) {
			return new WP_Error( $e->getErrorCode(), $e->getMessage(), array( 'status' => $e->getCode() ) );
		}

		// If there is no success key in the response, we assume the operation was successful.
		if ( ! isset( $response['success'] ) ) {
			$response['success'] = true;
		}

		return rest_ensure_response( $response );
	}

	/**
	 * Handle the onboarding preload action.
	 *
	 * @param WP_REST_Request $request The request object.
	 *
	 * @return WP_Error|WP_REST_Response The response or error.
	 */
	protected function handle_onboarding_preload( WP_REST_Request $request ) {
		$location = $request->get_param( 'location' );
		if ( empty( $location ) ) {
			// Fall back to the providers country if no location is provided.
			$location = $this->payments->get_country();
		}

		try {
			$response = $this->woopayments->onboarding_preload( $location );
		} catch ( ApiException $e ) {
			return new WP_Error( $e->getErrorCode(), $e->getMessage(), array( 'status' => $e->getCode() ) );
		}

		// If there is no success key in the response, we assume the operation was successful.
		if ( ! isset( $response['success'] ) ) {
			$response['success'] = true;
		}

		return rest_ensure_response( $response );
	}

	/**
	 * Handle the onboarding reset action.
	 *
	 * @param WP_REST_Request $request The request object.
	 *
	 * @return WP_Error|WP_REST_Response The response or error.
	 */
	protected function reset_onboarding( WP_REST_Request $request ) {
		$location = $request->get_param( 'location' );
		if ( empty( $location ) ) {
			// Fall back to the providers country if no location is provided.
			$location = $this->payments->get_country();
		}

		try {
			$this->woopayments->reset_onboarding( $location, $request->get_param( 'from' ) ?? '', $request->get_param( 'source' ) ?? '' );
		} catch ( ApiException $e ) {
			return new WP_Error( $e->getErrorCode(), $e->getMessage(), array( 'status' => $e->getCode() ) );
		}

		return rest_ensure_response(
			array(
				'success' => true,
			)
		);
	}

	/**
	 * Get a safe read-only account summary for the native WooPayments settings surface.
	 *
	 * @return WP_Error|WP_REST_Response The response or error.
	 */
	protected function get_account_summary() {
		try {
			$summary = $this->woopayments->get_account_summary();
		} catch ( Exception $e ) {
			return new WP_Error( 'woocommerce_rest_woopayments_account_error', $e->getMessage(), array( 'status' => WP_Http::INTERNAL_SERVER_ERROR ) );
		}

		return rest_ensure_response( $summary );
	}

	/**
	 * Get a safe read-only Overview projection for the native WooPayments settings surface.
	 *
	 * @return WP_Error|WP_REST_Response The response or error.
	 */
	protected function get_overview() {
		try {
			$overview = $this->get_overview_service()->get_overview();
		} catch ( Exception $e ) {
			return new WP_Error( 'woocommerce_rest_woopayments_overview_error', $e->getMessage(), array( 'status' => WP_Http::INTERNAL_SERVER_ERROR ) );
		}

		return rest_ensure_response( $overview );
	}

	/**
	 * Get the native WooPayments settings contract.
	 *
	 * @return WP_REST_Response The response.
	 */
	protected function get_native_settings(): WP_REST_Response {
		return rest_ensure_response( $this->get_settings_service()->get_settings() );
	}

	/**
	 * Update the native WooPayments settings contract.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @phpstan-param WP_REST_Request<array<string,mixed>> $request
	 * @return WP_Error|WP_REST_Response The response or error.
	 */
	protected function update_native_settings( WP_REST_Request $request ) {
		$result = $this->get_settings_service()->update_settings( $request->get_params() );
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return rest_ensure_response( $result );
	}

	/**
	 * Update an allowlisted native WooPayments settings option.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @phpstan-param WP_REST_Request<array<string,mixed>> $request
	 * @return WP_Error|WP_REST_Response The response or error.
	 */
	protected function update_native_settings_option( WP_REST_Request $request ) {
		$option_name = (string) $request->get_param( 'option_name' );
		$value       = $request->get_param( 'value' );
		$result      = $this->get_settings_service()->update_option( $option_name, $value );
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return rest_ensure_response( array( 'success' => true ) );
	}

	/**
	 * Get visible native WooPayments payment method promotions.
	 *
	 * @return WP_REST_Response The response.
	 */
	protected function get_native_pm_promotions(): WP_REST_Response {
		return rest_ensure_response( $this->get_pm_promotions_service()->get_visible_promotions() ?? array() );
	}

	/**
	 * Activate a native WooPayments payment method promotion.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @phpstan-param WP_REST_Request<array<string,mixed>> $request
	 * @return WP_REST_Response The response.
	 */
	protected function activate_native_pm_promotion( WP_REST_Request $request ): WP_REST_Response {
		return rest_ensure_response(
			array(
				'success' => $this->get_pm_promotions_service()->activate_promotion( (string) $request->get_param( 'promotion_id' ) ),
			)
		);
	}

	/**
	 * Dismiss a native WooPayments payment method promotion.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @phpstan-param WP_REST_Request<array<string,mixed>> $request
	 * @return WP_REST_Response The response.
	 */
	protected function dismiss_native_pm_promotion( WP_REST_Request $request ): WP_REST_Response {
		return rest_ensure_response(
			array(
				'success' => $this->get_pm_promotions_service()->dismiss_promotion( (string) $request->get_param( 'promotion_id' ) ),
			)
		);
	}

	/**
	 * Upload a file for the native WooPayments settings page.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @phpstan-param WP_REST_Request<array<string,mixed>> $request
	 * @return WP_Error|WP_REST_Response The response or error.
	 */
	protected function upload_native_settings_file( WP_REST_Request $request ) {
		$result = $this->get_settings_service()->upload_file( $request );
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return rest_ensure_response( $result );
	}

	/**
	 * Get provider file details for the native WooPayments settings page.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @phpstan-param WP_REST_Request<array<string,mixed>> $request
	 * @return WP_Error|WP_REST_Response The response or error.
	 */
	protected function get_native_settings_file_details( WP_REST_Request $request ) {
		$result = $this->get_settings_service()->get_file(
			(string) $request->get_param( 'file_id' ),
			(bool) $request->get_param( 'as_account' )
		);
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return rest_ensure_response( $result );
	}

	/**
	 * Get provider file contents for the native WooPayments settings page.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @phpstan-param WP_REST_Request<array<string,mixed>> $request
	 * @return WP_Error|WP_REST_Response The response or error.
	 */
	protected function get_native_settings_file_contents( WP_REST_Request $request ) {
		$result = $this->get_settings_service()->get_file_contents(
			(string) $request->get_param( 'file_id' ),
			(bool) $request->get_param( 'as_account' )
		);
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return rest_ensure_response( $result );
	}

	/**
	 * Get a provider file as inline bytes when it is public, or when the current user may manage payment gateways.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @phpstan-param WP_REST_Request<array<string,mixed>> $request
	 * @return WP_Error|WP_HTTP_Response The response or error.
	 */
	protected function get_native_public_settings_file( WP_REST_Request $request ) {
		$file_id    = (string) $request->get_param( 'file_id' );
		$as_account = (bool) $request->get_param( 'as_account' );
		$purpose    = $this->get_cached_file_purpose( $file_id, $as_account );

		if ( '' === $purpose ) {
			$file = $this->get_settings_service()->get_file( $file_id, $as_account );
			if ( is_wp_error( $file ) ) {
				return $this->get_file_error_response( $file );
			}

			$purpose = isset( $file['purpose'] ) && is_scalar( $file['purpose'] ) ? (string) $file['purpose'] : '';
			if ( '' !== $purpose ) {
				set_transient( $this->get_file_purpose_cache_key( $file_id, $as_account ), $purpose, DAY_IN_SECONDS );
			}
		}

		if ( ! $this->is_public_file_purpose( $purpose ) ) {
			$permission = $this->check_permissions( $request );
			if ( true !== $permission ) {
				return is_wp_error( $permission )
					? $permission
					: new WP_Error(
						'rest_forbidden',
						esc_html__( 'Sorry, you are not allowed to do that.', 'woocommerce' ),
						array( 'status' => rest_authorization_required_code() )
					);
			}
		}

		$contents = $this->get_settings_service()->get_file_contents( $file_id, $as_account );
		if ( is_wp_error( $contents ) ) {
			return $this->get_file_error_response( $contents );
		}

		$file_content = isset( $contents['file_content'] ) && is_scalar( $contents['file_content'] ) ? (string) $contents['file_content'] : '';
		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode -- Decoding provider file contents for the inline file response.
		$decoded_file = base64_decode( $file_content, true );
		if ( false === $decoded_file ) {
			return new WP_Error(
				'woocommerce_woopayments_file_content_invalid',
				esc_html__( 'Unable to read the file contents.', 'woocommerce' ),
				array( 'status' => WP_Http::INTERNAL_SERVER_ERROR )
			);
		}

		add_filter(
			'rest_pre_serve_request',
			static function ( bool $served, WP_HTTP_Response $response ): bool {
				$content_disposition = $response->get_headers()['Content-Disposition'] ?? '';
				if ( 'inline' !== $content_disposition ) {
					return $served;
				}

				echo $response->get_data(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- File bytes are intentionally streamed as the response body.
				return true;
			},
			10,
			2
		);

		$content_type = isset( $contents['content_type'] ) && is_scalar( $contents['content_type'] ) ? (string) $contents['content_type'] : 'application/octet-stream';

		return new WP_HTTP_Response(
			$decoded_file,
			200,
			array(
				'Content-Type'        => $content_type,
				'Content-Disposition' => 'inline',
			)
		);
	}

	/**
	 * Get a cached provider file purpose.
	 *
	 * @param string $file_id    Provider file ID.
	 * @param bool   $as_account Whether the file is fetched as the connected account.
	 * @return string
	 */
	private function get_cached_file_purpose( string $file_id, bool $as_account ): string {
		$purpose = get_transient( $this->get_file_purpose_cache_key( $file_id, $as_account ) );

		return is_string( $purpose ) ? $purpose : '';
	}

	/**
	 * Get the transient cache key for a provider file purpose.
	 *
	 * @param string $file_id    Provider file ID.
	 * @param bool   $as_account Whether the file is fetched as the connected account.
	 * @return string
	 */
	private function get_file_purpose_cache_key( string $file_id, bool $as_account ): string {
		return self::FILE_PURPOSE_CACHE_PREFIX . $file_id . '_' . ( $as_account ? '1' : '0' );
	}

	/**
	 * Tell whether a provider file purpose may be served publicly.
	 *
	 * @param string $purpose Provider file purpose.
	 * @return bool
	 */
	private function is_public_file_purpose( string $purpose ): bool {
		return in_array( $purpose, self::PUBLIC_FILE_PURPOSES, true );
	}

	/**
	 * Normalize public file route errors to the reference REST contract.
	 *
	 * @param WP_Error $error File API error.
	 * @return WP_Error
	 */
	private function get_file_error_response( WP_Error $error ): WP_Error {
		$status = 'resource_missing' === $error->get_error_code() ? WP_Http::NOT_FOUND : WP_Http::INTERNAL_SERVER_ERROR;

		return new WP_Error(
			$error->get_error_code(),
			$error->get_error_message(),
			array( 'status' => $status )
		);
	}

	/**
	 * Get the native WooPayments settings service.
	 *
	 * @return WooPaymentsSettingsService
	 */
	private function get_settings_service(): WooPaymentsSettingsService {
		if ( ! $this->settings_service instanceof WooPaymentsSettingsService ) {
			$this->settings_service = wc_get_container()->get( WooPaymentsSettingsService::class );
		}

		return $this->settings_service;
	}

	/**
	 * Get the native WooPayments payment method promotions service.
	 *
	 * @return WooPaymentsPmPromotionsService
	 */
	private function get_pm_promotions_service(): WooPaymentsPmPromotionsService {
		if ( ! $this->pm_promotions_service instanceof WooPaymentsPmPromotionsService ) {
			$this->pm_promotions_service = wc_get_container()->get( WooPaymentsPmPromotionsService::class );
		}

		return $this->pm_promotions_service;
	}

	/**
	 * Get the native WooPayments Overview projection service.
	 *
	 * @return WooPaymentsOverviewService
	 */
	private function get_overview_service(): WooPaymentsOverviewService {
		if ( ! $this->overview_service instanceof WooPaymentsOverviewService ) {
			$this->overview_service = wc_get_container()->get( WooPaymentsOverviewService::class );
		}

		return $this->overview_service;
	}

	/**
	 * Handle the onboarding test mode disable action.
	 *
	 * @param WP_REST_Request $request The request object.
	 *
	 * @return WP_Error|WP_REST_Response The response or error.
	 */
	protected function handle_test_account_disable( WP_REST_Request $request ) {
		$location = $request->get_param( 'location' );
		if ( empty( $location ) ) {
			// Fall back to the providers country if no location is provided.
			$location = $this->payments->get_country();
		}

		try {
			$this->woopayments->disable_test_account(
				$location,
				$request->get_param( 'from' ) ?? '',
				$request->get_param( 'source' ) ?? ''
			);
		} catch ( ApiException $e ) {
			return new WP_Error( $e->getErrorCode(), $e->getMessage(), array( 'status' => $e->getCode() ) );
		}

		return rest_ensure_response(
			array(
				'success' => true,
			)
		);
	}

	/**
	 * Get WooPay eligibility status.
	 *
	 * @return WP_REST_Response The response.
	 */
	protected function get_woopay_eligibility() {
		// We use the Payments Settings stored business location to determine the eligibility.
		$location = $this->payments->get_country();

		$woopay_eligible_countries = array( 'US' );
		$is_eligible               = in_array( $location, $woopay_eligible_countries, true );

		return rest_ensure_response(
			array(
				'is_eligible' => $is_eligible,
			)
		);
	}


	/**
	 * General permissions check for WooPayments settings REST API endpoint.
	 *
	 * @param WP_REST_Request $request The request for which the permission is checked.
	 *
	 * @return bool|WP_Error True if the current user has the capability, otherwise an "Unauthorized" error or False if no error is available for the request method.
	 */
	private function check_permissions( WP_REST_Request $request ) {
		$context = 'read';
		if ( 'POST' === $request->get_method() ) {
			$context = 'edit';
		} elseif ( 'DELETE' === $request->get_method() ) {
			$context = 'delete';
		}

		if ( wc_rest_check_manager_permissions( 'payment_gateways', $context ) ) {
			return true;
		}

		$error_information = $this->get_authentication_error_by_method( $request->get_method() );
		if ( is_null( $error_information ) ) {
			return false;
		}

		return new WP_Error(
			$error_information['code'],
			$error_information['message'],
			array( 'status' => rest_authorization_required_code() )
		);
	}

	/**
	 * Validate the location argument.
	 *
	 * @param mixed           $value   Value of the argument.
	 * @param WP_REST_Request $request The current request object.
	 *
	 * @return WP_Error|true True if the location argument is valid, otherwise a WP_Error object.
	 */
	private function check_location_arg( $value, WP_REST_Request $request ) {
		// If the 'location' argument is not a string return an error.
		if ( ! is_string( $value ) ) {
			return new WP_Error( 'rest_invalid_param', esc_html__( 'The location argument must be a string.', 'woocommerce' ), array( 'status' => 400 ) );
		}

		// Get the registered attributes for this endpoint request.
		$attributes = $request->get_attributes();

		// Grab the location param schema.
		$args = $attributes['args']['location'];

		// If the location param doesn't match the regex pattern then we should return an error as well.
		if ( ! preg_match( '/^' . $args['pattern'] . '$/', $value ) ) {
			return new WP_Error( 'rest_invalid_param', esc_html__( 'The location argument must be a valid ISO3166 alpha-2 country code.', 'woocommerce' ), array( 'status' => 400 ) );
		}

		return true;
	}

	/**
	 * Prepare the response for the GET onboarding details request.
	 *
	 * @param array $response The response to prepare.
	 *
	 * @return array The prepared response.
	 */
	private function prepare_onboarding_details_response( array $response ): array {
		return $this->prepare_onboarding_details_response_recursive( $response, $this->get_schema_for_get_onboarding_details() );
	}

	/**
	 * Recursively prepare the response items for the GET onboarding details request.
	 *
	 * @param mixed $response_item The response item to prepare.
	 * @param array $schema        The schema to use for preparing the response.
	 *
	 * @return mixed The prepared response item.
	 */
	private function prepare_onboarding_details_response_recursive( $response_item, array $schema ) {
		if ( is_null( $response_item ) ) {
			return null;
		}

		if ( ! array_key_exists( 'properties', $schema ) ||
			! is_array( $schema['properties'] ) ) {

			// Filter out null values for loosely defined schema types.
			if ( is_array( $response_item ) ) {
				return ArrayUtil::filter_null_values_recursive( $response_item );
			}
			return $response_item;
		}

		$prepared_response = array();
		foreach ( $schema['properties'] as $key => $property_schema ) {
			if ( is_array( $response_item ) && array_key_exists( $key, $response_item ) ) {
				if ( is_array( $property_schema ) && array_key_exists( 'properties', $property_schema ) ) {
					$prepared_response[ $key ] = $this->prepare_onboarding_details_response_recursive( $response_item[ $key ], $property_schema );
				} elseif ( is_array( $property_schema ) && array_key_exists( 'items', $property_schema ) ) {
					$prepared_response[ $key ] = array_map(
						fn( $item ) => $this->prepare_onboarding_details_response_recursive( $item, $property_schema['items'] ),
						$response_item[ $key ]
					);
				} else {
					$prepared_response[ $key ] = $response_item[ $key ];
				}
			}
		}

		// Ensure the order is the same as in the schema.
		$prepared_response = array_merge( array_fill_keys( array_keys( $schema['properties'] ), null ), $prepared_response );

		// Remove any null values from the response.
		return ArrayUtil::filter_null_values_recursive( $prepared_response );
	}

	/**
	 * Get the schema for the GET onboarding details request.
	 *
	 * @return array[]
	 */
	private function get_schema_for_get_onboarding_details(): array {
		$schema               = array(
			'$schema' => 'http://json-schema.org/draft-04/schema#',
			'title'   => 'WooCommerce Settings Payments WooPayments onboarding details for the given location.',
			'type'    => 'object',
		);
		$schema['properties'] = array(
			'state'    => array(
				'type'        => 'object',
				'description' => esc_html__( 'The general state of the onboarding process.', 'woocommerce' ),
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
				'properties'  => array(
					'supported' => array(
						'type'        => 'boolean',
						'description' => esc_html__( 'Whether onboarding is supported.', 'woocommerce' ),
						'context'     => array( 'view', 'edit' ),
						'readonly'    => true,
					),
					'started'   => array(
						'type'        => 'boolean',
						'description' => esc_html__( 'Whether the onboarding process is started.', 'woocommerce' ),
						'context'     => array( 'view', 'edit' ),
						'readonly'    => true,
					),
					'completed' => array(
						'type'        => 'boolean',
						'description' => esc_html__( 'Whether the onboarding process is completed.', 'woocommerce' ),
						'context'     => array( 'view', 'edit' ),
						'readonly'    => true,
					),
					'test_mode' => array(
						'type'        => 'boolean',
						'description' => esc_html__( 'Whether the onboarding process is in test mode.', 'woocommerce' ),
						'context'     => array( 'view', 'edit' ),
						'readonly'    => true,
					),
					'dev_mode'  => array(
						'type'        => 'boolean',
						'description' => esc_html__( 'Whether WooPayments is in dev mode.', 'woocommerce' ),
						'context'     => array( 'view', 'edit' ),
						'readonly'    => true,
					),
				),
			),
			'messages' => array(
				'type'                 => 'object',
				'description'          => esc_html__( 'Various messages to possibly show the user.', 'woocommerce' ),
				'context'              => array( 'view', 'edit' ),
				'readonly'             => true,
				'additionalProperties' => array(
					'type'        => 'string',
					'description' => esc_html__( 'Message to show the user.', 'woocommerce' ),
					'readonly'    => true,
				),
			),
			'steps'    => array(
				'type'        => 'array',
				'description' => esc_html__( 'The onboarding steps.', 'woocommerce' ),
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
				'items'       => array(
					'type'       => 'object',
					'properties' => array(
						'id'             => array(
							'type'        => 'string',
							'description' => esc_html__( 'The unique identifier for the step.', 'woocommerce' ),
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'path'           => array(
							'type'        => 'string',
							'description' => esc_html__( 'The relative path of the step to use for frontend navigation.', 'woocommerce' ),
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'required_steps' => array(
							'type'        => 'array',
							'description' => esc_html__( 'The steps that are required to be completed before this step.', 'woocommerce' ),
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
							'items'       => array(
								'type' => 'string',
							),
						),
						'status'         => array(
							'type'        => 'string',
							'description' => esc_html__( 'The current status of the step.', 'woocommerce' ),
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
							'enum'        => array(
								WooPaymentsService::ONBOARDING_STEP_STATUS_NOT_STARTED,
								WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED,
								WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED,
							),
						),
						'errors'         => array(
							'type'        => 'array',
							'description' => esc_html__( 'Errors list for the step.', 'woocommerce' ),
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
							'items'       => array(
								'type'       => 'object',
								'properties' => array(
									'code'    => array(
										'type'     => 'string',
										'readonly' => true,
									),
									'message' => array(
										'type'     => 'string',
										'readonly' => true,
									),
									'context' => array(
										'type'     => 'object',
										'readonly' => true,
									),
								),
							),
						),
						'actions'        => array(
							'type'        => 'object',
							'description' => esc_html__( 'The available actions for the step.', 'woocommerce' ),
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
							'properties'  => array(
								'start'                => array(
									'type'        => 'object',
									'description' => esc_html__( 'Action to signal the step start.', 'woocommerce' ),
									'properties'  => $this->get_schema_properties_for_onboarding_step_action(),
									'context'     => array( 'view', 'edit' ),
									'readonly'    => true,
								),
								'save'                 => array(
									'type'        => 'object',
									'description' => esc_html__( 'Action to save step information in the database.', 'woocommerce' ),
									'properties'  => $this->get_schema_properties_for_onboarding_step_action(),
									'context'     => array( 'view', 'edit' ),
									'readonly'    => true,
								),
								'check'                => array(
									'type'        => 'object',
									'description' => esc_html__( 'Action to check the step status.', 'woocommerce' ),
									'properties'  => $this->get_schema_properties_for_onboarding_step_action(),
									'context'     => array( 'view', 'edit' ),
									'readonly'    => true,
								),
								'finish'               => array(
									'type'        => 'object',
									'description' => esc_html__( 'Action to signal the step completion.', 'woocommerce' ),
									'properties'  => $this->get_schema_properties_for_onboarding_step_action(),
									'context'     => array( 'view', 'edit' ),
									'readonly'    => true,
								),
								'clean'                => array(
									'type'        => 'object',
									'description' => esc_html__( 'Action to clean the step progress.', 'woocommerce' ),
									'properties'  => $this->get_schema_properties_for_onboarding_step_action(),
									'context'     => array( 'view', 'edit' ),
									'readonly'    => true,
								),
								'auth'                 => array(
									'type'        => 'object',
									'description' => esc_html__( 'Action to authorize the WPCOM connection.', 'woocommerce' ),
									'properties'  => $this->get_schema_properties_for_onboarding_step_action(),
									'context'     => array( 'view', 'edit' ),
									'readonly'    => true,
								),
								'init'                 => array(
									'type'        => 'object',
									'description' => esc_html__( 'Action to initialize a test account.', 'woocommerce' ),
									'properties'  => $this->get_schema_properties_for_onboarding_step_action(),
									'context'     => array( 'view', 'edit' ),
									'readonly'    => true,
								),
								'kyc_session'          => array(
									'type'        => 'object',
									'description' => esc_html__( 'Action to create or resume an embedded KYC session.', 'woocommerce' ),
									'properties'  => $this->get_schema_properties_for_onboarding_step_action(),
									'context'     => array( 'view', 'edit' ),
									'readonly'    => true,
								),
								'kyc_session_finish'   => array(
									'type'        => 'object',
									'description' => esc_html__( 'Action to finish an embedded KYC session.', 'woocommerce' ),
									'properties'  => $this->get_schema_properties_for_onboarding_step_action(),
									'context'     => array( 'view', 'edit' ),
									'readonly'    => true,
								),
								'kyc_fallback'         => array(
									'type'        => 'object',
									'description' => esc_html__( 'Action to use as a fallback when dealing with errors with the embedded KYC.', 'woocommerce' ),
									'properties'  => $this->get_schema_properties_for_onboarding_step_action(),
									'context'     => array( 'view', 'edit' ),
									'readonly'    => true,
								),
								'reset'                => array(
									'type'        => 'object',
									'description' => esc_html__( 'Action to reset the onboarding process, either partially, for a certain step, or fully.', 'woocommerce' ),
									'properties'  => $this->get_schema_properties_for_onboarding_step_action(),
									'context'     => array( 'view', 'edit' ),
									'readonly'    => true,
								),
								'test_account_disable' => array(
									'type'        => 'object',
									'description' => esc_html__( 'Action to disable the test account currently in use', 'woocommerce' ),
									'properties'  => $this->get_schema_properties_for_onboarding_step_action(),
									'context'     => array( 'view', 'edit' ),
									'readonly'    => true,
								),
							),
						),
						'context'        => array(
							'type'        => 'object',
							'description' => esc_html__( 'Various contextual data for the step to use.', 'woocommerce' ),
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
					),
				),
			),
			'context'  => array(
				'type'        => 'object',
				'description' => esc_html__( 'Various contextual data for the onboarding process to use.', 'woocommerce' ),
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
		);

		return $schema;
	}

	/**
	 * Get the schema properties for an onboarding step action.
	 *
	 * @return array[] The schema properties for an onboarding step action.
	 */
	private function get_schema_properties_for_onboarding_step_action(): array {
		return array(
			'type' => array(
				'type'        => 'string',
				'description' => esc_html__( 'The action type to determine how to use the URL.', 'woocommerce' ),
				'enum'        => array( WooPaymentsService::ACTION_TYPE_REST, WooPaymentsService::ACTION_TYPE_REDIRECT ),
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'href' => array(
				'type'        => 'string',
				'description' => esc_html__( 'The URL to use for the action.', 'woocommerce' ),
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
		);
	}
}
