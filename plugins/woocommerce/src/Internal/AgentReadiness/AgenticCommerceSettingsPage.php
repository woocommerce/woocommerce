<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\AgentReadiness;

use Automattic\WooCommerce\Enums\ProductType;
use Automattic\WooCommerce\StoreApi\Utilities\Ucp\Utils as UcpUtils;
use Automattic\WooCommerce\Internal\Traits\ScriptDebug;

defined( 'ABSPATH' ) || exit;

/**
 * WooCommerce > Settings > Agentic commerce. The UCP switch saves on its own
 * through wc/v3/agent-readiness/ucp; the store policy fields save with
 * Save changes. Only created by AgenticCommerceSettings while the
 * agent_readiness feature is enabled, after WC_Settings_Page has loaded.
 *
 * @since 11.4.0
 */
class AgenticCommerceSettingsPage extends \WC_Settings_Page {

	use ScriptDebug;

	private const SCRIPT_HANDLE = 'wc-agentic-commerce-settings';

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
	 * UCP switch.
	 *
	 * @var UcpSwitch
	 */
	private UcpSwitch $ucp_switch;

	/**
	 * Constructor.
	 *
	 * @param StoreConditions $store_conditions Store conditions.
	 * @param AgentReadiness  $agent_readiness  Readiness service.
	 * @param UcpSwitch       $ucp_switch       UCP switch.
	 */
	public function __construct( StoreConditions $store_conditions, AgentReadiness $agent_readiness, UcpSwitch $ucp_switch ) {
		$this->id               = AgenticCommerceSettings::TAB;
		$this->label            = __( 'Agentic commerce', 'woocommerce' );
		$this->store_conditions = $store_conditions;
		$this->agent_readiness  = $agent_readiness;
		$this->ucp_switch       = $ucp_switch;

		parent::__construct();

		add_filter( 'woocommerce_admin_settings_sanitize_option', array( $this, 'sanitize_page_option' ), 10, 3 );
		add_action( 'woocommerce_settings_' . self::POLICIES_SECTION . '_after', array( $this, 'render_policies_footnote' ) );
	}

