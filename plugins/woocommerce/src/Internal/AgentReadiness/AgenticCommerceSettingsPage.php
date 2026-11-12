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
	 * Example products per product check, to keep the page short. The count column has the total.
	 */
	private const EXAMPLES_SHOWN = 3;

	/**
	 * Marks the page dropdowns on this tab, which share options with other tabs.
	 */
	private const PAGE_FIELD_KEY = 'agentic_commerce_page';

	/**
	 * Checks that can only run when the check they name passed, so aren't shown when it failed.
	 */
	private const CHECK_NEEDS = array(
		'product_jsonld_from_core'       => 'product_jsonld_present',
		'product_jsonld_required_fields' => 'product_jsonld_present',
		'product_offer_jsonld'           => 'product_jsonld_present',
		'llms_txt_valid'                 => 'llms_txt_present',
		'llms_txt_links_resolve'         => 'llms_txt_present',
	);

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
	 * Constructor.
	 *
	 * @param StoreConditions $store_conditions Store conditions.
	 * @param AgentReadiness  $agent_readiness  Readiness service.
	 * @param UcpSwitch       $ucp_switch       UCP switch.
	 * @param ScheduledScan   $scheduled_scan   Background scan.
	 */
	public function __construct( StoreConditions $store_conditions, AgentReadiness $agent_readiness, UcpSwitch $ucp_switch, ScheduledScan $scheduled_scan ) {
		$this->id               = AgenticCommerceSettings::TAB;
		$this->label            = __( 'Agentic commerce', 'woocommerce' );
		$this->store_conditions = $store_conditions;
		$this->agent_readiness  = $agent_readiness;
		$this->ucp_switch       = $ucp_switch;
		$this->scheduled_scan   = $scheduled_scan;

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
		$this->render_readiness( array_slice( $this->agent_readiness->get_store_checks( $report, $checks ), count( $checks ) ), (bool) ( array_column( $checks, 'ok', 'id' )['launched'] ?? true ), $report );

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
			echo '<p class="wc-agentic-commerce-card__body">' . esc_html__( 'Product counts appear after the first agent readiness check.', 'woocommerce' ) . '</p></div>';
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
	 * "Run check" queues the background scan through wc/v3/agent-readiness/scan. While a scan
	 * waits the button is disabled. When Action Scheduler looks stalled, the page says so;
	 * the scan never runs in the request (WOOAI-1661).
	 *
	 * @param array<string, mixed>|null $report Stored report, or null before the first scan.
	 */
	private function render_run( ?array $report ): void {
		$pending = $this->scheduled_scan->is_pending();
		$stalled = $pending && $this->scheduled_scan->is_stalled();
		$message = '';
		if ( $stalled ) {
			$message = __( 'The check has not started because scheduled actions do not seem to be running on this site. It runs once they do. See WooCommerce > Status > Scheduled Actions.', 'woocommerce' );
		} elseif ( $pending ) {
			$message = __( 'Checking your store in the background. Reload this page in a minute to see the result.', 'woocommerce' );
		} elseif ( null !== $report && ! empty( $report['generated_at'] ) ) {
			/* translators: %s: how long ago the check ran, e.g. "5 mins". */
			$message = sprintf( __( 'Last checked %s ago.', 'woocommerce' ), human_time_diff( (int) strtotime( (string) $report['generated_at'] ) ) );
		}
		?>
		<p>
			<button type="button" class="button wc-agentic-commerce-run" <?php disabled( $pending ); ?>><?php esc_html_e( 'Run check', 'woocommerce' ); ?></button>
			<span class="description wc-agentic-commerce-run-message" role="status"><?php echo esc_html( $message ); ?></span>
		</p>
		<?php
	}

	/**
	 * Per-channel product counts and each failing product check with example products.
	 *
	 * @param array<string, mixed> $report Stored report.
	 */
	private function render_product_checks( array $report ): void {
		$channels = array_filter( is_array( $report['channels'] ?? null ) ? $report['channels'] : array(), 'is_array' );
		$channels = array_map(
			static fn( array $channel ): array => $channel + array_fill_keys( array( 'url', 'name', 'spec', 'listable', 'not_listable', 'with_gaps', 'complete' ), '' ),
			$channels
		);
		$failing  = array_filter(
			is_array( $report['checks'] ?? null ) ? $report['checks'] : array(),
			static fn( $check ): bool => is_array( $check ) && (int) ( $check['count'] ?? 0 ) > 0
		);

		echo '<div class="wc-agentic-commerce-card__section"><h3>' . esc_html__( 'Product checks', 'woocommerce' ) . '</h3>';
		echo '<p>' . esc_html__( 'Checks your products against the product data specs of AI shopping channels, so agents such as ChatGPT, Google and Copilot can list and recommend them.', 'woocommerce' ) . '</p>';
		if ( ! empty( $report['truncated'] ) ) {
			/* translators: %d: number of products checked. */
			echo '<div class="notice notice-info inline"><p>' . esc_html( sprintf( __( 'Only the %d most recently updated products were checked.', 'woocommerce' ), ProductScan::PRODUCT_LIMIT ) ) . '</p></div>';
		}
		?>
		<table class="widefat striped wc-agentic-commerce-channels">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Channel', 'woocommerce' ); ?></th>
					<th><?php esc_html_e( 'Can be listed', 'woocommerce' ); ?></th>
					<th><?php esc_html_e( 'Missing a required field', 'woocommerce' ); ?></th>
					<th><?php esc_html_e( 'Listed with gaps', 'woocommerce' ); ?></th>
					<th><?php esc_html_e( 'Complete', 'woocommerce' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $channels as $channel_id => $channel ) : ?>
					<tr>
						<td><a href="<?php echo esc_url( (string) $channel['url'] ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( (string) $channel['name'] ); ?></a><br /><span class="description"><?php echo esc_html( (string) $channel['spec'] ); ?></span></td>
						<td><?php echo esc_html( number_format_i18n( (int) $channel['listable'] ) ); ?></td>
						<td><?php echo wp_kses_post( self::count_link( (int) $channel['not_listable'], ProductFailures::channel_value( ProductScan::NOT_LISTABLE, (string) $channel_id ), ! empty( $report['failures_stored'] ) ) ); ?></td>
						<td><?php echo esc_html( number_format_i18n( (int) $channel['with_gaps'] ) ); ?></td>
						<td><?php echo esc_html( number_format_i18n( (int) $channel['complete'] ) ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php
		if ( empty( $failing ) ) {
			echo '<p>' . esc_html__( 'Every product passed every check.', 'woocommerce' ) . '</p></div>';
			return;
		}
		?>
		<table class="widefat striped wc-agentic-commerce-product-checks">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Problem', 'woocommerce' ); ?></th>
					<th><?php esc_html_e( 'Channels', 'woocommerce' ); ?></th>
					<th><?php esc_html_e( 'Products', 'woocommerce' ); ?></th>
					<th><?php esc_html_e( 'Examples', 'woocommerce' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $failing as $check ) : ?>
					<tr>
						<td>
							<strong><?php echo esc_html( (string) $check['label'] ); ?></strong>
							<p><?php echo esc_html( (string) $check['why'] ); ?></p>
							<p><a href="<?php echo esc_url( (string) $check['fix_url'] ); ?>"><?php echo esc_html( (string) $check['fix'] ); ?></a></p>
						</td>
						<td><?php echo empty( $check['channels'] ) ? esc_html__( 'Product page only', 'woocommerce' ) : wp_kses_post( implode( '<br />', $this->channel_tags( (array) $check['channels'], $channels ) ) ); ?></td>
						<td><?php echo wp_kses_post( self::count_link( (int) $check['count'], (string) ( $check['id'] ?? '' ), ! empty( $report['failures_stored'] ) ) ); ?></td>
						<td><?php echo wp_kses_post( implode( '<br />', $this->product_links( (array) ( $check['product_ids'] ?? array() ) ) ) ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		</div>
		<?php
	}

	/**
	 * A count, linked to the Products list filtered to those products when each product's failures are stored.
	 *
	 * @param int    $count  Number of products.
	 * @param string $value  Stored failure value.
	 * @param bool   $linked Whether the list can be filtered.
	 * @return string Escaped HTML.
	 */
	private static function count_link( int $count, string $value, bool $linked ): string {
		$text = esc_html( number_format_i18n( $count ) );
		return $linked && $count > 0 ? '<a href="' . esc_url( ProductsList::url( $value ) ) . '">' . $text . '</a>' : $text;
	}

	/**
	 * "Required by X" or "Harder to find on X" per channel.
	 *
	 * @param array $check_channels Field and effect by channel id.
	 * @param array $channels       Channels from the report.
	 * @return array<int, string> Escaped HTML.
	 */
	private function channel_tags( array $check_channels, array $channels ): array {
		$tags = array();
		foreach ( $check_channels as $channel_id => $spec ) {
			$name = (string) ( $channels[ $channel_id ]['name'] ?? $channel_id );
			if ( ReadinessRule::EFFECT_NOT_LISTABLE === ( $spec['effect'] ?? '' ) ) {
				/* translators: %s: channel name, e.g. ChatGPT. */
				$tags[] = '<mark class="wc-agentic-commerce-badge wc-agentic-commerce-badge--fix">' . esc_html( sprintf( __( 'Required by %s', 'woocommerce' ), $name ) ) . '</mark>';
			} else {
				/* translators: %s: channel name, e.g. ChatGPT. */
				$tags[] = esc_html( sprintf( __( 'Harder to find on %s', 'woocommerce' ), $name ) );
			}
		}
		return $tags;
	}

	/**
	 * Edit links for the example products that still exist.
	 *
	 * @param array $product_ids Product ids.
	 * @return array<int, string> Escaped HTML.
	 */
	private function product_links( array $product_ids ): array {
		$links = array();
		foreach ( $product_ids as $product_id ) {
			$url = get_edit_post_link( (int) $product_id, 'raw' );
			if ( ! $url ) {
				continue;
			}
			$links[] = '<a href="' . esc_url( $url ) . '">' . esc_html( get_the_title( (int) $product_id ) ) . '</a>';
			if ( self::EXAMPLES_SHOWN === count( $links ) ) {
				break;
			}
		}
		return $links;
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
	 * Protocols and files the store declares to agents, as information: found or not found, never pass or fail.
	 *
	 * @param array $items Items from the report.
	 */
	private function render_declared( array $items ): void {
		$items = array_filter( $items, 'is_array' );
		if ( array() === $items ) {
			return;
		}
		$found   = count( array_filter( $items, static fn( array $item ): bool => true === ( $item['found'] ?? null ) ) );
		$unknown = count( array_filter( $items, static fn( array $item ): bool => null === ( $item['found'] ?? null ) ) );
		if ( count( $items ) === $unknown ) {
			$summary = __( 'Not checked', 'woocommerce' );
		} elseif ( $unknown > 0 ) {
			/* translators: 1: number found, 2: number looked for, 3: number that could not be checked. */
			$summary = sprintf( __( '%1$d of %2$d found, %3$d not checked', 'woocommerce' ), $found, count( $items ), $unknown );
		} else {
			/* translators: 1: number found, 2: number looked for. */
			$summary = sprintf( __( '%1$d of %2$d found', 'woocommerce' ), $found, count( $items ) );
		}
		echo '<div class="wc-agentic-commerce-card__section wc-agentic-commerce-declared-section"><h3>' . esc_html__( 'What this store declares to agents', 'woocommerce' ) . '</h3>';
		echo '<p>' . esc_html__( 'For information. Most stores don\'t need these, and none of them counts as a problem.', 'woocommerce' ) . '</p>';
		echo '<details><summary>' . esc_html( $summary ) . '</summary><table class="widefat striped wc-agentic-commerce-declared"><tbody>';
		foreach ( $items as $item ) {
			$state = null !== ( $item['found'] ?? null ) ? ( $item['found'] ? __( 'Found', 'woocommerce' ) : __( 'Not found', 'woocommerce' ) ) : __( 'Not checked', 'woocommerce' );
			echo '<tr><td>' . esc_html( (string) ( $item['name'] ?? '' ) ) . '</td><td>' . esc_html( $state ) . '</td></tr>';
		}
		echo '</tbody></table></details></div>';
	}

	/**
	 * One line on where a question stands.
	 *
	 * @param string                                                     $group            Question id.
	 * @param array<int, array{ok:bool|null,level:string,pending?:bool}> $checks           The question's checks. Pending ones don't count as unchecked.
	 * @param bool                                                       $scanned          Whether a scan has run.
	 * @param int                                                        $product_problems Failing product checks under the question.
	 * @param bool                                                       $ucp_scanned      Whether the last scan checked UCP.
	 * @param bool                                                       $ucp_enabled      Whether UCP is on.
	 */
	private static function group_summary( string $group, array $checks, bool $scanned, int $product_problems, bool $ucp_scanned, bool $ucp_enabled ): string {
		$fix     = count( array_filter( $checks, static fn( array $check ): bool => false === $check['ok'] && StoreConditions::LEVEL_REQUIRED === $check['level'] ) );
		$check   = $product_problems + count( array_filter( $checks, static fn( array $check ): bool => false === $check['ok'] && StoreConditions::LEVEL_REQUIRED !== $check['level'] ) );
		$unknown = count( array_filter( $checks, static fn( array $check ): bool => null === $check['ok'] && empty( $check['pending'] ) ) );
		$parts   = array();
		if ( $fix > 0 ) {
			/* translators: %d: number of checks. */
			$parts[] = sprintf( _n( '%d to fix', '%d to fix', $fix, 'woocommerce' ), $fix );
		}
		if ( $check > 0 ) {
			/* translators: %d: number of checks. */
			$parts[] = sprintf( _n( '%d to check', '%d to check', $check, 'woocommerce' ), $check );
		}
		if ( $unknown > 0 ) {
			/* translators: %d: number of checks. */
			$parts[] = sprintf( _n( '%d couldn\'t be checked', '%d couldn\'t be checked', $unknown, 'woocommerce' ), $unknown );
		}
		if ( 'access' === $group && ! $ucp_enabled ) {
			array_unshift( $parts, __( 'Agent shopping is off', 'woocommerce' ) );
		}
		if ( array() !== $parts ) {
			return implode( ', ', $parts );
		}
		$ran = array_filter( $checks, static fn( array $check ): bool => empty( $check['pending'] ) );
		if ( array() === $ran && array() !== $checks ) {
			return __( 'Coming soon', 'woocommerce' );
		}
		// Rows from before UCP was turned on, or a report without store checks, don't answer the question.
		$answered = $scanned && array() !== $ran && ( 'access' !== $group || $ucp_scanned );
		return $answered ? __( 'Yes', 'woocommerce' ) : __( 'Run check to find out', 'woocommerce' );
	}

	/**
	 * A table of store checks.
	 *
	 * @param array<int, array{id:string,ok:bool|null,blocks:string,level:string,name:string,why:string,fix:string,fix_url:string,conflict?:bool,pending?:bool,seen_at?:string}> $checks   Checks.
	 * @param bool|null                                                                                                                                                          $launched Whether the store is launched.
	 */
	private function render_check_rows( array $checks, ?bool $launched ): void {
		if ( array() === $checks ) {
			return;
		}
		?>
		<table class="widefat striped wc-agentic-commerce-checks">
			<tbody>
				<?php foreach ( $checks as $check ) : ?>
					<?php
					$conflict = ! empty( $check['conflict'] );
					if ( ! empty( $check['pending'] ) ) {
						$badge = array( 'off', __( 'Coming soon', 'woocommerce' ) );
					} elseif ( null === $check['ok'] ) {
						$badge = array( 'check', __( 'Couldn\'t check', 'woocommerce' ) );
					} elseif ( $check['ok'] ) {
						$badge = array( 'pass', __( 'Pass', 'woocommerce' ) );
					} elseif ( $conflict ) {
						$badge = array( 'check', __( 'Likely conflict', 'woocommerce' ) );
					} elseif ( StoreConditions::LEVEL_REQUIRED === $check['level'] ) {
						$badge = array( 'fix', __( 'Fix', 'woocommerce' ) );
					} else {
						$badge = array( 'check', __( 'Check', 'woocommerce' ) );
					}
					?>
					<tr>
						<td><mark class="wc-agentic-commerce-badge wc-agentic-commerce-badge--<?php echo esc_attr( $badge[0] ); ?>"><?php echo esc_html( $badge[1] ); ?></mark></td>
						<td>
							<?php echo esc_html( $check['name'] ); ?>
							<?php if ( false === $check['ok'] ) : ?>
								<br /><span class="description"><?php echo esc_html( trim( ( $conflict ? __( 'Something on your store may be replacing this. If you just changed it, a page cache may show the old page for a while.', 'woocommerce' ) . ' ' : '' ) . $check['why'] . ' ' . self::blocks_text( $check['blocks'] ) ) ); ?></span>
								<?php if ( $conflict && ! empty( $check['seen_at'] ) ) : ?>
									<br /><span class="description"><?php esc_html_e( 'Seen on:', 'woocommerce' ); ?> <a href="<?php echo esc_url( $check['seen_at'] ); ?>"><?php echo esc_html( $check['seen_at'] ); ?></a></span>
								<?php endif; ?>
							<?php endif; ?>
						</td>
						<td>
							<?php if ( ! empty( $check['pending'] ) ) : ?>
								<?php esc_html_e( 'Coming in a WooCommerce update.', 'woocommerce' ); ?>
							<?php elseif ( null === $check['ok'] ) : ?>
								<?php
								if ( $launched ) {
									esc_html_e( 'This couldn\'t be checked. The next check tries again.', 'woocommerce' );
								} else {
									esc_html_e( 'Launch your store to run this check.', 'woocommerce' );
								}
								?>
							<?php elseif ( ! $check['ok'] && '' === $check['fix_url'] ) : ?>
								<?php echo esc_html( $check['fix'] ); ?>
							<?php elseif ( ! $check['ok'] ) : ?>
								<a href="<?php echo esc_url( $check['fix_url'] ); ?>"><?php echo str_starts_with( $check['fix_url'], admin_url() ) ? esc_html__( 'Open settings', 'woocommerce' ) : esc_html__( 'Learn how', 'woocommerce' ); ?></a>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php
	}

	/**
	 * What a failing store check stops, from its `blocks` value.
	 *
	 * @param string $blocks listing, discovery, ucp or checkout.
	 */
	private static function blocks_text( string $blocks ): string {
		$texts = array(
			'listing'   => __( 'Affects every product on every channel.', 'woocommerce' ),
			'discovery' => __( 'Makes your store harder for agents to find and read.', 'woocommerce' ),
			'ucp'       => __( 'Affects agents that use UCP.', 'woocommerce' ),
			'checkout'  => __( 'Affects agent checkout.', 'woocommerce' ),
		);
		return $texts[ $blocks ] ?? '';
	}

	/**
	 * Agent readiness: the checks the last scan ran on what the store returns, under the
	 * merchant question each answers, with a one-line answer per question. They never
	 * affect the switch, so they sit apart from the setup checks in Status. Run check is
	 * at the top, and the product checks sit under "Do they understand your products?".
	 *
	 * @param array<int, array{id:string,ok:bool|null,blocks:string,group?:string,level:string,name:string,why:string,fix:string,fix_url:string,conflict?:bool,pending?:bool,seen_at?:string}> $checks   Scanned checks.
	 * @param bool                                                                                                                                                                             $launched Whether the store is launched.
	 * @param array<string, mixed>|null                                                                                                                                                        $report   Stored report, or null before the first scan.
	 */
	private function render_readiness( array $checks, bool $launched, ?array $report ): void {
		$results = array_column( $checks, 'ok', 'id' );
		$checks  = array_filter(
			$checks,
			static function ( array $check ) use ( $results ): bool {
				// With UCP off nothing is served; the switch already says so.
				if ( ! UcpUtils::is_enabled() && 'ucp' === $check['blocks'] ) {
					return false;
				}
				$needs = self::CHECK_NEEDS[ $check['id'] ] ?? null;
				return null === $needs || false !== ( $results[ $needs ] ?? null );
			}
		);
		$groups  = array(
			'access'     => array( __( 'Can agents get in?', 'woocommerce' ), array() ),
			'find'       => array( __( 'Can they find your store?', 'woocommerce' ), array() ),
			'understand' => array( __( 'Do they understand your products?', 'woocommerce' ), array() ),
			'buy'        => array( __( 'Can they buy?', 'woocommerce' ), array() ),
		);
		foreach ( $checks as $check ) {
			// A report from before checks named their question has no group; the next scan fills it in.
			if ( isset( $groups[ $check['group'] ?? '' ] ) ) {
				$groups[ $check['group'] ][1][] = $check;
			}
		}
		$product_problems = 0;
		foreach ( is_array( $report['checks'] ?? null ) ? $report['checks'] : array() as $product_check ) {
			$product_problems += is_array( $product_check ) && (int) ( $product_check['count'] ?? 0 ) > 0 ? 1 : 0;
		}
		?>
		<div class="wc-agentic-commerce-card wc-agentic-commerce-readiness">
			<div class="wc-agentic-commerce-card__header">
				<h2><?php esc_html_e( 'Agent readiness', 'woocommerce' ); ?></h2>
				<p><?php esc_html_e( 'What agents get when they visit your store, from the last check.', 'woocommerce' ); ?></p>
				<?php $this->render_run( $report ); ?>
			</div>
			<?php if ( null === $report ) : ?>
				<p class="wc-agentic-commerce-card__body"><?php esc_html_e( 'Results appear after the first agent readiness check.', 'woocommerce' ); ?></p>
			<?php else : ?>
				<ul class="wc-agentic-commerce-summary">
					<?php foreach ( $groups as $id => $group ) : ?>
						<li>
							<a href="#wc-agentic-commerce-group-<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $group[0] ); ?></a>
							<?php
							$summary = static fn( bool $ucp_enabled ): string => self::group_summary( $id, $group[1], true, 'understand' === $id ? $product_problems : 0, array_key_exists( 'ucp_profile', $results ), $ucp_enabled );
							if ( 'access' === $id ) {
								// The switch script swaps these, so the line follows the switch without a reload.
								printf( '<span class="wc-agentic-commerce-access-summary" data-on="%1$s" data-off="%2$s">%3$s</span>', esc_attr( $summary( true ) ), esc_attr( $summary( false ) ), esc_html( $summary( UcpUtils::is_enabled() ) ) );
							} else {
								echo esc_html( $summary( UcpUtils::is_enabled() ) );
							}
							?>
						</li>
					<?php endforeach; ?>
				</ul>
				<?php foreach ( $groups as $id => $group ) : ?>
					<div class="wc-agentic-commerce-card__section" id="wc-agentic-commerce-group-<?php echo esc_attr( $id ); ?>">
						<h3><?php echo esc_html( $group[0] ); ?></h3>
						<?php
						$open   = array_filter( $group[1], static fn( array $check ): bool => true !== $check['ok'] );
						$passed = array_filter( $group[1], static fn( array $check ): bool => true === $check['ok'] );
						$this->render_check_rows( $open, $launched );
						if ( array() !== $passed ) {
							/* translators: %d: number of checks that passed. */
							echo '<details><summary>' . esc_html( sprintf( _n( '%d check passed', '%d checks passed', count( $passed ), 'woocommerce' ), count( $passed ) ) ) . '</summary>';
							$this->render_check_rows( $passed, $launched );
							echo '</details>';
						}
						?>
					</div>
					<?php
					if ( 'understand' === $id ) {
						$this->render_product_checks( $report );
					}
					?>
				<?php endforeach; ?>
				<?php $this->render_declared( is_array( $report['declared'] ?? null ) ? $report['declared'] : array() ); ?>
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
			'payment_method' => array( __( 'Turn on a payment method', 'woocommerce' ), __( 'Open settings', 'woocommerce' ) ),
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
	 * Load the script for the switch, Run check and the copy button.
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
				'path'     => '/wc/v3/agent-readiness/ucp',
				'scanPath' => '/wc/v3/agent-readiness/scan',
				'i18n'     => array(
					'on'          => self::on_message( $failing_required ),
					'error'       => __( 'The setting could not be changed. Reload the page and try again.', 'woocommerce' ),
					'copied'      => __( 'Copied', 'woocommerce' ),
					'running'     => __( 'Checking your store in the background. Reload this page in a minute to see the result.', 'woocommerce' ),
					'statusLabel' => self::status_labels(),
				),
			)
		);
	}
}
