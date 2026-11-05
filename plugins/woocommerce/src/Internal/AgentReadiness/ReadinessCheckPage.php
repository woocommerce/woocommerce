<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\AgentReadiness;

use Automattic\WooCommerce\Internal\Features\FeaturesController;
use Automattic\WooCommerce\Internal\RegisterHooksInterface;
use Automattic\WooCommerce\StoreApi\Utilities\UcpUtils;

defined( 'ABSPATH' ) || exit;

/**
 * Readiness check, while the agent_readiness feature is enabled: when the store was last checked, Fix first, then a
 * card per merchant question with the checks from the last scan. It is a page of its own, since it saves nothing,
 * with no menu item: it opens from Settings > AI tools, which stays highlighted, and its breadcrumb leads back there.
 *
 * @since 11.4.0
 */
class ReadinessCheckPage implements RegisterHooksInterface {

	use ReadinessMarkup;

	public const PAGE_SLUG = 'wc-agent-readiness';

	/**
	 * Product checks that Readiness check leaves out: product image alt text repeats the images_have_alt store check.
	 */
	private const HIDDEN_PRODUCT_CHECKS = array( 'image_alt_text' );

	/**
	 * How many issues Fix first lists after the required steps.
	 */
	private const FIX_FIRST_ISSUES = 3;

	/**
	 * Recommended setup steps that stop every agent order, so Fix first ranks them above product problems.
	 */
	private const STOPS_EVERY_SALE = array( 'payment_method', 'guest_checkout' );

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
	 * Background scan.
	 *
	 * @var ScheduledScan
	 */
	private ScheduledScan $scheduled_scan;

	/**
	 * The page's screen id, once the menu has added it.
	 *
	 * @var string
	 */
	private string $screen_id = '';