	/**
	 * The store policy fields. The privacy policy page is a WordPress setting, so it is
	 * listed, and saved, only for users who can manage privacy options.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	protected function get_settings_for_default_section() {
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
				'desc'  => __( 'Choose the pages AI agents will link shoppers to before they buy, so they know your return, shipping and privacy terms up front.', 'woocommerce' ),
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
	 * Render the page as the design's cards: the switch, what agents can see, Status,
	 * the store policy fields, the store address and Security.
	 */
	public function output(): void {
		$report  = $this->agent_readiness->get_cached_report();
		$checks  = $this->store_conditions->all();
		$failing = $this->store_conditions->failing_required( $checks );

		$this->enqueue_script( count( $failing ) );

		echo '<div class="wc-agentic-commerce">';
		echo '<p class="wc-agentic-commerce-intro">' . esc_html__( 'Let AI shopping assistants find your products, build a cart, and send shoppers to your checkout to pay. Uses the Universal Commerce Protocol (UCP).', 'woocommerce' ) . '</p>';
		$this->render_switch( $checks, count( $failing ) );
		$this->render_visibility( $report );
		$this->render_status( $checks, $this->ucp_switch->status( $failing ), count( $failing ) );
		$this->render_readiness( array_slice( $this->agent_readiness->get_store_checks( $report, $checks ), count( $checks ) ), (bool) ( array_column( $checks, 'ok', 'id' )['launched'] ?? true ) );

		echo '<div class="wc-agentic-commerce-card wc-agentic-commerce-policies">';
		parent::output();
		echo '</div>';

		$this->render_address_and_test();
		$this->render_security();
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
		echo '<div class="wc-agentic-commerce-card__header"><h2>' . esc_html__( 'What agents can see', 'woocommerce' ) . '</h2>';
		echo '<p>' . esc_html__( 'Agents see the same products shoppers see in your shop. To keep a product away from agents, set its catalog visibility to Hidden.', 'woocommerce' ) . '</p></div>';

		if ( null === $report ) {
			printf(
				'<p class="wc-agentic-commerce-card__body">%1$s <a href="%2$s">%3$s</a></p></div>',
				esc_html__( 'Product counts appear after the first agent readiness check.', 'woocommerce' ),
				esc_url( admin_url( 'admin.php?page=wc-status&tab=' . StatusTab::TAB ) ),
				esc_html__( 'Run the check', 'woocommerce' )
			);
			return;
		}

		$list_url  = static fn( array $args ): string => add_query_arg(
			array_merge(
				array(
					'post_type'   => 'product',
					'post_status' => 'publish',
				),
				$args
			),
			admin_url( 'edit.php' )
		);
		$supported = ProductType::SIMPLE . ',' . ProductType::VARIABLE;
		// The products list can't filter out hidden products, so the available count has no list to link to.
		$cards = array(
			array(
				'count' => (int) ( $report['products'] ?? 0 ),
				'label' => __( 'Available to agents', 'woocommerce' ),
				'url'   => '',
			),
			array(
				'count' => (int) ( $report['hidden_from_catalog'] ?? 0 ),
				'label' => __( 'Hidden from your catalog', 'woocommerce' ),
				'url'   => $list_url(
					array(
						'product_type'       => $supported,
						'product_visibility' => 'exclude-from-catalog',
					)
				),
			),
			array(
				'count' => (int) ( $report['unsupported_types'] ?? 0 ),
				'label' => __( 'Not supported yet', 'woocommerce' ),
				'url'   => $list_url( array( 'product_type' => implode( ',', $this->unsupported_product_types() ) ) ),
			),
		);

		echo '<ul class="wc-agentic-commerce-counts">';
		foreach ( $cards as $card ) {
			printf(
				'<li><span>%1$s</span> <strong>%2$s</strong>%3$s</li>',
				esc_html( $card['label'] ),
				esc_html( number_format_i18n( $card['count'] ) ),
				'' === $card['url'] ? '' : ' <a href="' . esc_url( $card['url'] ) . '">' . esc_html__( 'View products', 'woocommerce' ) . '</a>'
			);
		}
		echo '</ul>';
		echo '<p class="wc-agentic-commerce-card__footer">' . esc_html__( 'Simple and variable products only, for now.', 'woocommerce' ) . '</p>';
		echo '</div>';
	}

