<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\AgentReadiness;

use Automattic\WooCommerce\Enums\ProductType;
use Automattic\WooCommerce\StoreApi\Utilities\UcpUtils;
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
		add_filter( 'woocommerce_admin_settings_sanitize_option_' . AgenticCommerceSettings::RETURN_POLICY_SUMMARY_OPTION, array( $this, 'sanitize_return_policy_summary' ), 10, 3 );
		add_action( 'woocommerce_settings_' . self::POLICIES_SECTION . '_after', array( $this, 'render_policies_footnote' ) );
	}

	/**
	 * The store policy fields.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	protected function get_settings_for_default_section() {
		$pages     = array( '' => __( 'Select a page…', 'woocommerce' ) );
		$published = get_pages(
			array(
				'post_status' => 'publish',
				'exclude'     => self::store_page_ids(),
			)
		);
		foreach ( is_array( $published ) ? $published : array() as $page ) {
			$pages[ (string) $page->ID ] = $page->post_title;
		}
		$page_field = static function ( string $option, string $title, string $desc = '' ) use ( $pages ): array {
			return array(
				'title'              => $title,
				'desc'               => $desc,
				'id'                 => $option,
				'type'               => 'select',
				'options'            => $pages,
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
				'desc'  => __( 'AI agents share these with shoppers before they buy.', 'woocommerce' ),
				'id'    => self::POLICIES_SECTION,
			),
			array(
				'title'       => __( 'Return policy summary', 'woocommerce' ),
				'desc'        => __( 'Optional. Agents quote this when a shopper asks about returns.', 'woocommerce' ),
				'id'          => AgenticCommerceSettings::RETURN_POLICY_SUMMARY_OPTION,
				'type'        => 'textarea',
				'default'     => '',
				'placeholder' => __( '30-day returns on unused items. Free return shipping in the US.', 'woocommerce' ),
				'css'         => 'min-width:300px; height: 80px;',
				'desc_at_end' => true,
				'autoload'    => false,
			),
			$page_field( AgenticCommerceSettings::REFUND_RETURNS_PAGE_OPTION, __( 'Refund and returns', 'woocommerce' ) ),
			$page_field( AgenticCommerceSettings::SHIPPING_POLICY_PAGE_OPTION, __( 'Shipping policy', 'woocommerce' ) ),
			$page_field( AgenticCommerceSettings::PRIVACY_PAGE_OPTION, __( 'Privacy policy', 'woocommerce' ) ),
			$page_field( AgenticCommerceSettings::TERMS_PAGE_OPTION, __( 'Terms and conditions', 'woocommerce' ) ),
			$page_field( AgenticCommerceSettings::FAQ_PAGE_OPTION, __( 'FAQ', 'woocommerce' ), __( 'Optional.', 'woocommerce' ) ),
			array(
				'type' => 'sectionend',
				'id'   => self::POLICIES_SECTION,
			),
		);
	}

	/**
	 * Render the readiness blocks and the switch, then the store policy fields.
	 */
	public function output(): void {
		$this->enqueue_script();

		$report  = $this->agent_readiness->get_cached_report();
		$checks  = $this->store_conditions->all();
		$failing = $this->store_conditions->failing_required( $checks );

		echo '<p>' . esc_html__( 'Let AI shopping assistants find your products, build a cart, and send shoppers to your checkout to pay. Uses the Universal Commerce Protocol (UCP).', 'woocommerce' ) . '</p>';
		$this->render_visibility( $report );
		$this->render_run( $report );
		$this->render_switch( count( $failing ) );
		$this->render_status( $this->agent_readiness->get_store_checks( $report, $checks ), $this->ucp_switch->status( $failing ) );
		if ( null !== $report ) {
			$this->render_product_checks( $report );
		}

		parent::output();
	}

	/**
	 * Keep a page dropdown on this tab from clearing a page it can't show, and
	 * accept only published pages. The dropdown lists published pages only, so
	 * an empty value next to a stored draft page means the merchant left it alone.
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
		if ( in_array( $page_id, self::store_page_ids(), true ) ) {
			return null;
		}
		if ( StoreConditions::is_published_page( $page_id ) ) {
			return (string) $page_id;
		}
		if ( 0 === $page_id && StoreConditions::is_published_page( (int) get_option( (string) $option['id'] ) ) ) {
			return '';
		}
		return null;
	}

	/**
	 * The cart, checkout, shop and My account pages, which can't be a policy page.
	 * Advanced > Page setup leaves them out of its terms and refund fields too.
	 *
	 * @return array<int, int>
	 */
	private static function store_page_ids(): array {
		return array_values(
			array_filter(
				array_map( 'wc_get_page_id', array( 'cart', 'checkout', 'shop', 'myaccount' ) ),
				static fn ( $page_id ) => $page_id > 0
			)
		);
	}

	/**
	 * Plain text, cut to the maximum length.
	 *
	 * @internal
	 *
	 * @param mixed $value     Sanitized value.
	 * @param mixed $option    Field definition.
	 * @param mixed $raw_value Posted value.
	 * @return string
	 */
	public function sanitize_return_policy_summary( $value, $option, $raw_value ): string {
		$text = sanitize_textarea_field( is_string( $raw_value ) ? $raw_value : '' );
		return mb_substr( $text, 0, AgenticCommerceSettings::RETURN_POLICY_SUMMARY_MAX_LENGTH );
	}

	/**
	 * Footnote under the store policy fields.
	 *
	 * @internal
	 */
	public function render_policies_footnote(): void {
		echo '<p class="description">' . esc_html__( 'Refund and returns, privacy policy and terms and conditions are the same pages your store already uses at checkout and in your footer. Changing them here changes them everywhere.', 'woocommerce' ) . '</p>';
	}

	/**
	 * Product counts from the last stored readiness report.
	 *
	 * @param array<string, mixed>|null $report Stored report, or null before the first scan.
	 */
	private function render_visibility( ?array $report ): void {
		echo '<h2>' . esc_html__( 'What agents can see', 'woocommerce' ) . '</h2>';
		echo '<p>' . esc_html__( 'Shows which of your products AI agents can find and sell. Agents see the same products shoppers see in your shop. To keep a product away from agents, set its catalog visibility to Hidden.', 'woocommerce' ) . '</p>';

		if ( null === $report ) {
			echo '<p>' . esc_html__( 'Product counts appear after the first agent readiness check.', 'woocommerce' ) . '</p>';
			return;
		}

		$list_url = static fn( array $args ): string => add_query_arg(
			array_merge(
				array(
					'post_type'   => 'product',
					'post_status' => 'publish',
				),
				$args
			),
			admin_url( 'edit.php' )
		);
		$cards    = array(
			array(
				'count' => (int) ( $report['products'] ?? 0 ),
				'label' => __( 'Available to agents', 'woocommerce' ),
				'url'   => $list_url( array( 'product_type' => ProductType::SIMPLE . ',' . ProductType::VARIABLE ) ),
			),
			array(
				'count' => (int) ( $report['hidden_from_catalog'] ?? 0 ),
				'label' => __( 'Hidden from your catalog', 'woocommerce' ),
				'url'   => $list_url( array( 'product_visibility' => 'exclude-from-catalog' ) ),
			),
			array(
				'count' => (int) ( $report['unsupported_types'] ?? 0 ),
				'label' => __( 'Product type not supported yet', 'woocommerce' ),
				'url'   => $list_url( array( 'product_type' => implode( ',', $this->unsupported_product_types() ) ) ),
			),
		);

		echo '<ul class="wc-agentic-commerce-counts">';
		foreach ( $cards as $card ) {
			printf(
				'<li><strong>%1$s</strong> <span>%2$s</span> <a href="%3$s">%4$s</a></li>',
				esc_html( number_format_i18n( $card['count'] ) ),
				esc_html( $card['label'] ),
				esc_url( $card['url'] ),
				esc_html__( 'View products', 'woocommerce' )
			);
		}
		echo '</ul>';
		echo '<p class="description">' . esc_html__( 'Supported now: simple and variable products. Grouped, external/affiliate, subscription, booking and bundle products aren\'t shown to agents yet.', 'woocommerce' ) . '</p>';
	}

	/**
	 * "Run check" queues the background scan through wc/v3/agent-readiness/scan. While a scan
	 * waits the button is disabled, unless Action Scheduler looks stalled, when the next run
	 * scans in the request.
	 *
	 * @param array<string, mixed>|null $report Stored report, or null before the first scan.
	 */
	private function render_run( ?array $report ): void {
		$pending = $this->scheduled_scan->is_pending();
		$stalled = $pending && $this->scheduled_scan->is_stalled();
		$message = '';
		if ( $stalled ) {
			$message = __( 'The background check has not started, so scheduled actions may not be running on this site. Run check to check your store now instead.', 'woocommerce' );
		} elseif ( $pending ) {
			$message = __( 'Checking your store in the background. Reload this page in a minute to see the result.', 'woocommerce' );
		} elseif ( null !== $report && ! empty( $report['generated_at'] ) ) {
			/* translators: %s: how long ago the check ran, e.g. "5 mins". */
			$message = sprintf( __( 'Last checked %s ago.', 'woocommerce' ), human_time_diff( (int) strtotime( (string) $report['generated_at'] ) ) );
		}
		?>
		<p>
			<button type="button" class="button wc-agentic-commerce-run" <?php disabled( $pending && ! $stalled ); ?>><?php esc_html_e( 'Run check', 'woocommerce' ); ?></button>
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
		$channels = is_array( $report['channels'] ?? null ) ? $report['channels'] : array();
		$failing  = array_filter(
			is_array( $report['checks'] ?? null ) ? $report['checks'] : array(),
			static fn( $check ): bool => is_array( $check ) && (int) ( $check['count'] ?? 0 ) > 0
		);

		echo '<h2>' . esc_html__( 'Product checks', 'woocommerce' ) . '</h2>';
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
				<?php foreach ( $channels as $channel ) : ?>
					<tr>
						<td><a href="<?php echo esc_url( (string) $channel['url'] ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( (string) $channel['name'] ); ?></a><br /><span class="description"><?php echo esc_html( (string) $channel['spec'] ); ?></span></td>
						<td><?php echo esc_html( number_format_i18n( (int) $channel['listable'] ) ); ?></td>
						<td><?php echo esc_html( number_format_i18n( (int) $channel['not_listable'] ) ); ?></td>
						<td><?php echo esc_html( number_format_i18n( (int) $channel['with_gaps'] ) ); ?></td>
						<td><?php echo esc_html( number_format_i18n( (int) $channel['complete'] ) ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php
		if ( empty( $failing ) ) {
			echo '<p>' . esc_html__( 'Every product passed every check.', 'woocommerce' ) . '</p>';
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
						<td><?php echo esc_html( number_format_i18n( (int) $check['count'] ) ); ?></td>
						<td><?php echo wp_kses_post( implode( '<br />', $this->product_links( (array) ( $check['product_ids'] ?? array() ) ) ) ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php
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
		foreach ( array_slice( $product_ids, 0, AgentReadiness::PRODUCT_IDS_LIMIT ) as $product_id ) {
			$url = get_edit_post_link( (int) $product_id, 'raw' );
			if ( ! $url ) {
				continue;
			}
			$links[] = '<a href="' . esc_url( $url ) . '">' . esc_html( get_the_title( (int) $product_id ) ) . '</a>';
		}
		return $links;
	}

	/**
	 * The UCP switch. It saves right away, apart from Save changes.
	 *
	 * @param int $failing_required Number of failing required setup checks.
	 */
	private function render_switch( int $failing_required ): void {
		$enabled = UcpUtils::is_enabled();
		$message = '';
		if ( $enabled && $failing_required > 0 ) {
			/* translators: %d: number of failing required setup checks. */
			$message = sprintf( _n( 'AI agents can shop your store, but %d required setup check below still needs fixing.', 'AI agents can shop your store, but %d required setup checks below still need fixing.', $failing_required, 'woocommerce' ), $failing_required );
		} elseif ( $enabled ) {
			$message = __( 'AI agents can now find and shop your store.', 'woocommerce' );
		} elseif ( $failing_required > 0 ) {
			/* translators: %d: number of failing required setup checks. */
			$message = sprintf( _n( 'Fix %d required setup check below to turn this on.', 'Fix %d required setup checks below to turn this on.', $failing_required, 'woocommerce' ), $failing_required );
		}
		?>
		<h2 id="wc-agentic-commerce-ucp-title"><?php esc_html_e( 'Let AI agents shop your store', 'woocommerce' ); ?></h2>
		<p><?php esc_html_e( 'When this is on, AI shopping assistants can find your products, answer shoppers\' questions about them and build a cart. Shoppers are then sent to your checkout page to pay, so you keep the order and the customer. Turn it off at any time to stop all agent access.', 'woocommerce' ); ?></p>
		<p>
			<button type="button" class="wc-agentic-commerce-ucp-switch button-link" role="switch" aria-checked="<?php echo $enabled ? 'true' : 'false'; ?>" aria-labelledby="wc-agentic-commerce-ucp-title">
				<span class="woocommerce-input-toggle woocommerce-input-toggle--<?php echo $enabled ? 'enabled' : 'disabled'; ?>"><?php echo $enabled ? esc_html__( 'Yes', 'woocommerce' ) : esc_html__( 'No', 'woocommerce' ); ?></span>
			</button>
			<span class="wc-agentic-commerce-ucp-message" role="status"><?php echo esc_html( $message ); ?></span>
		</p>
		<?php
	}

	/**
	 * Status badge, discovery URL and one row per store condition. UCP itself is the switch above, so it is not listed.
	 *
	 * @param array<int, array{id:string,ok:bool|null,blocks:string,level:string,name:string,why:string,fix:string,fix_url:string}> $checks Store conditions.
	 * @param string                                                                                                                $status UcpSwitch status.
	 */
	private function render_status( array $checks, string $status ): void {
		$statuses = array(
			UcpSwitch::STATUS_ACTIVE          => array( 'pass', __( 'Active', 'woocommerce' ) ),
			UcpSwitch::STATUS_OFF             => array( 'off', __( 'Off', 'woocommerce' ) ),
			UcpSwitch::STATUS_NEEDS_ATTENTION => array( 'fix', __( 'Needs attention', 'woocommerce' ) ),
		);
		$checks   = array_filter( $checks, static fn( array $check ): bool => 'ucp_on' !== $check['id'] );
		?>
		<h2><?php esc_html_e( 'Status', 'woocommerce' ); ?></h2>
		<p><?php esc_html_e( 'Checks that your store is ready for AI agents. Fix anything marked "Fix" before you turn agent shopping on; items marked "Check" are recommended.', 'woocommerce' ); ?></p>
		<table class="form-table">
			<tr>
				<th scope="row"><?php esc_html_e( 'Status', 'woocommerce' ); ?></th>
				<td><mark class="wc-agentic-commerce-badge wc-agentic-commerce-badge--<?php echo esc_attr( $statuses[ $status ][0] ); ?> wc-agentic-commerce-status"><?php echo esc_html( $statuses[ $status ][1] ); ?></mark></td>
			</tr>
			<tr>
				<th scope="row"><label for="wc-agentic-commerce-ucp-url"><?php esc_html_e( 'Store address for agents', 'woocommerce' ); ?></label></th>
				<td>
					<input type="text" id="wc-agentic-commerce-ucp-url" class="regular-text" readonly value="<?php echo esc_attr( home_url( '/.well-known/ucp' ) ); ?>" />
					<button type="button" class="button wc-agentic-commerce-copy" data-copy-target="wc-agentic-commerce-ucp-url"><?php esc_html_e( 'Copy', 'woocommerce' ); ?></button>
				</td>
			</tr>
		</table>
		<table class="widefat striped wc-agentic-commerce-checks">
			<tbody>
				<?php foreach ( $checks as $check ) : ?>
					<?php
					if ( null === $check['ok'] ) {
						$badge = array( 'check', __( 'Couldn\'t check', 'woocommerce' ) );
					} elseif ( $check['ok'] ) {
						$badge = array( 'pass', __( 'Pass', 'woocommerce' ) );
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
								<br /><span class="description"><?php echo esc_html( trim( $check['why'] . ' ' . self::blocks_text( $check['blocks'] ) ) ); ?></span>
							<?php endif; ?>
						</td>
						<td>
							<?php if ( null === $check['ok'] ) : ?>
								<?php esc_html_e( 'Your store could not load its own page. The next check tries again.', 'woocommerce' ); ?>
							<?php elseif ( ! $check['ok'] && '' === $check['fix_url'] ) : ?>
								<?php echo esc_html( $check['fix'] ); ?>
							<?php elseif ( ! $check['ok'] ) : ?>
								<a href="<?php echo esc_url( $check['fix_url'] ); ?>"><?php echo esc_html( $this->fix_link_text( $check['fix_url'] ) ); ?></a>
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
	 * @param string $blocks listing, ucp or checkout.
	 */
	private static function blocks_text( string $blocks ): string {
		$texts = array(
			'listing'  => __( 'Affects every product on every channel.', 'woocommerce' ),
			'ucp'      => __( 'Affects agents that use UCP.', 'woocommerce' ),
			'checkout' => __( 'Affects agent checkout.', 'woocommerce' ),
		);
		return $texts[ $blocks ] ?? '';
	}

	/**
	 * Link text for a check's fix URL: a dropdown on this page, a help page, or another settings screen.
	 *
	 * @param string $fix_url Fix URL.
	 */
	private function fix_link_text( string $fix_url ): string {
		if ( str_contains( $fix_url, '#' ) ) {
			return __( 'Choose a page', 'woocommerce' );
		}
		return str_starts_with( $fix_url, admin_url() ) ? __( 'Open settings', 'woocommerce' ) : __( 'Learn how', 'woocommerce' );
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
	 */
	private function enqueue_script(): void {
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
				'path'      => '/wc/v3/agent-readiness/ucp',
				'scanPath'  => '/wc/v3/agent-readiness/scan',
				'maxLength' => AgenticCommerceSettings::RETURN_POLICY_SUMMARY_MAX_LENGTH,
				'i18n'      => array(
					'on'          => __( 'AI agents can now find and shop your store.', 'woocommerce' ),
					'error'       => __( 'The setting could not be changed. Reload the page and try again.', 'woocommerce' ),
					'copied'      => __( 'Copied', 'woocommerce' ),
					'running'     => __( 'Checking your store in the background. Reload this page in a minute to see the result.', 'woocommerce' ),
					/* translators: 1: characters used, 2: character limit. */
					'counter'     => __( '%1$d of %2$d characters', 'woocommerce' ),
					'statusLabel' => array(
						UcpSwitch::STATUS_ACTIVE          => __( 'Active', 'woocommerce' ),
						UcpSwitch::STATUS_OFF             => __( 'Off', 'woocommerce' ),
						UcpSwitch::STATUS_NEEDS_ATTENTION => __( 'Needs attention', 'woocommerce' ),
					),
				),
			)
		);
	}
}