	/**
	 * Initialize dependencies.
	 *
	 * @internal
	 *
	 * @param FeaturesController $features_controller Features controller.
	 * @param StoreConditions    $store_conditions    Store conditions.
	 * @param AgentReadiness     $agent_readiness     Readiness service.
	 * @param ScheduledScan      $scheduled_scan      Background scan.
	 */
	final public function init( FeaturesController $features_controller, StoreConditions $store_conditions, AgentReadiness $agent_readiness, ScheduledScan $scheduled_scan ): void {
		$this->features_controller = $features_controller;
		$this->store_conditions    = $store_conditions;
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
	 * Add the page while the feature is enabled.
	 *
	 * @internal
	 */
	public function handle_init(): void {
		if ( ! is_admin() || ! $this->features_controller->feature_is_enabled( AgenticCommerceSettings::FEATURE_ID ) ) {
			return;
		}
		add_action( 'admin_menu', array( $this, 'handle_admin_menu' ) );
		add_action( 'admin_head', array( $this, 'handle_admin_head' ) );
		add_filter( 'woocommerce_screen_ids', array( $this, 'handle_woocommerce_screen_ids' ) );
	}

	/**
	 * Add the page under WooCommerce. handle_admin_head() removes its menu item, once WordPress has used it to
	 * check access, set the title and open the WooCommerce menu.
	 *
	 * @internal
	 */
	public function handle_admin_menu(): void {
		$title = __( 'Readiness check', 'woocommerce' );
		$hook  = add_submenu_page( 'woocommerce', $title, $title, 'manage_woocommerce', self::PAGE_SLUG, array( $this, 'output' ) );
		if ( is_string( $hook ) ) {
			$this->screen_id = $hook;
			add_filter( 'submenu_file', array( $this, 'handle_submenu_file' ) );
		}
	}

	/**
	 * Remove the menu item before the menu prints: Settings > AI tools opens the page.
	 *
	 * @internal
	 */
	public function handle_admin_head(): void {
		remove_submenu_page( 'woocommerce', self::PAGE_SLUG );
	}

	/**
	 * Highlight WooCommerce > Settings on the page, since it opens from Settings > AI tools.
	 *
	 * @internal
	 *
	 * @param mixed $submenu_file Submenu slug.
	 * @return mixed
	 */
	public function handle_submenu_file( $submenu_file ) {
		return $this->is_current_screen() ? 'wc-settings' : $submenu_file;
	}

	/**
	 * Whether the current admin screen is this page.
	 */
	private function is_current_screen(): bool {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		return '' !== $this->screen_id && null !== $screen && $this->screen_id === $screen->id;
	}

	/**
	 * Count the page as a WooCommerce screen, so the WooCommerce admin styles load on it.
	 *
	 * @internal
	 *
	 * @param mixed $screen_ids Screen ids.
	 * @return mixed
	 */
	public function handle_woocommerce_screen_ids( $screen_ids ) {
		if ( is_array( $screen_ids ) && '' !== $this->screen_id ) {
			$screen_ids[] = $this->screen_id;
		}
		return $screen_ids;
	}

	/**
	 * Link to the page.
	 */
	public static function url(): string {
		return admin_url( 'admin.php?page=' . self::PAGE_SLUG );
	}

	/**
	 * The badge for Readiness check as a whole, as AI shopping's Status card links to it: how many store and
	 * product checks fail, each failing product check counting as one issue.
	 *
	 * @param array<string, mixed>|null                                                                                                     $report Stored report, or null before the first scan.
	 * @param array<int, array{id:string,ok:bool,blocks:string,level:string,name:string,label:string,why:string,fix:string,fix_url:string}> $checks Store conditions.
	 * @return array{0:string,1:string} Badge class and text.
	 */
	public function badge( ?array $report, array $checks ): array {
		if ( null === $report ) {
			return array( 'off', __( 'Not checked yet', 'woocommerce' ) );
		}
		return self::group_badge( array_merge( $this->visible_store_checks( $report, $checks ), array_fill( 0, count( self::failing_product_checks( $report ) ), array( 'ok' => false ) ) ) );
	}

	/**
	 * Render the page: a header with the breadcrumb back to AI tools, when the store was last checked with
	 * Check again, Fix first, then a card per question.
	 *
	 * @internal
	 */
	public function output(): void {
		$report   = $this->agent_readiness->get_cached_report();
		$checks   = $this->store_conditions->all();
		$launched = (bool) ( array_column( $checks, 'ok', 'id' )['launched'] ?? true );
		$visible  = $this->visible_store_checks( $report, $checks );

		$this->enqueue_script();
		echo '<div class="wc-agentic-commerce-page-header"><nav class="wc-agentic-commerce-breadcrumb" aria-label="' . esc_attr__( 'Breadcrumb', 'woocommerce' ) . '"><a href="' . esc_url( AgenticCommerceSettings::url() ) . '">' . esc_html__( 'AI tools', 'woocommerce' ) . '</a> <span aria-hidden="true">/</span> <span aria-current="page">' . esc_html__( 'Readiness check', 'woocommerce' ) . '</span></nav>';
		echo '<p>' . esc_html__( 'See how AI assistants see your store, and what to improve.', 'woocommerce' ) . '</p></div>';
		echo '<div class="wrap woocommerce wc-agentic-commerce"><h1 class="screen-reader-text">' . esc_html__( 'Readiness check', 'woocommerce' ) . '</h1><hr class="wp-header-end">';
		echo '<div class="wc-agentic-commerce-card">';
		if ( null === $report ) {
			echo '<p class="wc-agentic-commerce-card__body">' . esc_html__( 'Run a check to see what AI assistants get when they visit your store and read your products.', 'woocommerce' ) . '</p>';
		}
		echo '<div class="wc-agentic-commerce-card__body wc-agentic-commerce-toolbar"><p>' . esc_html( self::last_checked( $report ) ) . '</p>';
		$this->render_run( $report );
		echo '</div></div>';
		if ( null !== $report ) {
			$this->render_fix_first( $visible, $report );
			$this->render_readiness( $visible, $launched, $report );
		}
		echo '</div>';
	}

	/**
	 * The Readiness check card on the AI tools landing page: a row per question with its badge, rows with
	 * issues opening on what they are, then View all checks. Before the first check it offers Run check.
	 *
	 * @param array<string, mixed>|null                                                                                                     $report Stored report, or null before the first scan.
	 * @param array<int, array{id:string,ok:bool,blocks:string,level:string,name:string,label:string,why:string,fix:string,fix_url:string}> $checks Store conditions.
	 */
	public function render_summary( ?array $report, array $checks ): void {
		echo '<div class="wc-agentic-commerce-card wc-agentic-commerce-summary">';
		echo '<div class="wc-agentic-commerce-card__header"><h2>' . esc_html__( 'Readiness check', 'woocommerce' ) . '</h2>';
		echo '<p>' . esc_html__( 'See how AI assistants see your store, and what to improve.', 'woocommerce' ) . '</p></div>';
		if ( null === $report ) {
			echo '<div class="wc-agentic-commerce-card__body wc-agentic-commerce-toolbar"><p>' . esc_html( self::last_checked( $report ) ) . '</p>';
			$this->render_run( $report );
			echo '</div></div>';
			return;
		}
		$channels = is_array( $report['channels'] ?? null ) ? $report['channels'] : array();
		$rows     = array();
		foreach ( self::question_groups( $this->visible_store_checks( $report, $checks ) ) as $id => $group ) {
			if ( 'buy' === $id ) {
				$rows['products'] = array( self::products_question(), self::product_badge( $report ), array_map( static fn( array $check ): string => self::product_issue( $check, $channels )[0], self::failing_product_checks( $report ) ) );
			}
			$failing     = array_filter( $group[1], static fn( array $check ): bool => false === $check['ok'] );
			$rows[ $id ] = array( $group[0], self::group_badge( $group[1] ), array_map( static fn( array $check ): string => self::check_title( $check ), array_values( $failing ) ) );
		}
		$declared = is_array( $report['declared'] ?? null ) ? $report['declared'] : array();
		if ( array() !== array_filter( $declared, 'is_array' ) ) {
			$rows['declared'] = array( self::declared_question(), array( 'info', self::declared_count( $declared ) ), array() );
		}
		echo '<ul class="wc-agentic-commerce-summary__rows">';
		foreach ( $rows as $id => $row ) {
			$head = '<span class="wc-agentic-commerce-summary__title">' . esc_html( $row[0] ) . '</span> <mark class="wc-agentic-commerce-badge wc-agentic-commerce-badge--' . esc_attr( $row[1][0] ) . '">' . esc_html( $row[1][1] ) . '</mark>';
			if ( array() === $row[2] ) {
				echo '<li><div class="wc-agentic-commerce-summary__row">' . wp_kses_post( $head ) . '</div></li>';
				continue;
			}
			echo '<li><details><summary class="wc-agentic-commerce-summary__row">' . wp_kses_post( $head ) . '</summary><ul class="wc-agentic-commerce-summary__issues">';
			foreach ( $row[2] as $title ) {
				echo '<li><a href="' . esc_url( self::url() . '#wc-agentic-commerce-group-' . $id ) . '">' . esc_html( $title ) . '</a></li>';
			}
			echo '</ul></details></li>';
		}
		echo '</ul><p class="wc-agentic-commerce-card__body wc-agentic-commerce-card__actions"><a class="button" href="' . esc_url( self::url() ) . '">' . esc_html__( 'View all checks', 'woocommerce' ) . '</a></p></div>';
	}

	/**
	 * Whether a store check is a setup step (a setting, from StoreConditions) rather than a scanned
	 * check from the stored report. Setup steps are worded as actions and can be Required; scanned
	 * checks keep the report's wording. Only scanned checks carry a conflict flag: AgentReadiness
	 * sets it on every one of them, so its absence is what marks a setup step.
	 *
	 * @param array<string, mixed> $check Store check.
	 */
	private static function is_setup_step( array $check ): bool {
		return ! array_key_exists( 'conflict', $check );
	}

	/**
	 * A failing store check's title: a setup step's action, or a scanned check's failing title.
	 *
	 * @param array{id:string,name:string,label?:string,why:string,fix_url:string,conflict?:bool} $check Store check.
	 */
	private static function check_title( array $check ): string {
		if ( self::is_setup_step( $check ) ) {
			return self::check_action( $check )[0];
		}
		return '' !== ( $check['label'] ?? '' ) ? (string) $check['label'] : $check['name'];
	}

	/**
	 * When the last check ran: "today at 10:42", or the date for an older one.
	 *
	 * @param array<string, mixed>|null $report Stored report.
	 */
	private static function last_checked( ?array $report ): string {
		$time = null === $report ? false : strtotime( (string) ( $report['generated_at'] ?? '' ) );
		if ( ! $time ) {
			return __( 'Not checked yet', 'woocommerce' );
		}
		if ( wp_date( 'Y-m-d', $time ) === wp_date( 'Y-m-d' ) ) {
			/* translators: %s: time of day, such as 10:42. */
			return sprintf( __( 'Last checked today at %s', 'woocommerce' ), wp_date( (string) get_option( 'time_format' ), $time ) );
		}
		/* translators: %s: date and time, such as November 3, 2026 10:42. */
		return sprintf( __( 'Last checked %s', 'woocommerce' ), wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $time ) );
	}