	/**
	 * The UCP switch card. The switch saves right away, apart from Save changes. While the
	 * store is in coming soon mode, the card says so: agents can't shop a store that isn't live.
	 *
	 * @param array<int, array{id:string,ok:bool,fix_url:string}> $checks           Store conditions.
	 * @param int                                                 $failing_required Number of failing required setup checks.
	 */
	private function render_switch( array $checks, int $failing_required ): void {
		$enabled  = UcpUtils::is_enabled();
		$message  = $enabled ? self::on_message( $failing_required ) : '';
		$launched = current( array_filter( $checks, static fn( array $check ): bool => 'launched' === $check['id'] ) );
		?>
		<div class="wc-agentic-commerce-card wc-agentic-commerce-switch-card">
			<div class="wc-agentic-commerce-switch-row">
				<button type="button" class="wc-agentic-commerce-ucp-switch button-link" role="switch" aria-checked="<?php echo $enabled ? 'true' : 'false'; ?>" aria-labelledby="wc-agentic-commerce-ucp-title" aria-describedby="wc-agentic-commerce-ucp-desc" <?php disabled( ! $enabled && $failing_required > 0 ); ?>>
					<span class="woocommerce-input-toggle woocommerce-input-toggle--<?php echo $enabled ? 'enabled' : 'disabled'; ?>"><?php echo $enabled ? esc_html__( 'Yes', 'woocommerce' ) : esc_html__( 'No', 'woocommerce' ); ?></span>
				</button>
				<div>
					<h2 id="wc-agentic-commerce-ucp-title"><?php esc_html_e( 'Let AI agents shop your store', 'woocommerce' ); ?></h2>
					<p id="wc-agentic-commerce-ucp-desc"><?php esc_html_e( 'AI shopping assistants find your products and build a cart. Shoppers pay on your checkout, so you keep the order and the customer. Turn it off at any time to stop all agent access.', 'woocommerce' ); ?></p>
					<p class="wc-agentic-commerce-ucp-message" role="status"><?php echo esc_html( $message ); ?></p>
				</div>
			</div>
			<?php if ( $launched && ! $launched['ok'] ) : ?>
				<div class="wc-agentic-commerce-notice">
					<span class="dashicons dashicons-warning" aria-hidden="true"></span>
					<p><?php esc_html_e( 'Your store is in coming soon mode, so agents can\'t shop it yet. Take it live to let them in.', 'woocommerce' ); ?></p>
					<a class="button button-primary" href="<?php echo esc_url( $launched['fix_url'] ); ?>"><?php esc_html_e( 'Go to Site visibility', 'woocommerce' ); ?></a>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * What the switch says while UCP is on.
	 *
	 * @param int $failing_required Number of failing required setup checks.
	 */
	private static function on_message( int $failing_required ): string {
		if ( $failing_required > 0 ) {
			/* translators: %d: number of failing required setup checks. */
			return sprintf( _n( 'AI agents can shop your store, but %d required setup check below still needs fixing.', 'AI agents can shop your store, but %d required setup checks below still need fixing.', $failing_required, 'woocommerce' ), $failing_required );
		}
		return __( 'AI agents can now find and shop your store.', 'woocommerce' );
	}

	/**
	 * The Status card: a badge, then the setup checks grouped into Required, Recommended and Done.
	 * Each open check says what to do and links to where to do it. UCP itself is the switch, so it is not listed.
	 *
	 * @param array<int, array{id:string,level:string,ok:bool,name:string,label:string,fix_url:string}> $checks           Store conditions.
	 * @param string                                                                                    $status           UcpSwitch status.
	 * @param int                                                                                       $failing_required Number of failing required setup checks.
	 */
	private function render_status( array $checks, string $status, int $failing_required ): void {
		$statuses = self::status_labels();
		$classes  = array(
			UcpSwitch::STATUS_ACTIVE          => 'pass',
			UcpSwitch::STATUS_OFF             => 'off',
			UcpSwitch::STATUS_NEEDS_ATTENTION => 'fix',
		);
		$groups   = array(
			'required'    => array( __( 'Required', 'woocommerce' ), array() ),
			'recommended' => array( __( 'Recommended', 'woocommerce' ), array() ),
			'done'        => array( __( 'Done', 'woocommerce' ), array() ),
		);
		foreach ( $checks as $check ) {
			if ( 'ucp_on' === $check['id'] ) {
				continue;
			}
			$group                 = $check['ok'] ? 'done' : ( StoreConditions::LEVEL_REQUIRED === $check['level'] ? 'required' : 'recommended' );
			$groups[ $group ][1][] = $check;
		}
		$summary = $failing_required > 0
			/* translators: %d: number of failing required setup checks. */
			? sprintf( _n( '%d required step to finish so agents can shop your store.', '%d required steps to finish so agents can shop your store.', $failing_required, 'woocommerce' ), $failing_required )
			: __( 'Everything agents need is set up.', 'woocommerce' );
		?>
		<div class="wc-agentic-commerce-card wc-agentic-commerce-status-card">
			<div class="wc-agentic-commerce-card__header wc-agentic-commerce-card__header--split">
				<div>
					<h2><?php esc_html_e( 'Status', 'woocommerce' ); ?></h2>
					<p><?php echo esc_html( $summary ); ?></p>
				</div>
				<mark class="wc-agentic-commerce-badge wc-agentic-commerce-badge--<?php echo esc_attr( $classes[ $status ] ); ?> wc-agentic-commerce-status"><?php echo esc_html( $statuses[ $status ] ); ?></mark>
			</div>
			<?php foreach ( $groups as $id => $group ) : ?>
				<?php
				if ( array() === $group[1] ) {
					continue;
				}
				$heading = sprintf( '%1$s <span class="wc-agentic-commerce-count">%2$s</span>', esc_html( $group[0] ), esc_html( number_format_i18n( count( $group[1] ) ) ) );
				?>
				<?php if ( 'done' === $id ) : ?>
					<details class="wc-agentic-commerce-group wc-agentic-commerce-group--done">
						<summary class="wc-agentic-commerce-group__title"><?php echo wp_kses_post( $heading ); ?></summary>
						<ul>
							<?php foreach ( $group[1] as $check ) : ?>
								<li class="wc-agentic-commerce-check wc-agentic-commerce-check--done"><span class="dashicons dashicons-yes-alt" aria-hidden="true"></span><span><?php echo esc_html( $check['name'] ); ?></span></li>
							<?php endforeach; ?>
						</ul>
					</details>
				<?php else : ?>
					<div class="wc-agentic-commerce-group wc-agentic-commerce-group--<?php echo esc_attr( $id ); ?>">
						<h3 class="wc-agentic-commerce-group__title"><?php echo wp_kses_post( $heading ); ?></h3>
						<ul>
							<?php foreach ( $group[1] as $check ) : ?>
								<?php $action = $this->check_action( $check ); ?>
								<li class="wc-agentic-commerce-check wc-agentic-commerce-check--<?php echo esc_attr( $id ); ?>">
									<span class="wc-agentic-commerce-check__icon" aria-hidden="true"></span>
									<span class="wc-agentic-commerce-check__text"><?php echo esc_html( $action[0] ); ?></span>
									<?php if ( '' !== $action[2] ) : ?>
										<a class="button" href="<?php echo esc_url( $action[2] ); ?>"><?php echo esc_html( $action[1] ); ?></a>
									<?php endif; ?>
								</li>
							<?php endforeach; ?>
						</ul>
					</div>
				<?php endif; ?>
			<?php endforeach; ?>
		</div>
		<?php
	}

	/**
	 * Agent readiness: the checks the last scan ran on what the store returns. They never
	 * affect the switch, so they sit apart from the setup checks in Status.
	 *
	 * @param array<int, array{id:string,level:string,ok:bool|null,name:string,fix:string,fix_url:string}> $checks   Scanned checks.
	 * @param bool                                                                                         $launched Whether the store is launched.
	 */
	private function render_readiness( array $checks, bool $launched ): void {
		?>
		<div class="wc-agentic-commerce-card wc-agentic-commerce-readiness">
			<div class="wc-agentic-commerce-card__header">
				<h2><?php esc_html_e( 'Agent readiness', 'woocommerce' ); ?></h2>
				<p><?php esc_html_e( 'What agents get when they visit your store, from the last check.', 'woocommerce' ); ?></p>
			</div>
			<?php if ( array() === $checks ) : ?>
				<p class="wc-agentic-commerce-card__body"><?php esc_html_e( 'Results appear after the first agent readiness check.', 'woocommerce' ); ?></p>
			<?php else : ?>
				<ul>
					<?php foreach ( $checks as $check ) : ?>
						<?php
						if ( null === $check['ok'] ) {
							$badge = array( 'check', __( 'Couldn\'t check', 'woocommerce' ) );
						} elseif ( $check['ok'] ) {
							$badge = array( 'pass', __( 'Pass', 'woocommerce' ) );
						} else {
							$badge = array( 'check', __( 'Check', 'woocommerce' ) );
						}
						?>
						<li class="wc-agentic-commerce-check">
							<mark class="wc-agentic-commerce-badge wc-agentic-commerce-badge--<?php echo esc_attr( $badge[0] ); ?>"><?php echo esc_html( $badge[1] ); ?></mark>
							<span class="wc-agentic-commerce-check__text"><?php echo esc_html( $check['name'] ); ?></span>
							<?php if ( null === $check['ok'] ) : ?>
								<span class="description"><?php echo $launched ? esc_html__( 'Your store could not load its own page. The next check tries again.', 'woocommerce' ) : esc_html__( 'Launch your store to run this check.', 'woocommerce' ); ?></span>
							<?php elseif ( ! $check['ok'] && '' === $check['fix_url'] ) : ?>
								<span class="description"><?php echo esc_html( $check['fix'] ); ?></span>
							<?php elseif ( ! $check['ok'] ) : ?>
								<a class="button" href="<?php echo esc_url( $check['fix_url'] ); ?>"><?php echo str_starts_with( $check['fix_url'], admin_url() ) ? esc_html__( 'Open settings', 'woocommerce' ) : esc_html__( 'Learn how', 'woocommerce' ); ?></a>
							<?php endif; ?>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * What an open check asks the merchant to do: the action, the button text and where it goes.
	 * A policy page that is chosen but not published links to its editor; a published store page asks for another page.
	 *
	 * @param array{id:string,label:string,fix_url:string} $check Store condition.
	 * @return array{0:string,1:string,2:string}
	 */
	private function check_action( array $check ): array {
		$policies = array(
			'privacy_page'         => array( AgenticCommerceSettings::PRIVACY_PAGE_OPTION, __( 'Publish your privacy policy page', 'woocommerce' ), __( 'Choose a privacy policy page', 'woocommerce' ) ),
			'terms_page'           => array( AgenticCommerceSettings::TERMS_PAGE_OPTION, __( 'Publish your terms and conditions page', 'woocommerce' ), __( 'Choose a terms and conditions page', 'woocommerce' ) ),
			'refund_returns_page'  => array( AgenticCommerceSettings::REFUND_RETURNS_PAGE_OPTION, __( 'Publish your refund and returns policy page', 'woocommerce' ), __( 'Choose a refund and returns policy page', 'woocommerce' ) ),
			'shipping_policy_page' => array( AgenticCommerceSettings::SHIPPING_POLICY_PAGE_OPTION, __( 'Publish your shipping policy page', 'woocommerce' ), __( 'Choose a shipping policy page', 'woocommerce' ) ),
		);
		if ( isset( $policies[ $check['id'] ] ) ) {
			$page_id = (int) get_option( $policies[ $check['id'] ][0] );
			$edit    = $page_id > 0 && get_post( $page_id ) && 'publish' !== get_post_status( $page_id ) ? (string) get_edit_post_link( $page_id, 'raw' ) : '';
			return '' !== $edit
				? array( $policies[ $check['id'] ][1], __( 'Review and publish', 'woocommerce' ), $edit )
				: array( $policies[ $check['id'] ][2], __( 'Choose a page', 'woocommerce' ), $check['fix_url'] );
		}
		$actions = array(
			'launched'       => array( __( 'Take your store out of coming soon mode', 'woocommerce' ), __( 'Go to Site visibility', 'woocommerce' ) ),
			'https'          => array( __( 'Move your store to HTTPS', 'woocommerce' ), __( 'Learn how', 'woocommerce' ) ),
			'search_visible' => array( __( 'Let search engines index your store', 'woocommerce' ), __( 'Open settings', 'woocommerce' ) ),
			'guest_checkout' => array( __( 'Allow guest checkout', 'woocommerce' ), __( 'Open settings', 'woocommerce' ) ),
			'order_emails'   => array( __( 'Turn on order confirmation emails', 'woocommerce' ), __( 'Open settings', 'woocommerce' ) ),
		);
		$action  = $actions[ $check['id'] ] ?? array( $check['label'], __( 'Open settings', 'woocommerce' ) );
		return array( $action[0], $action[1], $check['fix_url'] );
	}

	/**
	 * The store address agents start from, and Test your setup (WOOAI-1619, not built yet).
	 */
	private function render_address_and_test(): void {
		?>
		<div class="wc-agentic-commerce-card wc-agentic-commerce-card--split">
			<div>
				<h3><label for="wc-agentic-commerce-ucp-url"><?php esc_html_e( 'Store address for agents', 'woocommerce' ); ?></label></h3>
				<input type="text" id="wc-agentic-commerce-ucp-url" class="regular-text code" readonly value="<?php echo esc_attr( home_url( '/.well-known/ucp' ) ); ?>" />
				<button type="button" class="button wc-agentic-commerce-copy" data-copy-target="wc-agentic-commerce-ucp-url"><?php esc_html_e( 'Copy', 'woocommerce' ); ?></button>
			</div>
			<div class="wc-agentic-commerce-placeholder">
				<h3><?php esc_html_e( 'Test your setup', 'woocommerce' ); ?></h3>
				<p><?php esc_html_e( 'Coming in a WooCommerce update.', 'woocommerce' ); ?></p>
			</div>
		</div>
		<?php
	}

	/**
	 * Security (WOOAI-1620, not built yet). Keys are set up automatically, so there is nothing to do here today.
	 */
	private function render_security(): void {
		?>
		<div class="wc-agentic-commerce-card wc-agentic-commerce-placeholder">
			<div class="wc-agentic-commerce-card__header">
				<h2><?php esc_html_e( 'Security', 'woocommerce' ); ?></h2>
				<p><?php esc_html_e( 'Set up automatically. Nothing to do here.', 'woocommerce' ); ?></p>
			</div>
			<p class="wc-agentic-commerce-card__footer"><?php esc_html_e( 'Signing key details are coming in a WooCommerce update.', 'woocommerce' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Status badge text, by UcpSwitch status.
	 *
	 * @return array<string, string>
	 */
	private static function status_labels(): array {
		return array(
			UcpSwitch::STATUS_ACTIVE          => __( 'Active', 'woocommerce' ),
			UcpSwitch::STATUS_OFF             => __( 'Off', 'woocommerce' ),
			UcpSwitch::STATUS_NEEDS_ATTENTION => __( 'Action needed', 'woocommerce' ),
		);
	}

	/**
	 * Product type slugs agents don't support yet, for the product list filter.
	 *
	 * @return array<int, string>
	 */
	private function unsupported_product_types(): array {
		$slugs = get_terms(
			array(
				'taxonomy'   => 'product_type',
				'hide_empty' => false,
				'fields'     => 'slugs',
			)
		);
		if ( ! is_array( $slugs ) ) {
			return array( ProductType::GROUPED, ProductType::EXTERNAL );
		}
		return array_values( array_diff( array_map( 'strval', $slugs ), array( ProductType::SIMPLE, ProductType::VARIABLE ) ) );
	}

	/**
	 * Load the switch and copy button script.
	 *
	 * @param int $failing_required Number of failing required setup checks, for the message shown after turning UCP on.
	 */
	private function enqueue_script( int $failing_required ): void {
		wp_enqueue_script(
			self::SCRIPT_HANDLE,
			plugins_url( 'assets/js/admin/wc-agentic-commerce-settings' . $this->get_script_suffix() . '.js', WC_PLUGIN_FILE ),
			array( 'wp-api-fetch' ),
			WC_VERSION,
			true
		);
		wp_localize_script(
			self::SCRIPT_HANDLE,
			'wcAgenticCommerceSettings',
			array(
				'path' => '/wc/v3/agent-readiness/ucp',
				'i18n' => array(
					'on'          => self::on_message( $failing_required ),
					'error'       => __( 'The setting could not be changed. Reload the page and try again.', 'woocommerce' ),
					'copied'      => __( 'Copied', 'woocommerce' ),
					'statusLabel' => self::status_labels(),
				),
			)
		);
	}
}
