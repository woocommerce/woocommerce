<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\AgentReadiness;

use Automattic\WooCommerce\Internal\Features\FeaturesController;
use Automattic\WooCommerce\Internal\RegisterHooksInterface;

defined( 'ABSPATH' ) || exit;

/**
 * The "Agent readiness" tab under WooCommerce > Status, shown only when the
 * agent_readiness feature is enabled. "Run check" queues a background scan;
 * the tab shows the last stored report.
 *
 * @since 11.4.0
 */
class StatusTab implements RegisterHooksInterface {

	public const FEATURE_ID = 'agent_readiness';

	public const TAB = 'agent-readiness';

	private const NONCE_ACTION = 'wc_agent_readiness_run';

	/**
	 * Features controller.
	 *
	 * @var FeaturesController
	 */
	private FeaturesController $features_controller;

	/**
	 * Readiness service.
	 *
	 * @var AgentReadiness
	 */
	private AgentReadiness $agent_readiness;

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
	 * @param AgentReadiness     $agent_readiness     Readiness service.
	 * @param ScheduledScan      $scheduled_scan      Background scan.
	 */
	final public function init( FeaturesController $features_controller, AgentReadiness $agent_readiness, ScheduledScan $scheduled_scan ): void {
		$this->features_controller = $features_controller;
		$this->agent_readiness     = $agent_readiness;
		$this->scheduled_scan      = $scheduled_scan;
	}

	/**
	 * Register hooks. The feature check waits for init, since feature definitions load translations.
	 */
	public function register(): void {
		add_action( 'init', array( $this, 'handle_init' ) );
	}

	/**
	 * Add the tab when the feature is enabled.
	 *
	 * @internal
	 */
	public function handle_init(): void {
		if ( ! is_admin() || ! $this->features_controller->feature_is_enabled( self::FEATURE_ID ) ) {
			return;
		}
		add_filter( 'woocommerce_admin_status_tabs', array( $this, 'handle_woocommerce_admin_status_tabs' ) );
		add_action( 'woocommerce_admin_status_content_' . self::TAB, array( $this, 'render' ) );
		add_action( 'load-woocommerce_page_wc-status', array( $this, 'handle_run_request' ) );
	}

	/**
	 * Add the tab to the status page.
	 *
	 * @internal
	 *
	 * @param mixed $tabs Tab labels by slug.
	 * @return mixed
	 */
	public function handle_woocommerce_admin_status_tabs( $tabs ) {
		if ( ! is_array( $tabs ) ) {
			return $tabs;
		}
		$tabs[ self::TAB ] = __( 'Agent readiness', 'woocommerce' );
		return $tabs;
	}

