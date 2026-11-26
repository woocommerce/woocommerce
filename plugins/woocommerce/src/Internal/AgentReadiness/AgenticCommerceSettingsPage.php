<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\AgentReadiness;

use Automattic\WooCommerce\StoreApi\Utilities\Ucp\Utils as UcpUtils;

defined( 'ABSPATH' ) || exit;

/**
 * WooCommerce > Settings > AI tools. It opens on the Overview, a React view with the WordPress.com connection
 * card, the AI shopping card and the Sales readiness card; Manage opens AI shopping, a React view with the UCP switch, Product visibility, the
 * Setup checklist, the store policy fields and the connection URL. settings-embed mounts both, from data
 * this class builds. The UCP switch saves on its own through wc/v3/agent-readiness/ucp; the store policy
 * fields save with Save, which posts this settings form. Only created by AgenticCommerceSettings,
 * after WC_Settings_Page has loaded.
 *
 * @since 11.4.0
 */
class AgenticCommerceSettingsPage extends \WC_Settings_Page {

	use ReadinessMarkup;

	private const POLICIES_SECTION = 'agentic_commerce_policies';

	/**
	 * Marks the page dropdowns on this tab, which share options with other tabs.
	 */
	private const PAGE_FIELD_KEY = 'agentic_commerce_page';

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
	 * Sales readiness, linked from Status.
	 *
	 * @var ReadinessCheckPage
	 */
	private ReadinessCheckPage $readiness_page;

	/**
	 * Constructor.
	 *
	 * @param StoreConditions    $store_conditions Store conditions.
	 * @param AgentReadiness     $agent_readiness  Readiness service.
	 * @param ReadinessCheckPage $readiness_page   Sales readiness.
	 */
	public function __construct( StoreConditions $store_conditions, AgentReadiness $agent_readiness, ReadinessCheckPage $readiness_page ) {
		$this->id               = AgenticCommerceSettings::TAB;
		$this->label            = __( 'AI tools', 'woocommerce' );
		$this->store_conditions = $store_conditions;
		$this->agent_readiness  = $agent_readiness;
		$this->readiness_page   = $readiness_page;

		parent::__construct();

		add_filter( 'woocommerce_admin_settings_sanitize_option', array( $this, 'sanitize_page_option' ), 10, 3 );
	}

	/**
	 * The Overview and AI shopping.
	 *
	 * @return array<string, string>
	 */
	protected function get_own_sections() {
		return array(
			''                                        => __( 'Overview', 'woocommerce' ),
			AgenticCommerceSettings::SHOPPING_SECTION => __( 'AI shopping', 'woocommerce' ),
		);
	}

	/**
	 * No section links: the landing page's cards and AI shopping's breadcrumb replace them.
	 *
	 * @return void
	 */
	public function output_sections() {
	}

	/**
	 * The landing page has no fields.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	protected function get_settings_for_default_section() {
		return array();
	}

	/**
	 * A page title for a dropdown. Tags are removed and entities decoded, so a stored title shows as the merchant wrote it.
	 *
	 * @param \WP_Post $page Page.
	 * @return string
	 */
	private static function page_title( \WP_Post $page ): string {
		return html_entity_decode( wp_strip_all_tags( get_the_title( $page ) ), ENT_QUOTES, 'UTF-8' );
	}

