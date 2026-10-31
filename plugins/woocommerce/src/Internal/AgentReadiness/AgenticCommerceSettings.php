<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\AgentReadiness;

use Automattic\WooCommerce\Internal\Features\FeaturesController;
use Automattic\WooCommerce\Internal\RegisterHooksInterface;

defined( 'ABSPATH' ) || exit;

/**
 * Adds the WooCommerce > Settings > Agentic commerce tab while the
 * agent_readiness feature is enabled, and names the options it saves.
 *
 * @since 11.4.0
 */
class AgenticCommerceSettings implements RegisterHooksInterface {

	public const FEATURE_ID = 'agent_readiness';

	public const TAB = 'agentic-commerce';

	public const PRIVACY_PAGE_OPTION = 'wp_page_for_privacy_policy';

	public const TERMS_PAGE_OPTION = 'woocommerce_terms_page_id';

	public const REFUND_RETURNS_PAGE_OPTION = 'woocommerce_refund_returns_page_id';

	public const SHIPPING_POLICY_PAGE_OPTION = 'woocommerce_shipping_policy_page_id';

	public const FAQ_PAGE_OPTION = 'woocommerce_faq_page_id';

	/**
	 * Saved now, sent to agents once UCP has a return policy description field.
	 */
	public const RETURN_POLICY_SUMMARY_OPTION = 'woocommerce_return_policy_summary';

	public const RETURN_POLICY_SUMMARY_MAX_LENGTH = 300;

	/**
	 * Features controller.
	 *
	 * @var FeaturesController
	 */
	private FeaturesController $features_controller;

	/**
	 * Store conditions.
	 *
	 * @var StoreConditions
	 */
	private StoreConditions $store_conditions;

	/**
	 * Readiness service.
	 *
	 * @var AgentReadiness
	 */
	private AgentReadiness $agent_readiness;

	/**
	 * UCP switch.
	 *
	 * @var UcpSwitch
	 */
	private UcpSwitch $ucp_switch;

	/**
	 * Background scan.
	 *
	 * @var ScheduledScan
	 */
	private ScheduledScan $scheduled_scan;

	/**
	 * Initialize dependencies.
	 *
	 * @internal
	 *
	 * @param FeaturesController $features_controller Features controller.
	 * @param StoreConditions    $store_conditions    Store conditions.
	 * @param AgentReadiness     $agent_readiness     Readiness service.
	 * @param UcpSwitch          $ucp_switch          UCP switch.
	 * @param ScheduledScan      $scheduled_scan      Background scan.
	 */
	final public function init( FeaturesController $features_controller, StoreConditions $store_conditions, AgentReadiness $agent_readiness, UcpSwitch $ucp_switch, ScheduledScan $scheduled_scan ): void {
		$this->features_controller = $features_controller;
		$this->store_conditions    = $store_conditions;
		$this->agent_readiness     = $agent_readiness;
		$this->ucp_switch          = $ucp_switch;
		$this->scheduled_scan      = $scheduled_scan;
	}

	/**
	 * Register hooks. The feature check waits for init, since feature definitions load translations.
	 */
	public function register(): void {
		add_action( 'init', array( $this, 'handle_init' ) );
	}

	/**
	 * Add the settings page when the feature is enabled.
	 *
	 * @internal
	 */
	public function handle_init(): void {
		if ( ! is_admin() || ! $this->features_controller->feature_is_enabled( self::FEATURE_ID ) ) {
			return;
		}
		add_filter( 'woocommerce_get_settings_pages', array( $this, 'handle_woocommerce_get_settings_pages' ) );
	}

	/**
	 * Add the Agentic commerce page to the settings pages.
	 *
	 * @internal
	 *
	 * @param mixed $pages Settings pages.
	 * @return mixed
	 */
	public function handle_woocommerce_get_settings_pages( $pages ) {
		if ( ! is_array( $pages ) || ! class_exists( \WC_Settings_Page::class, false ) ) {
			return $pages;
		}
		$pages[] = new AgenticCommerceSettingsPage( $this->store_conditions, $this->agent_readiness, $this->ucp_switch, $this->scheduled_scan );
		return $pages;
	}
}