	/**
	 * The store checks the page shows: the setup steps (UCP itself is the switch, so not a step),
	 * then the scanned checks. With UCP off nothing is served, so the scanned UCP checks are left
	 * out; a check whose prerequisite failed can't say anything, and checks waiting on a later
	 * update (`pending`) aren't shown or counted.
	 *
	 * @param array<string, mixed>|null                                                                                                     $report Stored report, or null before the first scan.
	 * @param array<int, array{id:string,ok:bool,blocks:string,level:string,name:string,label:string,why:string,fix:string,fix_url:string}> $checks Store conditions.
	 * @return array<int, array{id:string,ok:bool|null,blocks:string,group?:string,level:string,name:string,label?:string,why:string,fix:string,fix_url:string,conflict?:bool,pending?:bool,seen_at?:string}>
	 */
	private function visible_store_checks( ?array $report, array $checks ): array {
		$scanned = array_slice( $this->agent_readiness->get_store_checks( $report, $checks ), count( $checks ) );
		$results = array_column( $scanned, 'ok', 'id' );
		$setup   = array_filter( $checks, static fn( array $check ): bool => 'ucp_on' !== $check['id'] );
		return array_merge(
			array_values( $setup ),
			array_filter(
				$scanned,
				static function ( array $check ) use ( $results ): bool {
					if ( ! empty( $check['pending'] ) || ( ! UcpUtils::is_enabled() && 'ucp' === $check['blocks'] ) ) {
						return false;
					}
					$needs = self::CHECK_NEEDS[ $check['id'] ] ?? null;
					return null === $needs || false !== ( $results[ $needs ] ?? null );
				}
			)
		);
	}