	/**
	 * Queue a scan when the "Run check" form is submitted, then redirect back to the tab.
	 *
	 * @internal
	 */
	public function handle_run_request(): void {
		if ( ! isset( $_POST['wc_agent_readiness_run'] ) ) {
			return;
		}
		check_admin_referer( self::NONCE_ACTION );
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to run this check.', 'woocommerce' ), 403 );
		}
		$this->scheduled_scan->queue();
		wp_safe_redirect( $this->tab_url() );
		exit;
	}

	/**
	 * Render the tab.
	 *
	 * @internal
	 */
	public function render(): void {
		$report  = $this->agent_readiness->get_cached_report();
		$pending = $this->scheduled_scan->is_pending();
		$stalled = $pending && $this->scheduled_scan->is_stalled();
		?>
		<h2><?php esc_html_e( 'Agent readiness', 'woocommerce' ); ?></h2>
		<p><?php esc_html_e( 'Checks your products against the product data specs of AI shopping channels, so agents such as ChatGPT, Google and Copilot can list and recommend them.', 'woocommerce' ); ?></p>
		<form method="post" action="<?php echo esc_url( $this->tab_url() ); ?>">
			<?php wp_nonce_field( self::NONCE_ACTION ); ?>
			<p>
				<button type="submit" name="wc_agent_readiness_run" value="1" class="button button-primary" <?php disabled( $pending && ! $stalled ); ?>><?php esc_html_e( 'Run check', 'woocommerce' ); ?></button>
				<?php if ( null !== $report && ! empty( $report['generated_at'] ) ) : ?>
					<span class="description">
						<?php
						/* translators: %s: how long ago the check ran, e.g. "5 mins". */
						echo esc_html( sprintf( __( 'Last run %s ago.', 'woocommerce' ), human_time_diff( (int) strtotime( (string) $report['generated_at'] ) ) ) );
						?>
					</span>
				<?php endif; ?>
			</p>
		</form>
		<?php
		if ( $stalled ) {
			echo '<div class="notice notice-warning inline"><p>' . esc_html__( 'The background check has not started, so scheduled actions may not be running on this site. Run check to check your products now instead.', 'woocommerce' ) . '</p></div>';
		} elseif ( $pending ) {
			echo '<div class="notice notice-info inline"><p>' . esc_html__( 'Checking your products in the background. Reload this page in a minute to see the result.', 'woocommerce' ) . '</p></div>';
		}
		if ( null === $report ) {
			if ( ! $pending ) {
				echo '<p>' . esc_html__( 'No check has run yet.', 'woocommerce' ) . '</p>';
			}
			return;
		}

		$this->render_store_checks( $report['store_checks'] ?? array() );
		if ( ! empty( $report['truncated'] ) ) {
			echo '<div class="notice notice-info inline"><p>';
			/* translators: %d: number of products checked. */
			echo esc_html( sprintf( __( 'Only the %d most recently updated products were checked.', 'woocommerce' ), ProductScan::PRODUCT_LIMIT ) );
			echo '</p></div>';
		}
		$this->render_channels( $report['channels'] ?? array(), (int) ( $report['products'] ?? 0 ) );
		$this->render_not_checked( (int) ( $report['hidden_from_catalog'] ?? 0 ), (int) ( $report['unsupported_types'] ?? 0 ) );
		$this->render_checks( $report['checks'] ?? array(), $report['channels'] ?? array() );
	}

	/**
	 * Store-wide problems, shown first because they affect every product.
	 *
	 * @param array $store_checks Store checks from the report.
	 */
	private function render_store_checks( array $store_checks ): void {
		$failing   = array();
		$unchecked = array();
		foreach ( $store_checks as $check ) {
			if ( array_key_exists( 'ok', $check ) && null === $check['ok'] ) {
				$unchecked[] = (string) $check['name'];
			} elseif ( empty( $check['ok'] ) ) {
				$failing[] = $check;
			}
		}
		if ( ! empty( $unchecked ) ) {
			printf(
				'<div class="notice notice-warning inline"><p><strong>%1$s</strong> %2$s</p></div>',
				esc_html__( 'Your store could not load its own pages, so these were not checked:', 'woocommerce' ),
				esc_html( implode( ', ', $unchecked ) )
			);
		}
		if ( empty( $failing ) ) {
			return;
		}
		echo '<h3>' . esc_html__( 'Store-wide problems', 'woocommerce' ) . '</h3>';
		foreach ( $failing as $check ) {
			printf(
				'<div class="notice notice-error inline"><p><strong>%1$s</strong> %2$s %3$s</p></div>',
				esc_html( (string) $check['label'] ),
				esc_html( (string) $check['why'] ),
				esc_html( (string) $check['fix'] )
			);
		}
	}

	/**
	 * Per-channel product counts.
	 *
	 * @param array $channels Channels from the report.
	 * @param int   $products Products checked.
	 */
	private function render_channels( array $channels, int $products ): void {
		echo '<h3>' . esc_html__( 'Channels', 'woocommerce' ) . '</h3>';
		/* translators: %d: number of products checked. */
		echo '<p>' . esc_html( sprintf( _n( '%d published product checked.', '%d published products checked.', $products, 'woocommerce' ), $products ) ) . '</p>';
		?>
		<table class="widefat striped">
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
						<td><?php echo esc_html( (string) (int) $channel['listable'] ); ?></td>
						<td><?php echo esc_html( (string) (int) $channel['not_listable'] ); ?></td>
						<td><?php echo esc_html( (string) (int) $channel['with_gaps'] ); ?></td>
						<td><?php echo esc_html( (string) (int) $channel['complete'] ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Published products the scan counts but doesn't check.
	 *
	 * @param int $hidden      Products hidden from the shop catalog.
	 * @param int $unsupported Products of a type agents don't support yet.
	 */
	private function render_not_checked( int $hidden, int $unsupported ): void {
		$lines = array();
		if ( $hidden > 0 ) {
			/* translators: %d: number of products. */
			$lines[] = sprintf( __( 'Hidden from your catalog: %d', 'woocommerce' ), $hidden );
		}
		if ( $unsupported > 0 ) {
			/* translators: %d: number of products. */
			$lines[] = sprintf( __( 'Product type not supported yet: %d', 'woocommerce' ), $unsupported );
		}
		if ( empty( $lines ) ) {
			return;
		}
		echo '<p class="description">' . esc_html__( 'Not checked, because agents don\'t see these products:', 'woocommerce' ) . '</p><ul>';
		foreach ( $lines as $line ) {
			echo '<li>' . esc_html( $line ) . '</li>';
		}
		echo '</ul>';
	}

	/**
	 * Each failing check with its channel tags and example products.
	 *
	 * @param array $checks   Checks from the report.
	 * @param array $channels Channels from the report.
	 */
	private function render_checks( array $checks, array $channels ): void {
		$failing = array_filter(
			$checks,
			static function ( $check ) {
				return (int) ( $check['count'] ?? 0 ) > 0;
			}
		);
		echo '<h3>' . esc_html__( 'Product checks', 'woocommerce' ) . '</h3>';
		if ( empty( $failing ) ) {
			echo '<p>' . esc_html__( 'Every product passed every check.', 'woocommerce' ) . '</p>';
			return;
		}
		?>
		<table class="widefat striped">
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
						<td><?php echo esc_html( (string) (int) $check['count'] ); ?></td>
						<td><?php echo wp_kses_post( implode( '<br />', $this->product_links( (array) $check['product_ids'] ) ) ); ?></td>
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
				$tags[] = '<mark class="error">' . esc_html( sprintf( __( 'Required by %s', 'woocommerce' ), $name ) ) . '</mark>';
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
	 * URL of this tab.
	 */
	private function tab_url(): string {
		return admin_url( 'admin.php?page=wc-status&tab=' . self::TAB );
	}
}
