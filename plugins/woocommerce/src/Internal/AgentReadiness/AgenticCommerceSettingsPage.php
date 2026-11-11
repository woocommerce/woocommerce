<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\AgentReadiness;

use Automattic\WooCommerce\StoreApi\Utilities\Ucp\Utils as UcpUtils;

defined( 'ABSPATH' ) || exit;

/**
 * WooCommerce > Settings > AI tools. It opens on the Overview, a React view with the AI shopping card and the
 * Sales readiness card (settings-embed mounts it); Manage opens AI shopping, with the UCP switch, what AI assistants can see, Status, the store
 * policy fields and the store address. The UCP switch saves on its own through
 * wc/v3/agent-readiness/ucp; the store policy fields save with Save changes. Only created by
 * AgenticCommerceSettings while the agent_readiness feature is enabled, after WC_Settings_Page has loaded.
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
		add_action( 'woocommerce_settings_' . self::POLICIES_SECTION . '_after', array( $this, 'render_policies_footnote' ) );
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
	 * The store policy fields, on AI shopping. The privacy policy page is a WordPress setting, so it is
	 * listed, and saved, only for users who can manage privacy options.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	protected function get_settings_for_shopping_section() {
		$store_pages = StoreConditions::store_page_ids();
		$pages       = array( '' => __( 'Select a page…', 'woocommerce' ) );
		$published   = get_pages(
			array(
				'post_status' => 'publish',
				'exclude'     => $store_pages,
			)
		);
		foreach ( is_array( $published ) ? $published : array() as $page ) {
			$pages[ (string) $page->ID ] = $page->post_title;
		}
		$page_field = static function ( string $option, string $title, string $desc = '' ) use ( $pages, $store_pages ): array {
			$options = $pages;
			$stored  = get_post( (int) get_option( $option ) );
			// A stored page that can't be chosen here stays listed, so saving another field keeps it.
			if ( $stored instanceof \WP_Post && 'page' === $stored->post_type && ! isset( $options[ (string) $stored->ID ] ) ) {
				$status = get_post_status_object( $stored->post_status );
				$state  = in_array( $stored->ID, $store_pages, true ) ? __( 'Store page', 'woocommerce' ) : ( $status ? $status->label : $stored->post_status );
				/* translators: 1: page title, 2: page status, such as Draft, or "Store page". */
				$options[ (string) $stored->ID ] = sprintf( __( '%1$s (%2$s)', 'woocommerce' ), $stored->post_title, $state );
			}
			return array(
				'title'              => $title,
				'desc'               => $desc,
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
				'desc'  => __( 'AI assistants will be able to share these with customers before they buy. Changing a page here changes it everywhere in your store.', 'woocommerce' ),
				'id'    => self::POLICIES_SECTION,
			),
			$page_field( AgenticCommerceSettings::REFUND_RETURNS_PAGE_OPTION, __( 'Refund and returns', 'woocommerce' ) ),
			$page_field( AgenticCommerceSettings::SHIPPING_POLICY_PAGE_OPTION, __( 'Shipping policy', 'woocommerce' ) ),
			...( current_user_can( 'manage_privacy_options' ) ? array( $page_field( AgenticCommerceSettings::PRIVACY_PAGE_OPTION, __( 'Privacy policy', 'woocommerce' ) ) ) : array() ),
			$page_field( AgenticCommerceSettings::TERMS_PAGE_OPTION, __( 'Terms and conditions', 'woocommerce' ) ),
			$page_field( AgenticCommerceSettings::FAQ_PAGE_OPTION, __( 'FAQ', 'woocommerce' ), __( 'Optional.', 'woocommerce' ) ),
			$page_field( AgenticCommerceSettings::WARRANTY_PAGE_OPTION, __( 'Warranty', 'woocommerce' ), __( 'Optional.', 'woocommerce' ) ),
			array(
				'type' => 'sectionend',
				'id'   => self::POLICIES_SECTION,
			),
		);
	}

	/**
	 * Render the current section. The Overview leaves a root for its React view;
	 * AI shopping has the switch, what AI assistants can see, Status, the store policy fields and the
	 * store address.
	 */
	public function output(): void {
		global $current_section;

		$report = $this->agent_readiness->get_cached_report();
		$checks = $this->store_conditions->all();

		$this->enqueue_script();

		echo '<div class="wc-agentic-commerce">';
		if ( AgenticCommerceSettings::SHOPPING_SECTION !== $current_section ) {
			// Cards only; nothing here saves with Save changes.
			$GLOBALS['hide_save_button'] = true;
			// The Overview is React: the settings-embed script mounts it here with wc/v3/agent-readiness/view.
			echo '<div id="wc-agent-readiness-overview" class="wc-agent-readiness-overview"></div>';
			echo '</div>';
			return;
		}
		// Not a nav element: the settings form styles those as its tab bar.
		echo '<div class="wc-agentic-commerce-header"><div class="wc-agentic-commerce-breadcrumb" role="navigation" aria-label="' . esc_attr__( 'Breadcrumb', 'woocommerce' ) . '"><a href="' . esc_url( AgenticCommerceSettings::url() ) . '">' . esc_html( $this->label ) . '</a> <span aria-hidden="true">/</span> <span aria-current="page">' . esc_html__( 'AI shopping', 'woocommerce' ) . '</span></div>';
		echo '<p>' . esc_html__( 'Let AI shopping assistants find your products, build a cart, and send shoppers to your checkout to pay. Uses the Universal Commerce Protocol (UCP).', 'woocommerce' ) . '</p></div>';
		$this->render_switch( $checks );
		$this->render_visibility( $report );
		$this->render_status( $checks, $report );

		echo '<div class="wc-agentic-commerce-card wc-agentic-commerce-policies">';
		parent::output();
		echo '</div>';

		$this->render_address();
		echo '</div>';
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
	 * Footnote under the store policy fields.
	 *
	 * @internal
	 */
	public function render_policies_footnote(): void {
		echo '<p class="description">' . esc_html__( 'Refund and returns, privacy policy and terms and conditions are the pages your store already uses at checkout and in your footer. Changing a page here changes it everywhere in your store.', 'woocommerce' ) . '</p>';
	}

	/**
	 * Product counts from the last stored readiness report.
	 *
	 * @param array<string, mixed>|null $report Stored report, or null before the first scan.
	 */
	private function render_visibility( ?array $report ): void {
		echo '<div class="wc-agentic-commerce-card">';
		echo '<div class="wc-agentic-commerce-card__header"><h2>' . esc_html__( 'What AI assistants can see', 'woocommerce' ) . '</h2>';
		echo '<p>' . esc_html__( 'The same products your customers see in your catalog.', 'woocommerce' ) . '</p></div>';

		if ( null === $report ) {
			echo '<p class="wc-agentic-commerce-card__body">' . esc_html__( 'Product counts appear after the first readiness check.', 'woocommerce' ) . '</p></div>';
			return;
		}

		$cards = array(
			array( __( 'Available to AI assistants', 'woocommerce' ), (int) ( $report['products'] ?? 0 ), '', ProductScan::COUNT_AVAILABLE ),
			array( __( 'Hidden from your catalog', 'woocommerce' ), (int) ( $report['hidden_from_catalog'] ?? 0 ), __( 'Products with Catalog visibility set to Hidden or Search results only. They\'re hidden from customers too.', 'woocommerce' ), ProductScan::COUNT_HIDDEN ),
			array( __( 'Product type not supported yet', 'woocommerce' ), (int) ( $report['unsupported_types'] ?? 0 ), '', ProductScan::COUNT_UNSUPPORTED ),
		);

		echo '<ul class="wc-agentic-commerce-counts">';
		foreach ( $cards as $card ) {
			printf(
				'<li><span>%1$s%2$s</span> <strong>%3$s</strong> <a href="%4$s">%5$s</a></li>',
				esc_html( $card[0] ),
				'' === $card[2] ? '' : ' ' . wc_help_tip( $card[2] ),
				esc_html( number_format_i18n( $card[1] ) ),
				esc_url( ProductsList::url( $card[3] ) ),
				esc_html__( 'View products', 'woocommerce' )
			);
		}
		echo '</ul>';
		echo '<p class="wc-agentic-commerce-card__footer description">' . esc_html__( 'Supported now: simple and variable products. AI assistants can find grouped, external/affiliate, subscription, booking and bundle products, but can\'t buy them through your store yet.', 'woocommerce' ) . '</p>';
		echo '</div>';
	}

	/**
	 * The UCP switch card. The switch saves right away, apart from Save changes. While the
	 * store is in coming soon mode, the card says so: agents can't shop a store that isn't live.
	 *
	 * @param array<int, array{id:string,ok:bool,blocks:string,group:string,level:string,name:string,label:string,why:string,fix:string,fix_url:string}> $checks Store conditions.
	 */
	private function render_switch( array $checks ): void {
		$enabled  = UcpUtils::is_enabled();
		$failing  = $this->store_conditions->failing_required( $checks );
		$blocked  = ! $enabled && array() !== $failing;
		$launched = current( array_filter( $checks, static fn( array $check ): bool => 'launched' === $check['id'] ) );
		?>
		<div class="wc-agentic-commerce-card wc-agentic-commerce-switch-card">
			<div class="wc-agentic-commerce-switch-row">
				<button type="button" class="wc-agentic-commerce-ucp-switch button-link" role="switch" aria-checked="<?php echo $enabled ? 'true' : 'false'; ?>" aria-labelledby="wc-agentic-commerce-ucp-title" aria-describedby="wc-agentic-commerce-ucp-desc" <?php disabled( $blocked ); ?>>
					<span class="woocommerce-input-toggle woocommerce-input-toggle--<?php echo $enabled ? 'enabled' : 'disabled'; ?>"><?php echo $enabled ? esc_html__( 'Yes', 'woocommerce' ) : esc_html__( 'No', 'woocommerce' ); ?></span>
				</button>
				<div>
					<h2 id="wc-agentic-commerce-ucp-title"><?php esc_html_e( 'Allow AI assistants to shop your store', 'woocommerce' ); ?></h2>
					<p id="wc-agentic-commerce-ucp-desc"><?php esc_html_e( 'AI assistants find your products and build a cart. Customers pay on your checkout.', 'woocommerce' ); ?></p>
					<p class="wc-agentic-commerce-ucp-message" role="status"><?php echo ( $enabled && array() === $failing ) ? esc_html__( 'AI assistants can now find and shop your store.', 'woocommerce' ) : ''; ?></p>
				</div>
			</div>
			<?php if ( $launched && ! $launched['ok'] ) : ?>
				<div class="wc-agentic-commerce-notice">
					<span class="dashicons dashicons-warning" aria-hidden="true"></span>
					<p><?php esc_html_e( 'Your store is in coming soon mode, so AI assistants can\'t shop it yet. Take it live to let them in.', 'woocommerce' ); ?></p>
					<a class="button button-primary" href="<?php echo esc_url( $launched['fix_url'] ); ?>"><?php esc_html_e( 'Go to Site visibility', 'woocommerce' ); ?></a>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * The Status card: a badge, then the open setup checks in one list, required ones first and
	 * marked Required. Each says why it matters and links to where to fix it. Completed checks fold
	 * behind "Show N completed steps". UCP itself is the switch, so it is not listed.
	 *
	 * @param array<int, array{id:string,ok:bool,blocks:string,level:string,name:string,label:string,why:string,fix:string,fix_url:string}> $checks           Store conditions.
	 * @param array<string, mixed>|null                                                                                                     $report           Stored report, or null before the first scan.
	 */
	private function render_status( array $checks, ?array $report ): void {
		$done     = array_filter( $checks, static fn( array $check ): bool => 'ucp_on' !== $check['id'] && $check['ok'] );
		$open     = ReadinessCheckPage::open_steps( $checks );
		$required = count( array_filter( $open, static fn( array $check ): bool => StoreConditions::LEVEL_REQUIRED === $check['level'] ) );
		$badge    = $this->readiness_page->shopping_badge( $checks );
		if ( $required > 0 ) {
			/* translators: %d: number of failing required setup steps. */
			$summary = sprintf( _n( '%d required step to fix so AI assistants can shop your store.', '%d required steps to fix so AI assistants can shop your store.', $required, 'woocommerce' ), $required );
		} elseif ( array() !== $open ) {
			/* translators: %d: number of open setup steps. */
			$summary = sprintf( _n( '%d step left.', '%d steps left.', count( $open ), 'woocommerce' ), count( $open ) );
		} else {
			$summary = __( 'Everything AI assistants need is set up.', 'woocommerce' );
		}
		?>
		<div class="wc-agentic-commerce-card wc-agentic-commerce-status-card" id="wc-agentic-commerce-status">
			<div class="wc-agentic-commerce-card__header">
				<div class="wc-agentic-commerce-card__header--split">
					<h2><?php esc_html_e( 'Status', 'woocommerce' ); ?></h2>
					<mark class="wc-agentic-commerce-badge wc-agentic-commerce-badge--<?php echo esc_attr( $badge[0] ); ?>"><?php echo esc_html( $badge[1] ); ?></mark>
				</div>
				<p><?php esc_html_e( 'Checks that your store is ready for AI assistants. Fix anything marked "Fix" so AI assistants can shop your store; items marked "Check" are recommended.', 'woocommerce' ); ?></p>
				<p><strong><?php echo esc_html( $summary ); ?></strong></p>
			</div>
			<?php
			if ( array() !== $open ) {
				echo '<ul class="wc-agentic-commerce-items wc-agentic-commerce-steps">';
				foreach ( $open as $check ) {
					$action   = self::check_action( $check );
					$required = StoreConditions::LEVEL_REQUIRED === $check['level'];
					$title    = '' === $action[2] ? esc_html( $action[0] ) : '<a href="' . esc_url( $action[2] ) . '">' . esc_html( $action[0] ) . '</a>';
					self::render_item( $required ? 'required' : 'open', $title . ' ' . self::step_badge( $check ), array( esc_html( $action[1] ) ) );
				}
				echo '</ul>';
			}
			if ( array() !== $done ) {
				/* translators: %d: number of completed setup steps. */
				self::open_more( sprintf( _n( 'Show %d completed step', 'Show %d completed steps', count( $done ), 'woocommerce' ), count( $done ) ) );
				echo '<ul class="wc-agentic-commerce-items">';
				foreach ( $done as $check ) {
					self::render_item( 'pass', esc_html( $check['name'] ) . ' ' . self::step_badge( $check ), array() );
				}
				echo '</ul></details>';
			}
			$this->render_readiness_link( $report, $checks );
			?>
		</div>
		<?php
	}

	/**
	 * The last row of Status: a link to Sales readiness with how many issues it found.
	 *
	 * @param array<string, mixed>|null                                                                                                     $report Stored report, or null before the first scan.
	 * @param array<int, array{id:string,ok:bool,blocks:string,level:string,name:string,label:string,why:string,fix:string,fix_url:string}> $checks Store conditions.
	 */
	private function render_readiness_link( ?array $report, array $checks ): void {
		$badge = $this->readiness_page->badge( $report, $checks );
		printf(
			'<ul class="wc-agentic-commerce-results"><li><a href="%1$s">%2$s</a> <mark class="wc-agentic-commerce-badge wc-agentic-commerce-badge--%3$s">%4$s</mark></li></ul>',
			esc_url( ReadinessCheckPage::url() ),
			esc_html__( 'Sales readiness', 'woocommerce' ),
			esc_attr( $badge[0] ),
			esc_html( $badge[1] )
		);
	}

	/**
	 * The store address AI assistants start from, with a copy button.
	 */
	private function render_address(): void {
		?>
		<div class="wc-agentic-commerce-card">
			<div class="wc-agentic-commerce-card__header">
				<h2><label for="wc-agentic-commerce-ucp-url"><?php esc_html_e( 'Store address for AI assistants', 'woocommerce' ); ?></label></h2>
			</div>
			<p class="wc-agentic-commerce-card__body wc-agentic-commerce-address">
				<input type="text" id="wc-agentic-commerce-ucp-url" class="regular-text code" readonly value="<?php echo esc_attr( home_url( '/.well-known/ucp' ) ); ?>" />
				<button type="button" class="button wc-agentic-commerce-copy" data-copy-target="wc-agentic-commerce-ucp-url"><?php esc_html_e( 'Copy', 'woocommerce' ); ?></button>
			</p>
		</div>
		<?php
	}
}