	/**
	 * Failing product checks from the report, without the ones this page leaves out.
	 *
	 * @param array<string, mixed>|null $report Stored report.
	 * @return array<int, array<string, mixed>>
	 */
	private static function failing_product_checks( ?array $report ): array {
		return array_values(
			array_filter(
				is_array( $report['checks'] ?? null ) ? $report['checks'] : array(),
				static fn( $check ): bool => is_array( $check ) && (int) ( $check['count'] ?? 0 ) > 0 && ! in_array( $check['id'] ?? '', self::HIDDEN_PRODUCT_CHECKS, true )
			)
		);
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
		}
		?>
		<div class="wc-agentic-commerce-toolbar__action">
			<button type="button" class="button wc-agentic-commerce-run" <?php disabled( $pending ); ?>><?php echo null === $report ? esc_html__( 'Run check', 'woocommerce' ) : esc_html__( 'Check again', 'woocommerce' ); ?></button>
			<p class="description wc-agentic-commerce-run-message" role="status"><?php echo esc_html( $message ); ?></p>
		</div>
		<?php
	}

	/**
	 * The product question of Readiness check: how many products each channel can list, then each
	 * failing product check with what to do and a link to its products.
	 *
	 * @param array<string, mixed> $report Stored report.
	 */
	private function render_product_checks( array $report ): void {
		$failing  = self::failing_product_checks( $report );
		$channels = is_array( $report['channels'] ?? null ) ? $report['channels'] : array();
		$linked   = ! empty( $report['failures_stored'] );

		self::open_question( 'products', self::products_question(), self::product_badge( $report ) );
		if ( ! empty( $report['truncated'] ) ) {
			/* translators: %d: number of products checked. */
			echo '<div class="notice notice-info inline"><p>' . esc_html( sprintf( __( 'Only the %d most recently updated products were checked.', 'woocommerce' ), ProductScan::PRODUCT_LIMIT ) ) . '</p></div>';
		}
		echo '<ul class="wc-agentic-commerce-channels">';
		foreach ( $channels as $channel_id => $channel ) {
			$listed = sprintf(
				/* translators: 1: number of products the channel can list, 2: number of products checked. */
				esc_html__( '%1$s of %2$s can be listed', 'woocommerce' ),
				'<strong>' . esc_html( number_format_i18n( (int) ( $channel['listable'] ?? 0 ) ) ) . '</strong>',
				esc_html( number_format_i18n( (int) ( $report['products'] ?? 0 ) ) )
			);
			printf(
				'<li><span>%1$s</span> <p>%2$s</p></li>',
				esc_html( (string) ( $channel['name'] ?? $channel_id ) ),
				wp_kses_post( $listed )
			);
		}
		echo '</ul>';

		if ( array() === $failing ) {
			echo '<p class="wc-agentic-commerce-card__body">' . esc_html__( 'Every product passed every check.', 'woocommerce' ) . '</p>';
			self::close_question();
			return;
		}
		echo '<ul class="wc-agentic-commerce-items">';
		foreach ( $failing as $check ) {
			$issue = self::product_issue( $check, $channels );
			self::render_item( 'open', esc_html( $issue[0] ), array( esc_html( $issue[1] ), self::products_link( (int) $check['count'], (string) ( $check['id'] ?? '' ), $linked ) ) );
		}
		echo '</ul>';
		self::close_question();
	}

	/**
	 * Fix first: the required steps that fail, then the top FIX_FIRST_ISSUES other issues, ranked by
	 * what they stop (every sale, listing, then findability) and, within that, by the share of sales
	 * on the products they affect, or by how many products when there are no sales to compare.
	 *
	 * @param array<int, array{id:string,ok:bool|null,blocks:string,group?:string,level:string,name:string,label?:string,why:string,fix:string,fix_url:string,conflict?:bool,pending?:bool,seen_at?:string}> $checks Store checks shown on the page.
	 * @param array<string, mixed>                                                                                                                                                                           $report Stored report.
	 * @return array<int, array{tier:int,title:string,why:string,impact:array{0:string,1:string}|null,link:string,share:float|null,count:int}> Escaped title and link HTML.
	 */
	private function fix_first_items( array $checks, array $report ): array {
		$required = array();
		$issues   = array();
		foreach ( $checks as $check ) {
			if ( false !== $check['ok'] ) {
				continue;
			}
			$is_step     = self::is_setup_step( $check );
			$action      = $is_step ? self::check_action( $check ) : array( self::check_title( $check ), $check['why'], $check['fix_url'] );
			$link        = '' === $action[2] ? '' : '<a href="' . esc_url( $action[2] ) . '">' . self::fix_link_text( $action[2] ) . '</a>';
			$is_required = $is_step && StoreConditions::LEVEL_REQUIRED === ( $check['level'] ?? '' );
			// Agents can't buy without these, or can't reach the store's pages at all.
			$every_sale = in_array( $check['id'], self::STOPS_EVERY_SALE, true ) || ( 'listing' === $check['blocks'] && 'access' === ( $check['group'] ?? '' ) );
			// Only store-wide problems join Fix first; the rest have no sales to rank by.
			if ( ! $is_required && ! $every_sale && 'listing' !== $check['blocks'] ) {
				continue;
			}
			$item = array(
				'tier'   => $is_required ? 0 : ( $every_sale ? 1 : 2 ),
				'title'  => esc_html( $action[0] ),
				'why'    => $action[1],
				// The tier heading already says a non-required step stops every sale.
				'impact' => $is_required ? array( 'check', __( 'Required', 'woocommerce' ) ) : ( $every_sale ? null : array( 'info', __( 'All products', 'woocommerce' ) ) ),
				'link'   => $link,
				'share'  => null,
				'count'  => PHP_INT_MAX,
			);
			if ( $is_required ) {
				$required[] = $item;
			} else {
				$issues[] = $item;
			}
		}
		$linked   = ! empty( $report['failures_stored'] );
		$channels = is_array( $report['channels'] ?? null ) ? $report['channels'] : array();
		foreach ( self::failing_product_checks( $report ) as $check ) {
			$blocks_listing = in_array( ReadinessRule::EFFECT_NOT_LISTABLE, array_column( (array) ( $check['channels'] ?? array() ), 'effect' ), true );
			$share          = is_numeric( $check['sales_share'] ?? null ) ? (float) $check['sales_share'] : null;
			$count          = (int) $check['count'];
			$issue          = self::product_issue( $check, $channels );
			$issues[]       = array(
				'tier'   => $blocks_listing ? 2 : 3,
				'title'  => esc_html( $issue[0] ),
				'why'    => $issue[1],
				'impact' => null === $share
					/* translators: %s: number of products. */
					? array( 'info', sprintf( _n( '%s product', '%s products', $count, 'woocommerce' ), number_format_i18n( $count ) ) )
					: array( 'info', self::share_badge( $share ) ),
				'link'   => self::products_link( $count, (string) ( $check['id'] ?? '' ), $linked ),
				'share'  => $share,
				'count'  => $count,
			);
		}
		// Tier first, then the larger share of sales, then more products.
		usort( $issues, static fn( array $a, array $b ): int => array( $a['tier'], $b['share'] ?? 0.0, $b['count'] ) <=> array( $b['tier'], $a['share'] ?? 0.0, $a['count'] ) );
		return array_merge( $required, array_slice( $issues, 0, self::FIX_FIRST_ISSUES ) );
	}

	/**
	 * The Fix first card: numbered, under a heading for what each group of issues stops, with a line on
	 * how it is ranked. A product issue's line starts with its share of sales. Nothing shows when there is
	 * nothing to fix.
	 *
	 * @param array<int, array{id:string,ok:bool|null,blocks:string,group?:string,level:string,name:string,label?:string,why:string,fix:string,fix_url:string,conflict?:bool,pending?:bool,seen_at?:string}> $checks Store checks shown on the page.
	 * @param array<string, mixed>                                                                                                                                                                           $report Stored report.
	 */
	private function render_fix_first( array $checks, array $report ): void {
		$items = $this->fix_first_items( $checks, $report );
		if ( array() === $items ) {
			return;
		}
		$tiers = array(
			0 => __( 'Stops every sale', 'woocommerce' ),
			1 => __( 'Stops every sale', 'woocommerce' ),
			2 => __( 'Products that can\'t be listed', 'woocommerce' ),
			3 => __( 'Products that are harder to find', 'woocommerce' ),
		);
		$days  = (int) ( is_array( $report['sales'] ?? null ) ? ( $report['sales']['days'] ?? 0 ) : 0 );
		echo '<div class="wc-agentic-commerce-card wc-agentic-commerce-fix-first" id="wc-agentic-commerce-fix-first">';
		echo '<div class="wc-agentic-commerce-card__header"><h2>' . esc_html__( 'Fix first', 'woocommerce' ) . '</h2>';
		echo '<p>' . esc_html( self::ranking_text( $report ) ) . '</p></div><ol class="wc-agentic-commerce-fix-first__list">';
		$last  = null;
		$first = true;
		foreach ( $items as $item ) {
			$tier = $tiers[ $item['tier'] ];
			if ( $tier !== $last ) {
				echo '<li class="wc-agentic-commerce-fix-first__tier" aria-hidden="true">' . esc_html( $tier ) . '</li>';
				$last = $tier;
			}
			$why = $item['why'];
			if ( null !== $item['share'] ) {
				// The first share says over which period; the rest only give the share.
				$why   = trim( self::sales_text( $item['share'], $first ? $days : 0 ) . ' ' . $why );
				$first = false;
			}
			echo '<li class="wc-agentic-commerce-fix-first__item"><span class="screen-reader-text">' . esc_html( $tier ) . ': </span>';
			echo '<div class="wc-agentic-commerce-fix-first__head"><p class="wc-agentic-commerce-fix-first__title">' . wp_kses_post( $item['title'] ) . '</p>';
			if ( null !== $item['impact'] ) {
				echo '<mark class="wc-agentic-commerce-badge wc-agentic-commerce-badge--' . esc_attr( $item['impact'][0] ) . '">' . esc_html( $item['impact'][1] ) . '</mark>';
			}
			echo '</div>';
			if ( '' !== $why ) {
				echo '<p class="description">' . esc_html( $why ) . '</p>';
			}
			if ( '' !== $item['link'] ) {
				echo '<p class="description">' . wp_kses_post( $item['link'] ) . '</p>';
			}
			echo '</li>';
		}
		echo '</ol></div>';
	}

	/**
	 * How Fix first is ranked, and what the sales shares compare.
	 *
	 * @param array<string, mixed> $report Stored report.
	 */
	private static function ranking_text( array $report ): string {
		// A report from before sales were read has no `sales` key; the next check fills it in.
		if ( ! array_key_exists( 'sales', $report ) ) {
			return __( 'Ranked by how many products each issue affects.', 'woocommerce' );
		}
		$sales = is_array( $report['sales'] ) ? $report['sales'] : null;
		if ( null === $sales ) {
			return __( 'Ranked by how many products each issue affects. Turn on Analytics to rank by sales instead.', 'woocommerce' );
		}
		$orders = (int) ( $sales['orders'] ?? 0 );
		$days   = (int) ( $sales['days'] ?? 0 );
		if ( 0 === $orders ) {
			/* translators: %d: number of days. */
			return sprintf( _n( 'No sales in the last %d day, so issues are ranked by how many products they affect.', 'No sales in the last %d days, so issues are ranked by how many products they affect.', $days, 'woocommerce' ), $days );
		}
		if ( $orders < ProductSales::MIN_ORDERS ) {
			return sprintf(
				/* translators: 1: number of orders, 2: number of days. */
				_n( 'Only %1$s order in the last %2$d days, too few to rank by sales, so issues are ranked by how many products they affect.', 'Only %1$s orders in the last %2$d days, too few to rank by sales, so issues are ranked by how many products they affect.', $orders, 'woocommerce' ),
				number_format_i18n( $orders ),
				$days
			);
		}
		// Orders with no net sales, or counts changed since the check, leave no share to rank by.
		if ( ! in_array( true, array_map( static fn( array $check ): bool => is_numeric( $check['sales_share'] ?? null ), self::failing_product_checks( $report ) ), true ) ) {
			return __( 'Ranked by how many products each issue affects.', 'woocommerce' );
		}
		return __( 'Ranked by what each fix is worth in sales.', 'woocommerce' );
	}

	/**
	 * "18% of your sales in the last 90 days.", or "18% of your sales." without the period.
	 *
	 * @param float $share Share of sales, from 0 to 1.
	 * @param int   $days  Days the share covers, or 0 to leave the period out.
	 */
	private static function sales_text( float $share, int $days ): string {
		if ( 0.0 === $share ) {
			/* translators: %d: number of days. */
			return $days > 0 ? sprintf( __( 'No sales in the last %d days.', 'woocommerce' ), $days ) : __( 'No sales.', 'woocommerce' );
		}
		if ( $days > 0 ) {
			/* translators: 1: share of sales, such as 18%, 2: number of days. */
			return sprintf( __( '%1$s of your sales in the last %2$d days.', 'woocommerce' ), self::percent( $share ), $days );
		}
		/* translators: %s: share of sales, such as 18%. */
		return sprintf( __( '%s of your sales.', 'woocommerce' ), self::percent( $share ) );
	}

	/**
	 * The badge on a product issue with a share of sales: "18% of sales", or "No sales" when the
	 * affected products sold nothing in the period.
	 *
	 * @param float $share Share from 0 to 1.
	 */
	private static function share_badge( float $share ): string {
		if ( 0.0 === $share ) {
			return __( 'No sales', 'woocommerce' );
		}
		/* translators: %s: share of sales, such as 18%. */
		return sprintf( __( '%s of sales', 'woocommerce' ), self::percent( $share ) );
	}

	/**
	 * A share as a whole percentage; under 1% but above none reads "under 1%".
	 *
	 * @param float $share Share from 0 to 1.
	 */
	private static function percent( float $share ): string {
		if ( $share > 0 && $share < 0.005 ) {
			return __( 'under 1%', 'woocommerce' );
		}
		/* translators: %s: a whole number percentage. */
		return sprintf( __( '%s%%', 'woocommerce' ), number_format_i18n( (int) round( $share * 100 ) ) );
	}

	/**
	 * A failing product check's title, counted: "3 products have no brand". A check this page
	 * has no counted title for keeps the label the scan stored.
	 *
	 * @param array<string, mixed> $check Failing product check from the report.
	 */
	private static function product_check_title( array $check ): string {
		$count = (int) ( $check['count'] ?? 0 );
		$n     = number_format_i18n( $count );
		switch ( $check['id'] ?? '' ) {
			case 'missing_brand':
				/* translators: %s: number of products. */
				return sprintf( _n( '%s product has no brand', '%s products have no brand', $count, 'woocommerce' ), $n );
			case 'missing_gtin':
				/* translators: %s: number of products. */
				return sprintf( _n( '%s product has no GTIN', '%s products have no GTIN', $count, 'woocommerce' ), $n );
			case 'invalid_gtin':
				/* translators: %s: number of products. */
				return sprintf( _n( '%s product has an invalid GTIN', '%s products have an invalid GTIN', $count, 'woocommerce' ), $n );
			case 'duplicate_gtin':
				/* translators: %s: number of products. */
				return sprintf( _n( '%s product shares its GTIN with another product', '%s products share a GTIN with another product', $count, 'woocommerce' ), $n );
			case 'missing_regular_price':
				/* translators: %s: number of products. */
				return sprintf( _n( '%s product has no price', '%s products have no price', $count, 'woocommerce' ), $n );
			case 'missing_images':
				/* translators: %s: number of products. */
				return sprintf( _n( '%s product has no main image', '%s products have no main image', $count, 'woocommerce' ), $n );
			case 'image_too_small':
				/* translators: %s: number of products. */
				return sprintf( _n( '%s product has a main image under 500 x 500 pixels', '%s products have a main image under 500 x 500 pixels', $count, 'woocommerce' ), $n );
			case 'missing_product_text':
				/* translators: %s: number of products. */
				return sprintf( _n( '%s product has no description', '%s products have no description', $count, 'woocommerce' ), $n );
			case 'uncategorised':
				/* translators: %s: number of products. */
				return sprintf( _n( '%s product has only the default category', '%s products have only the default category', $count, 'woocommerce' ), $n );
			case 'variation_attributes_not_set':
				/* translators: %s: number of products. */
				return sprintf( _n( '%s product has variations set to "Any"', '%s products have variations set to "Any"', $count, 'woocommerce' ), $n );
			case 'missing_variation_images':
				/* translators: %s: number of products. */
				return sprintf( _n( '%s product has variations with no image', '%s products have variations with no image', $count, 'woocommerce' ), $n );
			case 'title_too_long':
				/* translators: %s: number of products. */
				return sprintf( _n( '%s product has a title over 150 characters', '%s products have titles over 150 characters', $count, 'woocommerce' ), $n );
		}
		return (string) ( $check['label'] ?? '' );
	}

	/**
	 * "View N products", linked to the Products list filtered to them when each product's failures are stored.
	 *
	 * @param int    $count  Number of products.
	 * @param string $value  Stored failure value.
	 * @param bool   $linked Whether the list can be filtered.
	 * @return string Escaped HTML.
	 */
	private static function products_link( int $count, string $value, bool $linked ): string {
		if ( ! $linked ) {
			/* translators: %s: number of products. */
			return esc_html( sprintf( _n( '%s product', '%s products', $count, 'woocommerce' ), number_format_i18n( $count ) ) );
		}
		/* translators: %s: number of products. */
		return '<a href="' . esc_url( ProductsList::url( $value ) ) . '">' . esc_html( sprintf( _n( 'View %s product', 'View %s products', $count, 'woocommerce' ), number_format_i18n( $count ) ) ) . '</a>';
	}

	/**
	 * A failing product check's title and what it means. When some channels can't list the products and
	 * the rest list them with a warning, the title names the channels that can't: "ChatGPT can't list 13
	 * products without a brand", then "Google and Microsoft Copilot still list them, with a warning.".
	 * Otherwise the counted title, then why it matters and what to do.
	 *
	 * @param array<string, mixed> $check    Failing product check from the report.
	 * @param array<string, mixed> $channels Channels from the report.
	 * @return array{0:string,1:string} Title and line.
	 */
	private static function product_issue( array $check, array $channels ): array {
		$count = (int) ( $check['count'] ?? 0 );
		$n     = number_format_i18n( $count );
		$cant  = array();
		$warn  = array();
		foreach ( (array) ( $check['channels'] ?? array() ) as $channel_id => $spec ) {
			$name = (string) ( $channels[ $channel_id ]['name'] ?? $channel_id );
			if ( ReadinessRule::EFFECT_NOT_LISTABLE === ( $spec['effect'] ?? '' ) ) {
				$cant[] = $name;
			} else {
				$warn[] = $name;
			}
		}
		if ( array() !== $cant && array() !== $warn ) {
			$names = wp_sprintf( '%l', $cant );
			$title = null;
			switch ( $check['id'] ?? '' ) {
				case 'missing_brand':
					/* translators: 1: channel names, such as ChatGPT, 2: number of products. */
					$title = sprintf( _n( '%1$s can\'t list %2$s product without a brand', '%1$s can\'t list %2$s products without a brand', $count, 'woocommerce' ), $names, $n );
					break;
				case 'invalid_gtin':
					/* translators: 1: channel names, such as Google, 2: number of products. */
					$title = sprintf( _n( '%1$s can\'t list %2$s product with an invalid GTIN', '%1$s can\'t list %2$s products with an invalid GTIN', $count, 'woocommerce' ), $names, $n );
					break;
			}
			if ( null !== $title ) {
				$line = 1 === count( $warn )
					/* translators: %s: channel name, such as Google. */
					? _n( '%s still lists it, with a warning.', '%s still lists them, with a warning.', $count, 'woocommerce' )
					/* translators: %s: channel names, such as Google and Microsoft Copilot. */
					: _n( '%s still list it, with a warning.', '%s still list them, with a warning.', $count, 'woocommerce' );
				return array( $title, sprintf( $line, wp_sprintf( '%l', $warn ) ) );
			}
		}
		return array( self::product_check_title( $check ), trim( (string) ( $check['why'] ?? '' ) . ' ' . (string) ( $check['fix'] ?? '' ) ) );
	}

	/**
	 * Protocols and files the store serves to agents, as information, never pass or fail: how many were
	 * found, then every item behind a fold. With no items in the report there is no card.
	 *
	 * @param array $items Items from the report.
	 */
	private function render_declared( array $items ): void {
		$items = array_filter( $items, 'is_array' );
		if ( array() === $items ) {
			return;
		}
		self::open_question( 'declared', self::declared_question(), null );
		/* translators: %s: how many items were found, such as "4 of 33 found". */
		echo '<p class="wc-agentic-commerce-card__body">' . esc_html( sprintf( __( 'Optional files some AI tools look for. Nothing to fix. %s.', 'woocommerce' ), self::declared_count( $items ) ) ) . '</p>';
		/* translators: %d: number of items. */
		self::open_more( sprintf( _n( 'Show the %d item', 'Show the %d items', count( $items ), 'woocommerce' ), count( $items ) ) );
		echo '<ul class="wc-agentic-commerce-items">';
		foreach ( $items as $item ) {
			self::render_item( true === ( $item['found'] ?? null ) ? 'pass' : 'off', esc_html( (string) ( $item['name'] ?? '' ) ), array() );
		}
		echo '</ul></details>';
		self::close_question();
	}

	/**
	 * The declared items question's title, on its card and its landing page row.
	 */
	private static function declared_question(): string {
		return __( 'What does your store share with AI assistants?', 'woocommerce' );
	}

	/**
	 * "4 of 33 found": how many declared items the store serves.
	 *
	 * @param array $items Items from the report.
	 */
	private static function declared_count( array $items ): string {
		/* translators: 1: number of items found, 2: number of items looked for. */
		return sprintf( __( '%1$s of %2$s found', 'woocommerce' ), number_format_i18n( count( self::found_declared( $items ) ) ), number_format_i18n( count( array_filter( $items, 'is_array' ) ) ) );
	}

	/**
	 * The badge on a question: how many issues, or that everything passed. A check that
	 * couldn't run isn't an issue.
	 *
	 * @param array<int, array{ok:bool|null}> $checks The question's checks.
	 * @return array{0:string,1:string} Badge class and text.
	 */
	private static function group_badge( array $checks ): array {
		$issues = count( array_filter( $checks, static fn( array $check ): bool => false === $check['ok'] ) );
		if ( $issues > 0 ) {
			/* translators: %d: number of issues. */
			return array( 'check', sprintf( _n( '%d issue', '%d issues', $issues, 'woocommerce' ), $issues ) );
		}
		if ( array() === $checks ) {
			return array( 'off', __( 'Not checked yet', 'woocommerce' ) );
		}
		$passed = array() === array_filter( $checks, static fn( array $check ): bool => true !== $check['ok'] );
		return $passed ? array( 'info', __( 'All passed', 'woocommerce' ) ) : array( 'info', __( 'No issues', 'woocommerce' ) );
	}

	/**
	 * Store checks as rows. A failing check is named by its failing title, then what it affects, why it
	 * matters, what to do and a link; a required setup step is marked Required. One that couldn't run
	 * says why; a passed one keeps its passing title.
	 *
	 * @param array<int, array{id:string,ok:bool|null,blocks:string,level:string,name:string,label?:string,why:string,fix:string,fix_url:string,conflict?:bool,seen_at?:string}> $checks   Checks.
	 * @param bool|null                                                                                                                                                          $launched Whether the store is launched.
	 */
	private function render_check_rows( array $checks, ?bool $launched ): void {
		if ( array() === $checks ) {
			return;
		}
		echo '<ul class="wc-agentic-commerce-items">';
		foreach ( $checks as $check ) {
			if ( null === $check['ok'] ) {
				self::render_item( 'unknown', esc_html( $check['name'] ), array( $launched ? esc_html__( 'This couldn\'t be checked. The next check tries again.', 'woocommerce' ) : esc_html__( 'Launch your store to run this check.', 'woocommerce' ) ) );
				continue;
			}
			if ( $check['ok'] ) {
				// A check's why describes the failure, so a passed one shows only its name, as in Status.
				self::render_item( 'pass', esc_html( $check['name'] ), array() );
				continue;
			}
			$lines = array( esc_html( self::affects_text( $check ) ) );
			if ( self::is_setup_step( $check ) ) {
				// A setup step, from StoreConditions: worded as in Status.
				$action   = self::check_action( $check );
				$required = StoreConditions::LEVEL_REQUIRED === $check['level'];
				$lines[]  = esc_html( $action[1] );
				if ( '' !== $action[2] ) {
					$lines[] = '<a href="' . esc_url( $action[2] ) . '">' . self::fix_link_text( $action[2] ) . '</a>';
				}
				self::render_item( $required ? 'required' : 'open', esc_html( $action[0] ) . ( $required ? ' <mark class="wc-agentic-commerce-badge wc-agentic-commerce-badge--check">' . esc_html__( 'Required', 'woocommerce' ) . '</mark>' : '' ), $lines );
				continue;
			}
			$lines[] = esc_html( $check['why'] );
			if ( ! empty( $check['conflict'] ) && ! empty( $check['seen_at'] ) ) {
				$lines[] = esc_html__( 'Seen on:', 'woocommerce' ) . ' <a href="' . esc_url( $check['seen_at'] ) . '">' . esc_html( $check['seen_at'] ) . '</a>';
			}
			$lines[] = esc_html( $check['fix'] );
			if ( '' !== $check['fix_url'] ) {
				$lines[] = '<a href="' . esc_url( $check['fix_url'] ) . '">' . self::fix_link_text( $check['fix_url'] ) . '</a>';
			}
			self::render_item( 'open', esc_html( self::check_title( $check ) ), $lines );
		}
		echo '</ul>';
	}

	/**
	 * Open a question's card: its title and badge. The caller closes it with close_question().
	 *
	 * @param string                        $id    Group id, for the card's anchor.
	 * @param string                        $title The question.
	 * @param array{0:string,1:string}|null $badge Badge class and text, or null for none.
	 */
	private static function open_question( string $id, string $title, ?array $badge ): void {
		printf(
			'<div class="wc-agentic-commerce-card wc-agentic-commerce-question" id="wc-agentic-commerce-group-%1$s"><div class="wc-agentic-commerce-card__header wc-agentic-commerce-card__header--split"><h2>%2$s</h2>%3$s</div>',
			esc_attr( $id ),
			esc_html( $title ),
			null === $badge ? '' : ' <mark class="wc-agentic-commerce-badge wc-agentic-commerce-badge--' . esc_attr( $badge[0] ) . '">' . esc_html( $badge[1] ) . '</mark>'
		);
	}

	/**
	 * The declared protocols and files the store serves.
	 *
	 * @param array $items Items from the report.
	 * @return array<int|string, array<string, mixed>>
	 */
	private static function found_declared( array $items ): array {
		return array_filter( $items, static fn( $item ): bool => is_array( $item ) && true === ( $item['found'] ?? null ) );
	}

	/**
	 * Close a question opened with open_question().
	 */
	private static function close_question(): void {
		echo '</div>';
	}

	/**
	 * The text of a failing check's fix link: where it goes.
	 *
	 * @param string $url Fix URL.
	 * @return string Escaped text.
	 */
	private static function fix_link_text( string $url ): string {
		if ( str_starts_with( $url, admin_url( 'upload.php' ) ) ) {
			return esc_html__( 'Go to Media library', 'woocommerce' );
		}
		if ( str_starts_with( $url, admin_url( 'post.php' ) ) ) {
			return esc_html__( 'Edit page', 'woocommerce' );
		}
		if ( str_starts_with( $url, admin_url( 'admin.php?page=wc-settings&tab=site-visibility' ) ) ) {
			return esc_html__( 'Go to Site visibility', 'woocommerce' );
		}
		return str_starts_with( $url, admin_url() ) ? esc_html__( 'Open settings', 'woocommerce' ) : esc_html__( 'Learn how', 'woocommerce' );
	}

	/**
	 * "Affects: …": what a failing store check changes for AI assistants, by check where the design names
	 * it, otherwise from what the check stops.
	 *
	 * @param array{id:string,blocks:string} $check Store check.
	 */
	private static function affects_text( array $check ): string {
		$summarize = __( 'how AI assistants summarize your store', 'woocommerce' );
		$details   = __( 'the product details AI assistants read', 'woocommerce' );
		$by_check  = array(
			'llms_txt_present'               => $summarize,
			'llms_txt_valid'                 => $summarize,
			'llms_txt_links_resolve'         => $summarize,
			'meta_description'               => __( 'how AI answers describe your pages', 'woocommerce' ),
			'images_have_alt'                => __( 'how AI assistants understand your images', 'woocommerce' ),
			'product_jsonld_present'         => $details,
			'product_jsonld_from_core'       => $details,
			'product_jsonld_required_fields' => $details,
			'product_offer_jsonld'           => $details,
			'schema_matches_product'         => $details,
		);
		$by_blocks = array(
			'listing'   => __( 'every product on every channel', 'woocommerce' ),
			'discovery' => __( 'how AI assistants find and read your store', 'woocommerce' ),
			'ucp'       => __( 'AI assistants that use UCP', 'woocommerce' ),
			'checkout'  => __( 'checkout through AI assistants', 'woocommerce' ),
		);
		$affects   = $by_check[ $check['id'] ] ?? $by_blocks[ $check['blocks'] ] ?? '';
		/* translators: %s: what a check affects, such as "how AI assistants summarize your store". */
		return '' === $affects ? '' : sprintf( __( 'Affects: %s', 'woocommerce' ), $affects );
	}

	/**
	 * The questions under Fix first: a card per merchant question with the checks the last scan ran on
	 * what the store returns, one for the product checks before the buying question, then what the store
	 * shares. Inside a card, problems show first and passed checks are folded.
	 *
	 * @param array<int, array{id:string,ok:bool|null,blocks:string,group?:string,level:string,name:string,label?:string,why:string,fix:string,fix_url:string,conflict?:bool,pending?:bool,seen_at?:string}> $checks   Scanned checks to show.
	 * @param bool                                                                                                                                                                                           $launched Whether the store is launched.
	 * @param array<string, mixed>                                                                                                                                                                           $report   Stored report.
	 */
	private function render_readiness( array $checks, bool $launched, array $report ): void {
		foreach ( self::question_groups( $checks ) as $id => $group ) {
			if ( 'buy' === $id ) {
				$this->render_product_checks( $report );
			}
			$folded = array_filter( $group[1], static fn( array $check ): bool => true === $check['ok'] );
			self::open_question( $id, $group[0], self::group_badge( $group[1] ) );
			if ( 'access' === $id && ! UcpUtils::is_enabled() ) {
				echo '<p class="wc-agentic-commerce-card__body">' . esc_html__( 'Agent shopping is off, so the UCP checks don\'t run.', 'woocommerce' ) . '</p>';
			}
			$this->render_check_rows( array_diff_key( $group[1], $folded ), $launched );
			if ( array() !== $folded ) {
				/* translators: %d: number of checks. */
				self::open_more( sprintf( _n( 'Show %d more check', 'Show %d more checks', count( $folded ), 'woocommerce' ), count( $folded ) ) );
				$this->render_check_rows( $folded, $launched );
				echo '</details>';
			}
			self::close_question();
		}
		$this->render_declared( is_array( $report['declared'] ?? null ) ? $report['declared'] : array() );
	}

	/**
	 * The store checks by the merchant question they answer, with each question's title.
	 *
	 * @param array<int, array{id:string,ok:bool|null,blocks:string,group?:string,level:string,name:string,label?:string,why:string,fix:string,fix_url:string,conflict?:bool,pending?:bool,seen_at?:string}> $checks Scanned checks to show.
	 * @return array<string, array{0:string,1:array<int, array{id:string,ok:bool|null,blocks:string,group?:string,level:string,name:string,label?:string,why:string,fix:string,fix_url:string,conflict?:bool,pending?:bool,seen_at?:string}>}>
	 */
	private static function question_groups( array $checks ): array {
		$groups = array(
			'access'     => array( __( 'Can AI assistants visit your store?', 'woocommerce' ), array() ),
			'find'       => array( __( 'Can AI assistants find your store?', 'woocommerce' ), array() ),
			'understand' => array( __( 'Can AI assistants read your product details?', 'woocommerce' ), array() ),
			'buy'        => array( __( 'Can shoppers buy through AI assistants?', 'woocommerce' ), array() ),
		);
		foreach ( $checks as $check ) {
			// A report from before checks named their question has no group; the next scan fills it in.
			if ( isset( $groups[ $check['group'] ?? '' ] ) ) {
				$groups[ $check['group'] ][1][] = $check;
			}
		}
		return $groups;
	}

	/**
	 * The product question's title, on its card and its landing page row.
	 */
	private static function products_question(): string {
		return __( 'Can AI shopping apps list your products?', 'woocommerce' );
	}

	/**
	 * The badge on the product question: how many product checks fail, or All passed.
	 *
	 * @param array<string, mixed> $report Stored report.
	 * @return array{0:string,1:string} Badge class and text.
	 */
	private static function product_badge( array $report ): array {
		$failing = self::failing_product_checks( $report );
		return array() === $failing ? array( 'info', __( 'All passed', 'woocommerce' ) ) : self::group_badge( array_fill( 0, count( $failing ), array( 'ok' => false ) ) );
	}
}