	/**
	 * The store policy fields, on AI shopping. The privacy policy page is a WordPress setting, so it is
	 * listed, and saved, only for users who can manage privacy options.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	protected function get_settings_for_shopping_section() {
		$store_pages = StoreConditions::store_page_ids();
		$pages       = array( '' => __( 'Choose a page', 'woocommerce' ) );
		$published   = get_pages(
			array(
				'post_status' => 'publish',
				'exclude'     => $store_pages,
			)
		);
		foreach ( is_array( $published ) ? $published : array() as $page ) {
			$pages[ (string) $page->ID ] = self::page_title( $page );
		}
		$page_field = static function ( string $option, string $title ) use ( $pages, $store_pages ): array {
			$options = $pages;
			$stored  = get_post( (int) get_option( $option ) );
			// A stored page that can't be chosen here stays listed, so saving another field keeps it.
			if ( $stored instanceof \WP_Post && 'page' === $stored->post_type && ! isset( $options[ (string) $stored->ID ] ) ) {
				$status = get_post_status_object( $stored->post_status );
				$state  = in_array( $stored->ID, $store_pages, true ) ? __( 'Store page', 'woocommerce' ) : ( $status ? $status->label : $stored->post_status );
				/* translators: 1: page title, 2: page status, such as Draft, or "Store page". */
				$options[ (string) $stored->ID ] = sprintf( __( '%1$s (%2$s)', 'woocommerce' ), self::page_title( $stored ), $state );
			}
			return array(
				'title'              => $title,
				'id'                 => $option,
				'type'               => 'select',
				'options'            => $options,
				'default'            => '',
				'css'                => 'min-width:300px;',
				'autoload'           => in_array( $option, array( AgenticCommerceSettings::PRIVACY_PAGE_OPTION, AgenticCommerceSettings::TERMS_PAGE_OPTION ), true ),
				self::PAGE_FIELD_KEY => true,
			);
		};

		return array(
			array(
				'title' => __( 'Store policies', 'woocommerce' ),
				'type'  => 'title',
				'desc'  => __( 'AI assistants share these pages with shoppers. The pages you choose here are also used across the rest of your store.', 'woocommerce' ),
				'id'    => self::POLICIES_SECTION,
			),
			$page_field( AgenticCommerceSettings::REFUND_RETURNS_PAGE_OPTION, __( 'Refund and returns policy', 'woocommerce' ) ),
			$page_field( AgenticCommerceSettings::SHIPPING_POLICY_PAGE_OPTION, __( 'Shipping policy (optional)', 'woocommerce' ) ),
			...( current_user_can( 'manage_privacy_options' ) ? array( $page_field( AgenticCommerceSettings::PRIVACY_PAGE_OPTION, __( 'Privacy policy', 'woocommerce' ) ) ) : array() ),
			$page_field( AgenticCommerceSettings::TERMS_PAGE_OPTION, __( 'Terms and conditions', 'woocommerce' ) ),
			$page_field( AgenticCommerceSettings::FAQ_PAGE_OPTION, __( 'FAQ (optional)', 'woocommerce' ) ),
			$page_field( AgenticCommerceSettings::WARRANTY_PAGE_OPTION, __( 'Warranty policy (optional)', 'woocommerce' ) ),
			array(
				'type' => 'sectionend',
				'id'   => self::POLICIES_SECTION,
			),
		);
	}

	/**
	 * Render the current section. Both are React roots the settings-embed script mounts: the Overview, and
	 * AI shopping with the switch, Product visibility, the Setup checklist, the store policy fields and the
	 * connection URL. The data comes with the page, so the React views draw without a request.
	 */
	public function output(): void {
		global $current_section;

		if ( AgenticCommerceSettings::SHOPPING_SECTION !== $current_section ) {
			// Cards only; nothing here saves with Save changes.
			$GLOBALS['hide_save_button'] = true;
			printf(
				'<div class="wc-agentic-commerce"><div id="wc-agent-readiness-overview" class="wc-agent-readiness-overview" data-view="%s" data-connect="%s"></div></div>',
				esc_attr( self::view_json( $this->readiness_page->view() ) ),
				esc_attr( self::view_json( AgenticCommerceSettings::connect_view() ) )
			);
			return;
		}
		// Save is in the React page header. It submits this form, with the store policy fields as hidden
		// inputs, so they save and validate as settings fields do; the nonce stays under the form.
		// wc-settings-prevent-change-event keeps settings.js from adding its "Leave site?" prompt to the
		// switch, which saves on its own; React tracks unsaved fields itself.
		$GLOBALS['hide_save_button'] = true;
		printf(
			'<div id="wc-ai-shopping" class="wc-ai-shopping-root wc-settings-prevent-change-event" data-view="%s"></div>',
			esc_attr( self::view_json( $this->shopping_view() ) )
		);
	}

	/**
	 * A view as JSON for a data attribute. The hex flags encode &, quotes, < and > inside its strings, so
	 * an entity already in a value, such as &quot;, which esc_attr() leaves as it is, can't decode to a
	 * quote that ends a JSON string.
	 *
	 * @param array<string, mixed> $view View data.
	 * @return string JSON for the attribute.
	 */
	private static function view_json( array $view ): string {
		return (string) wp_json_encode( $view, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT );
	}

	/**
	 * Everything AI shopping shows, as data for its React view: the switch, the coming soon notice,
	 * Product visibility, the Setup checklist, the store policy fields with their pages, and the
	 * connection URL.
	 *
	 * @return array<string, mixed>
	 */
	private function shopping_view(): array {
		$report   = $this->agent_readiness->get_cached_report();
		$checks   = $this->store_conditions->all();
		$enabled  = UcpUtils::is_enabled();
		$failing  = $this->store_conditions->failing_required( $checks );
		$launched = current( array_filter( $checks, static fn( array $check ): bool => 'launched' === $check['id'] ) );

		return array(
			'parent'      => array(
				'href' => AgenticCommerceSettings::url(),
				'text' => $this->label,
			),
			'title'       => __( 'AI shopping', 'woocommerce' ),
			'description' => __( 'Control how AI assistants shop your store.', 'woocommerce' ),
			// The switch saves on its own, through wc/v3/agent-readiness/ucp. It can't be turned on while a
			// required step fails; turning it off always works.
			'ucp'         => array(
				'enabled'     => $enabled,
				'blocked'     => ! $enabled && array() !== $failing,
				'title'       => __( 'Allow AI assistants to shop your store', 'woocommerce' ),
				'description' => __( 'Your products can be found by AI assistants and added to a shopper’s cart. Orders come through your checkout as usual.', 'woocommerce' ),
				'learn_more'  => array(
					'href' => 'https://woocommerce.com/document/woocommerce-ai/agentic-commerce/',
					'text' => __( 'Learn how it works', 'woocommerce' ),
				),
				// The snackbar after the switch saves, as in the design.
				'turned_on'   => __( 'AI assistants can now shop your store.', 'woocommerce' ),
				'turned_off'  => __( 'AI assistants can no longer shop your store.', 'woocommerce' ),
				'path'        => '/wc/v3/agent-readiness/ucp',
				// Shown when the switch didn't save, unless the reason is required steps that aren't done.
				'error_on'    => __( 'AI shopping wasn’t turned on. Reload the page and try again.', 'woocommerce' ),
				'error_off'   => __( 'AI shopping wasn’t turned off. Reload the page and try again.', 'woocommerce' ),
				'unsaved'     => __( 'Save your changes to see the updated status.', 'woocommerce' ),
			),
			// Agents can't shop a store that isn't live.
			'coming_soon' => $launched && ! $launched['ok'] ? array(
				'text' => __( 'Your store is in coming soon mode, so AI assistants can’t find your products yet.', 'woocommerce' ),
				'link' => array(
					'href' => $launched['fix_url'],
					'text' => __( 'Change site visibility', 'woocommerce' ),
				),
			) : null,
			'visibility'  => self::visibility( $report ),
			'checklist'   => self::checklist( $checks ),
			'policies'    => array(
				'title'       => __( 'Store policies', 'woocommerce' ),
				'description' => __( 'AI assistants share these pages with shoppers. The pages you choose here are also used across the rest of your store.', 'woocommerce' ),
				'fields'      => $this->policy_fields(),
			),
			'connection'  => array(
				'title'       => __( 'Connection URL', 'woocommerce' ),
				'description' => __( 'A separate address, just for AI assistants to connect to your store. It was created automatically.', 'woocommerce' ),
				'url'         => home_url( '/.well-known/ucp' ),
				'copy'        => __( 'Copy', 'woocommerce' ),
				'copied'      => __( 'Copied', 'woocommerce' ),
				'test'        => array(
					'href' => 'https://ucpplayground.com',
					'text' => __( 'Test the connection in UCP Playground', 'woocommerce' ),
				),
			),
		);
	}

	/**
	 * Whether a setting of the AI shopping section is a store policy field, the only kind the page posts.
	 * Filtered settings may lack keys; only complete select fields become form controls.
	 *
	 * @param mixed $field Setting.
	 */
	private static function is_policy_field( $field ): bool {
		return is_array( $field ) && 'select' === ( $field['type'] ?? null ) && ! empty( $field['id'] ) && is_array( $field['options'] ?? null );
	}

	/**
	 * Save only the fields the AI shopping page posts, so a setting a filter adds to the section, which
	 * the page doesn't show, keeps its value instead of being reset.
	 */
	protected function save_settings_for_current_section(): void {
		global $current_section;

		if ( AgenticCommerceSettings::SHOPPING_SECTION !== $current_section ) {
			parent::save_settings_for_current_section();
			return;
		}
		\WC_Admin_Settings::save_fields( array_filter( $this->get_settings( $current_section ), array( self::class, 'is_policy_field' ) ) );
	}

	/**
	 * The store policy fields as the form saves them, with their pages as a list, so the order holds in
	 * JavaScript. A stored page that is no longer listed starts on "Choose a page", as a select would.
	 *
	 * @return array<int, array{id:string,name:string,label:string,value:string,options:array<int, array{value:string,label:string}>}>
	 */
	private function policy_fields(): array {
		$fields = array();
		foreach ( array_filter( $this->get_settings_for_section( AgenticCommerceSettings::SHOPPING_SECTION ), array( self::class, 'is_policy_field' ) ) as $field ) {
			$options = array();
			foreach ( $field['options'] as $value => $label ) {
				$options[] = array(
					'value' => (string) $value,
					'label' => (string) $label,
				);
			}
			$value    = (string) \WC_Admin_Settings::get_option( $field['id'], '' );
			$fields[] = array(
				'id'      => (string) $field['id'],
				// The key WC_Admin_Settings::save_fields() reads the posted value from.
				'name'    => (string) ( $field['field_name'] ?? $field['id'] ),
				'label'   => (string) ( $field['title'] ?? '' ),
				'value'   => in_array( $value, array_column( $options, 'value' ), true ) ? $value : '',
				'options' => $options,
			);
		}
		return $fields;
	}

	/**
	 * Accept only published pages from a page dropdown on this tab, or an empty
	 * value to clear it. A stored page that isn't published is listed in its own
	 * dropdown, so submitting it unchanged keeps it.
	 *
	 * @internal
	 *
	 * @param mixed $value     Sanitized value.
	 * @param mixed $option    Field definition.
	 * @param mixed $raw_value Posted value.
	 * @return mixed Null skips saving the option.
	 */
	public function sanitize_page_option( $value, $option, $raw_value ) {
		if ( ! is_array( $option ) || empty( $option[ self::PAGE_FIELD_KEY ] ) ) {
			return $value;
		}
		$page_id = absint( is_scalar( $raw_value ) ? $raw_value : 0 );
		if ( 0 === $page_id ) {
			return '';
		}
		if ( StoreConditions::is_policy_page( $page_id ) ) {
			return (string) $page_id;
		}
		return null;
	}

	/**
	 * Product visibility: the catalog counts as they stand now, each with a link to those products, or a
	 * line saying they show after the first check.
	 *
	 * @param array<string, mixed>|null $report Stored report, or null before the first scan.
	 * @return array{title:string,description:string,empty:string|null,counts:array<int, array{id:string,label:string,count:string,tip:string,tip_label:string,link:array{href:string,text:string}}>}
	 */
	private static function visibility( ?array $report ): array {
		$counts = array();
		if ( null !== $report ) {
			// Counted now: products added, hidden or removed since the last check count, as in the lists the cards link to.
			$report = array_merge( $report, ProductScan::catalog_counts() );
			$cards  = array(
				// The whole catalog, like the other two; `products` stops at PRODUCT_LIMIT. Reports from before `available` have only `products`.
				array( ProductScan::COUNT_AVAILABLE, __( 'Visible', 'woocommerce' ), (int) ( $report['available'] ?? $report['products'] ?? 0 ), '', '' ),
				array( ProductScan::COUNT_HIDDEN, __( 'Hidden', 'woocommerce' ), (int) ( $report['hidden_from_catalog'] ?? 0 ), __( 'Products set to Hidden in Catalog visibility.', 'woocommerce' ), __( 'More information about hidden products', 'woocommerce' ) ),
				array( ProductScan::COUNT_UNSUPPORTED, __( 'Not supported', 'woocommerce' ), (int) ( $report['unsupported_types'] ?? 0 ), __( 'AI assistants can find these products, but shoppers can\'t buy them through an assistant yet. This includes grouped, external, subscription, booking, and bundle products.', 'woocommerce' ), __( 'More information about products that aren’t supported', 'woocommerce' ) ),
			);
			foreach ( $cards as $card ) {
				$counts[] = array(
					'id'        => $card[0],
					'label'     => $card[1],
					'count'     => number_format_i18n( $card[2] ),
					'tip'       => $card[3],
					'tip_label' => $card[4],
					'link'      => array(
						'href' => ProductsList::url( $card[0] ),
						'text' => __( 'View products', 'woocommerce' ),
					),
				);
			}
		}
		return array(
			'title'       => __( 'Product visibility', 'woocommerce' ),
			'description' => __( 'AI assistants can see the same products as shoppers. For now, shoppers can only buy simple and variable products through an assistant.', 'woocommerce' ),
			'empty'       => null === $report ? __( 'Product counts show after the first AI shopping health check.', 'woocommerce' ) : null,
			'counts'      => $counts,
		);
	}

	/**
	 * The Setup checklist: how many steps are left, then the open setup checks in one list, required ones first and
	 * marked Required. Each says why it matters, with a button to where to fix it. Completed checks fold
	 * behind "Show N completed steps". UCP itself is the switch, so it is not listed.
	 *
	 * @param array<int, array{id:string,ok:bool,blocks:string,level:string,name:string,label:string,why:string,fix:string,fix_url:string}> $checks Store conditions.
	 * @return array<string, mixed>
	 */
	private static function checklist( array $checks ): array {
		$done     = ReadinessCheckPage::done_steps( $checks );
		$open     = ReadinessCheckPage::open_steps( $checks );
		$required = count( array_filter( $open, array( StoreConditions::class, 'shows_required' ) ) );
		if ( $required > 0 ) {
			$summary = ReadinessCheckPage::required_steps_summary( $required );
		} elseif ( array() !== $open ) {
			/* translators: %d: number of open setup steps. */
			$summary = sprintf( _n( 'Your store is ready for AI shopping. You have %d optional step left.', 'Your store is ready for AI shopping. You have %d optional steps left.', count( $open ), 'woocommerce' ), count( $open ) );
		} elseif ( UcpUtils::is_enabled() ) {
			$summary = __( 'Your store is ready for AI shopping.', 'woocommerce' );
		} else {
			$summary = __( 'Your store is ready for AI shopping. Turn it on to let AI assistants find your products.', 'woocommerce' );
		}

		$steps = array();
		foreach ( $open as $check ) {
			$action  = self::check_action( $check );
			$is_req  = StoreConditions::shows_required( $check );
			$label   = self::step_button( $check['id'], $action[3] );
			$steps[] = array(
				'id'    => $check['id'],
				'state' => $is_req ? 'required' : 'open',
				'title' => $action[0],
				'badge' => $is_req ? array(
					'intent' => 'low',
					'text'   => __( 'Required', 'woocommerce' ),
				) : null,
				'lines' => array_values( array_filter( array( $action[1], $action[4] ), static fn( string $line ): bool => '' !== trim( $line ) ) ),
				// Policy steps repeat the same button, so screen readers also hear which policy it is for.
				'link'  => '' === $action[2] ? null : array(
					'href' => $action[2],
					'text' => $label[0],
					'sr'   => $label[1],
				),
			);
		}
		$passed = array();
		// A completed step keeps its title and why it matters, as in the design.
		foreach ( $done as $check ) {
			$action   = self::passed_step( $check );
			$passed[] = array(
				'id'    => $check['id'],
				'title' => $action[0],
				'text'  => $action[1],
			);
		}

		// The badge counts what the summary counts: the required steps while any is left, then the optional ones.
		$left = $required > 0 ? $required : count( $open );
		return array(
			'title'     => __( 'Setup checklist', 'woocommerce' ),
			// As in the design: the steps left, or All steps done in green once none are.
			'badge'     => array() === $open ? array(
				'intent' => 'stable',
				'text'   => __( 'All steps done', 'woocommerce' ),
			) : array(
				'intent' => 'none',
				/* translators: %d: number of setup steps left. */
				'text'   => sprintf( _n( '%d step left', '%d steps left', $left, 'woocommerce' ), $left ),
			),
			'summary'   => $summary,
			'steps'     => $steps,
			'done'      => $passed,
			/* translators: %d: number of completed setup steps. */
			'show_done' => sprintf( _n( 'Show %d completed step', 'Show %d completed steps', count( $done ), 'woocommerce' ), count( $done ) ),
			'hide_done' => __( 'Hide completed steps', 'woocommerce' ),
		);
	}
}
