<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\AgentReadiness;

use Automattic\WooCommerce\Internal\Features\FeaturesController;
use Automattic\WooCommerce\Internal\RegisterHooksInterface;
use Automattic\WooCommerce\StoreApi\Utilities\Ucp\Utils as UcpUtils;

defined( 'ABSPATH' ) || exit;

/**
 * Sales readiness, while the agent_readiness feature is enabled: when the store was last checked, Fix first,
 * then a card per merchant question with the checks from the last scan. It is a WooCommerce Admin page
 * under the WooCommerce menu, before Settings; the React page reads everything it shows from
 * wc/v3/agent-readiness/view, which this class builds. The same builder gives the AI tools tab its
 * Overview: the AI shopping card and the Sales readiness card with Fix first.
 *
 * @since 11.4.0
 */
class ReadinessCheckPage implements RegisterHooksInterface {

	use ReadinessMarkup;

	public const PAGE_ID = 'woocommerce-sales-readiness';

	public const PATH = '/sales-readiness';

	/**
	 * The admin_menu priority: between WooCommerce's Reports (20) and Settings (50).
	 */
	public const MENU_PRIORITY = 30;

	/**
	 * Product checks that Sales readiness leaves out: product image alt text repeats the images_have_alt store check.
	 */
	private const HIDDEN_PRODUCT_CHECKS = array( 'image_alt_text' );

	/**
	 * How many issues Fix first lists after the required steps.
	 */
	private const FIX_FIRST_ISSUES = 3;

	/**
	 * Recommended setup steps that stop every agent order, so Fix first ranks them above product problems.
	 * A missing payment method stops every sale too; it is shown as Required (StoreConditions::SHOWN_REQUIRED).
	 */
	private const STOPS_EVERY_SALE = array( 'guest_checkout' );

	/**
	 * Scanned checks that are Required although they don't keep AI assistants off the store's pages:
	 * with AI shopping on, AI assistants can't connect, search or check out without them. These checks
	 * are only shown while AI shopping is on.
	 */
	private const REQUIRED_SCANNED = array( 'ucp_profile', 'ucp_endpoints_live', 'ucp_checkout' );

	/**
	 * Checks that can only run when the check they name passed, so aren't shown when it failed.
	 */
	private const CHECK_NEEDS = array(
		'product_jsonld_from_core'       => 'product_jsonld_present',
		'product_jsonld_required_fields' => 'product_jsonld_present',
		'product_offer_jsonld'           => 'product_jsonld_present',
		'product_return_policy_schema'   => 'product_jsonld_present',
		'llms_txt_valid'                 => 'llms_txt_present',
		'llms_txt_links_resolve'         => 'llms_txt_present',
	);

	/**
	 * Scanned checks left out while a setup step with the same cause fails, so it shows once: WordPress
	 * prints noindex while "Discourage search engines" is on, and WooCommerce adds no returns policy
	 * to product pages until a refund and returns page is chosen.
	 */
	private const SAME_CAUSE = array(
		'no_noindex_directives'        => 'search_visible',
		'product_return_policy_schema' => 'refund_returns_page',
	);

	/**
	 * Badge intents of the design system, by the badge class the PHP pages use.
	 */
	private const INTENTS = array(
		'check' => 'low',
		'info'  => 'stable',
		'pass'  => 'stable',
		'off'   => 'none',
		'fix'   => 'low',
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
		// After Reports (20) and before Settings (50), as WooCommerce adds them.
		add_action( 'admin_menu', array( $this, 'handle_admin_menu' ), self::MENU_PRIORITY );
	}

	/**
	 * Register the WooCommerce Admin page under WooCommerce.
	 *
	 * @internal
	 */
	public function handle_admin_menu(): void {
		if ( ! function_exists( 'wc_admin_register_page' ) ) {
			return;
		}
		wc_admin_register_page(
			array(
				'id'         => self::PAGE_ID,
				'parent'     => 'woocommerce',
				'title'      => __( 'Sales readiness', 'woocommerce' ),
				'path'       => self::PATH,
				'capability' => 'manage_woocommerce',
			)
		);
	}

	/**
	 * The page's slug in the WooCommerce submenu.
	 */
	private static function menu_slug(): string {
		return 'wc-admin&path=' . self::PATH;
	}

	/**
	 * Link to the page.
	 */
	public static function url(): string {
		return admin_url( 'admin.php?page=' . self::menu_slug() );
	}

	/**
	 * The badge for Sales readiness as a whole, as AI shopping's Status card links to it: how many store and
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
		// Only what the page shows: setup steps that don't join Fix first live in the Setup checklist.
		$shown = array_filter( $this->visible_store_checks( $report, $checks ), static fn( array $check ): bool => ! self::is_setup_step( $check ) || self::joins_fix_first( $check ) );
		return self::group_badge( array_merge( $shown, array_fill( 0, count( self::failing_product_checks( $report ) ), array( 'ok' => false ) ) ) );
	}

	/**
	 * The AI shopping badge: Needs attention while a required step fails, even with UCP off,
	 * otherwise Active or Off as UCP is.
	 *
	 * @param array<int, array{id:string,ok:bool,level:string}> $checks Store conditions.
	 * @return array{0:string,1:string} Badge class and text.
	 */
	public function shopping_badge( array $checks ): array {
		if ( array() !== $this->store_conditions->failing_required( $checks ) ) {
			return array( 'check', __( 'Needs attention', 'woocommerce' ) );
		}
		return UcpUtils::is_enabled() ? array( 'pass', __( 'Active', 'woocommerce' ) ) : array( 'off', __( 'Off', 'woocommerce' ) );
	}

	/**
	 * The setup steps still open, required ones first. UCP itself is the switch, so it is not a step.
	 *
	 * @param array<int, array{id:string,ok:bool,blocks:string,level:string,name:string,label:string,why:string,fix:string,fix_url:string}> $checks Store conditions.
	 * @return array<int, array{id:string,ok:bool,blocks:string,level:string,name:string,label:string,why:string,fix:string,fix_url:string}>
	 */
	public static function open_steps( array $checks ): array {
		$open     = self::setup_order( array_filter( $checks, static fn( array $check ): bool => 'ucp_on' !== $check['id'] && ! $check['ok'] ) );
		$required = array( StoreConditions::class, 'shows_required' );
		return array_merge( array_values( array_filter( $open, $required ) ), array_values( array_filter( $open, static fn( array $check ): bool => ! $required( $check ) ) ) );
	}

	/**
	 * Setup steps in the order the pages list them: HTTPS first, then as StoreConditions lists them,
	 * which is also the order the report and the ability keep.
	 *
	 * @template T of array{id:string}
	 * @param array<int|string, T> $checks Store conditions.
	 * @return array<int, T>
	 */
	private static function setup_order( array $checks ): array {
		return array_merge(
			array_values( array_filter( $checks, static fn( array $check ): bool => 'https' === $check['id'] ) ),
			array_values( array_filter( $checks, static fn( array $check ): bool => 'https' !== $check['id'] ) )
		);
	}

	/**
	 * Everything the Sales readiness page and the AI tools Overview show, as data for the React views:
	 * when the store was last checked and the check button, Fix first, a section per question, the
	 * declared items, and the Overview's AI shopping card.
	 *
	 * @return array<string, mixed>
	 */
	public function view(): array {
		$report   = $this->agent_readiness->get_cached_report();
		$checks   = $this->store_conditions->all();
		$launched = (bool) ( array_column( $checks, 'ok', 'id' )['launched'] ?? true );
		$visible  = $this->visible_store_checks( $report, $checks );
		$pending  = $this->scheduled_scan->is_pending();
		$stalled  = $pending && $this->scheduled_scan->is_stalled();
		$message  = '';
		if ( $stalled ) {
			$message = __( 'The check can’t start because scheduled actions aren’t running on this site.', 'woocommerce' );
		} elseif ( $pending ) {
			$message = __( 'Checking your store.', 'woocommerce' );
		}
		$truncated = null;
		if ( null !== $report && ! empty( $report['truncated'] ) ) {
			$available = (int) ( $report['available'] ?? 0 );
			$truncated = $available > ProductScan::PRODUCT_LIMIT
				/* translators: 1: number of products checked, 2: number of products in the store. */
				? sprintf( __( 'Checked the %1$s products you changed most recently, out of %2$s.', 'woocommerce' ), number_format_i18n( ProductScan::PRODUCT_LIMIT ), number_format_i18n( $available ) )
				/* translators: %s: number of products checked. Reports from before the total was stored. */
				: sprintf( __( 'Checked the %s products you changed most recently.', 'woocommerce' ), number_format_i18n( ProductScan::PRODUCT_LIMIT ) );
		}
		return array(
			'title'        => __( 'Sales readiness', 'woocommerce' ),
			'description'  => __( 'Check how well AI assistants can find and shop your store, and what to improve first.', 'woocommerce' ),
			'last_checked' => self::last_checked( $report ),
			'checked_note' => $truncated,
			'intro'        => null === $report ? __( 'Run a check to see what AI assistants get when they visit your store and read your products.', 'woocommerce' ) : null,
			'run'          => array(
				'label'   => null === $report ? __( 'Run checks', 'woocommerce' ) : __( 'Check again', 'woocommerce' ),
				'pending' => $pending,
				'stalled' => $stalled,
				'message' => $message,
				'link'    => $stalled ? array(
					'href' => admin_url( 'admin.php?page=wc-status&tab=action-scheduler&status=pending' ),
					'text' => __( 'Go to Scheduled Actions', 'woocommerce' ),
				) : null,
			),
			'fix_first'    => null === $report ? null : $this->fix_first( $visible, $report ),
			// Setup steps belong to the Setup checklist, so the question cards show only scanned checks.
			'sections'     => null === $report ? array() : $this->sections( array_values( array_filter( $visible, static fn( array $check ): bool => ! self::is_setup_step( $check ) ) ), $launched, $report ),
			'declared'     => null === $report ? null : self::declared( is_array( $report['declared'] ?? null ) ? $report['declared'] : array() ),
			'overview'     => array(
				'shopping' => $this->shopping_card( $checks ),
				// After the description, when the last check stopped at the product limit.
				'based_on' => null === $truncated ? null : sprintf(
					/* translators: %s: number of products checked. */
					__( 'Based on your %s most recently changed products.', 'woocommerce' ),
					number_format_i18n( ProductScan::PRODUCT_LIMIT )
				),
				'view_all' => array(
					'href' => self::url(),
					'text' => __( 'View all checks', 'woocommerce' ),
				),
			),
		);
	}

	/**
	 * The Overview's AI shopping card: its badge once AI shopping is on, whether AI assistants can shop the store and how
	 * many setup steps are left, and Manage. Before the UCP setting is first saved, it invites the merchant to set up AI
	 * shopping instead; once it has been turned off, it says so.
	 *
	 * @param array<int, array{id:string,ok:bool,blocks:string,level:string,name:string,label:string,why:string,fix:string,fix_url:string}> $checks Store conditions.
	 * @return array{title:string,badge:array{intent:string,text:string}|null,text:string,button:array{href:string,text:string}}
	 */
	private function shopping_card( array $checks ): array {
		$enabled  = UcpUtils::is_enabled();
		$left     = count( self::open_steps( $checks ) );
		$blocked  = array() !== $this->store_conditions->failing_required( $checks );
		$badge    = $this->shopping_badge( $checks );
		$can_shop = $enabled && ! $blocked;
		$set_up   = $enabled || false !== get_option( wc_get_container()->get( FeaturesController::class )->feature_enable_option_name( UcpUtils::FEATURE_ID ) );
		if ( ! $set_up ) {
			$text = __( 'Let shoppers find and buy your products through AI assistants.', 'woocommerce' );
		} elseif ( ! $enabled ) {
			$text = __( 'AI shopping is off.', 'woocommerce' );
		} elseif ( 0 === $left ) {
			$text = __( 'AI assistants can shop your store.', 'woocommerce' );
		} else {
			$text = sprintf(
				/* translators: %d: number of setup steps left. */
				$can_shop ? _n( 'AI assistants can shop your store. %d step left.', 'AI assistants can shop your store. %d steps left.', $left, 'woocommerce' ) : _n( 'AI assistants can\'t shop your store yet. %d step left.', 'AI assistants can\'t shop your store yet. %d steps left.', $left, 'woocommerce' ),
				$left
			);
		}
		return array(
			'title'  => __( 'AI shopping', 'woocommerce' ),
			'badge'  => ! $set_up || 'off' === $badge[0] ? null : self::badge_data( $badge ),
			'text'   => $text,
			'button' => array(
				'href' => AgenticCommerceSettings::url( AgenticCommerceSettings::SHOPPING_SECTION ),
				'text' => $set_up ? __( 'Manage', 'woocommerce' ) : __( 'Set up AI shopping', 'woocommerce' ),
			),
		);
	}

	/**
	 * A badge for the React views: the design system intent and the text.
	 *
	 * @param array{0:string,1:string} $badge Badge class and text.
	 * @return array{intent:string,text:string}
	 */
	private static function badge_data( array $badge ): array {
		return array(
			'intent' => self::INTENTS[ $badge[0] ] ?? 'none',
			'text'   => $badge[1],
		);
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
	 * What a failing store check says: what to do, why it matters and what was found, the fix line,
	 * and where to fix it. A setup step is worded as in Status; a scanned check takes its copy from
	 * ReadinessCopy, or from the report for a check that has none there.
	 *
	 * @param array{id:string,name:string,label?:string,why:string,fix?:string,fix_url:string,conflict?:bool,source?:string} $check Store check.
	 * @return array{title:string,text:string,fix:string,url:string,link?:string}
	 */
	private static function failing_copy( array $check ): array {
		if ( self::is_setup_step( $check ) ) {
			$action = self::check_action( $check );
			return array(
				'title' => $action[0],
				'text'  => $action[1],
				'fix'   => '',
				'url'   => $action[2],
				'link'  => $action[3] ?? '',
			);
		}
		$copy = ReadinessCopy::store_check( $check['id'], (string) ( $check['source'] ?? '' ) );
		if ( null !== $copy ) {
			return array(
				'title' => $copy['action'],
				'text'  => $copy['found'],
				'fix'   => ReadinessCopy::llms_txt_source( (string) ( $check['source'] ?? '' ) )['fix'] ?? $copy['fix'],
				'url'   => '' !== $copy['url'] ? $copy['url'] : $check['fix_url'],
			);
		}
		return array(
			'title' => '' !== ( $check['label'] ?? '' ) ? (string) $check['label'] : $check['name'],
			'text'  => $check['why'],
			'fix'   => (string) ( $check['fix'] ?? '' ),
			'url'   => $check['fix_url'],
		);
	}

	/**
	 * The stored report's channels, skipping entries that are not arrays.
	 *
	 * @param array<string, mixed> $report Stored report.
	 * @return array<string, array<string, mixed>>
	 */
	private static function stored_channels( array $report ): array {
		return array_filter( is_array( $report['channels'] ?? null ) ? $report['channels'] : array(), 'is_array' );
	}

	/**
	 * Whether a failing store check blocks every sale, so it is marked Required: a required setup step,
	 * a scanned check that keeps AI assistants off the store's pages, or one in REQUIRED_SCANNED.
	 *
	 * @param array{id:string,blocks:string,group?:string,level:string,conflict?:bool} $check Store check.
	 */
	private static function is_required( array $check ): bool {
		if ( self::is_setup_step( $check ) ) {
			return StoreConditions::shows_required( $check );
		}
		return in_array( $check['id'], self::REQUIRED_SCANNED, true ) || ( 'listing' === $check['blocks'] && 'access' === ( $check['group'] ?? '' ) );
	}

	/**
	 * Whether a store check joins Fix first when it fails: it is Required, stops every sale (AI
	 * assistants can't buy without it) or keeps products from being listed. The rest are store-wide
	 * problems with no sales to rank by.
	 *
	 * @param array{id:string,blocks:string,group?:string,level:string,conflict?:bool} $check Store check.
	 */
	private static function joins_fix_first( array $check ): bool {
		return self::is_required( $check ) || in_array( $check['id'], self::STOPS_EVERY_SALE, true ) || 'listing' === $check['blocks'];
	}

	/**
	 * The Required badge.
	 *
	 * @return array{0:string,1:string} Badge class and text.
	 */
	private static function required_badge(): array {
		return array( 'fix', __( 'Required', 'woocommerce' ) );
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
			/* translators: %s: time of day, such as 10:42 am. */
			return sprintf( __( 'Last checked today at %s', 'woocommerce' ), wp_date( (string) get_option( 'time_format' ), $time ) );
		}
		/* translators: %s: date and time, such as November 3, 2026 10:42 am. */
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
		$steps   = array_column( $checks, 'ok', 'id' );
		return array_merge(
			self::setup_order( $setup ),
			array_filter(
				$scanned,
				static function ( array $check ) use ( $results, $steps ): bool {
					if ( ! empty( $check['pending'] ) || ( ! UcpUtils::is_enabled() && 'ucp' === $check['blocks'] ) ) {
						return false;
					}
					if ( false === ( $steps[ self::SAME_CAUSE[ $check['id'] ] ?? '' ] ?? null ) ) {
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
	 * The product question: how many products each channel can list, then each failing product
	 * check with what to do and a link to its products.
	 *
	 * @param array<string, mixed> $report Stored report.
	 * @return array<string, mixed>
	 */
	private function product_section( array $report ): array {
		$failing  = self::failing_product_checks( $report );
		$channels = self::stored_channels( $report );
		$linked   = ! empty( $report['failures_stored'] );
		$rows     = array();
		foreach ( $failing as $check ) {
			$issue  = self::product_issue( $check, $linked );
			$rows[] = self::row( (string) ( $check['id'] ?? '' ), 'open', $issue['title'], $issue['why'], $issue['fix'], self::products_link( (int) $check['count'], (string) ( $check['id'] ?? '' ), $linked ) );
		}
		$listed = array();
		foreach ( $channels as $channel_id => $channel ) {
			$listed[] = array(
				'name'     => (string) ( $channel['name'] ?? $channel_id ),
				'listable' => number_format_i18n( (int) ( $channel['listable'] ?? 0 ) ),
				/* translators: %s: number of products checked. */
				'of'       => sprintf( __( 'of %s can be listed', 'woocommerce' ), number_format_i18n( (int) ( $report['products'] ?? 0 ) ) ),
			);
		}
		return array(
			'id'       => 'products',
			'title'    => self::products_question(),
			'badge'    => self::badge_data( self::product_badge( $report ) ),
			'note'     => null,
			'channels' => $listed,
			'rows'     => $rows,
			'folded'   => array(),
			'empty'    => array() === $failing ? array(
				'title' => __( 'Every product passed every check.', 'woocommerce' ),
				'text'  => '',
				'link'  => null,
			) : null,
		);
	}

	/**
	 * A product card on a store with no products: a No products badge and a way to add one, instead
	 * of checks that have nothing to look at.
	 *
	 * @param string $id    Section id.
	 * @param string $title Section title.
	 * @return array<string, mixed>
	 */
	private static function no_products_section( string $id, string $title ): array {
		return array(
			'id'       => $id,
			'title'    => $title,
			'badge'    => self::badge_data( array( 'off', __( 'No products', 'woocommerce' ) ) ),
			'note'     => null,
			'channels' => array(),
			'rows'     => array(),
			'folded'   => array(),
			'empty'    => array(
				'title' => __( 'You don’t have any products yet', 'woocommerce' ),
				'text'  => __( 'Product checks start once you add products.', 'woocommerce' ),
				'link'  => array(
					'href' => admin_url( 'post-new.php?post_type=product' ),
					'text' => __( 'Add a product', 'woocommerce' ),
				),
			),
		);
	}

	/**
	 * One check row for the React views.
	 *
	 * @param string                              $id      The check's id, for tracking.
	 * @param string                              $state   open, required, unknown or pass.
	 * @param string                              $title   Title: what to do while it fails.
	 * @param string                              $text    Why it matters, and what was found while it fails.
	 * @param string                              $fix     What to do, or '' when the title says it.
	 * @param array{href:string,text:string}|null $link    Where to fix it, or the products it affects.
	 * @param array{0:string,1:string}|null       $badge   Badge class and text: Required, or Likely conflict.
	 * @param array{href:string,text:string}|null $seen_at The page a conflict was seen on.
	 * @return array<string, mixed>
	 */
	private static function row( string $id, string $state, string $title, string $text, string $fix = '', ?array $link = null, ?array $badge = null, ?array $seen_at = null ): array {
		return array(
			'id'      => $id,
			'state'   => $state,
			'title'   => $title,
			'badge'   => null === $badge ? null : self::badge_data( $badge ),
			'text'    => trim( $text ),
			'fix'     => trim( $fix ),
			'link'    => $link,
			'seen_at' => $seen_at,
		);
	}

	/**
	 * Fix first: the required steps that fail, then the top FIX_FIRST_ISSUES other issues, ranked by
	 * what they stop (every sale, listing, then findability) and, within that, by the share of sales
	 * on the products they affect, or by how many products when there are no sales to compare.
	 *
	 * @param array<int, array{id:string,ok:bool|null,blocks:string,group?:string,level:string,name:string,label?:string,why:string,fix:string,fix_url:string,conflict?:bool,pending?:bool,seen_at?:string}> $checks Store checks shown on the page.
	 * @param array<string, mixed>                                                                                                                                                                           $report Stored report.
	 * @return array<int, array{id:string,tier:int,title:string,why:string,link:array{href:string,text:string}|null,share:float|null,count:int}>
	 */
	private function fix_first_items( array $checks, array $report ): array {
		$required = array();
		$issues   = array();
		foreach ( $checks as $check ) {
			if ( false !== $check['ok'] ) {
				continue;
			}
			if ( ! self::joins_fix_first( $check ) ) {
				continue;
			}
			$copy        = self::failing_copy( $check );
			$is_required = self::is_required( $check );
			$every_sale  = in_array( $check['id'], self::STOPS_EVERY_SALE, true );
			$item        = array(
				'id'    => $check['id'],
				'tier'  => $is_required ? 0 : ( $every_sale ? 1 : 2 ),
				'title' => $copy['title'],
				'why'   => $copy['text'],
				'link'  => self::fix_link( $copy['url'], $copy['link'] ?? '' ),
				'share' => null,
				'count' => PHP_INT_MAX,
			);
			if ( $is_required ) {
				$required[] = $item;
			} else {
				$issues[] = $item;
			}
		}
		$linked = ! empty( $report['failures_stored'] );
		foreach ( self::failing_product_checks( $report ) as $check ) {
			$blocks_listing = in_array( ReadinessRule::EFFECT_NOT_LISTABLE, array_column( (array) ( $check['channels'] ?? array() ), 'effect' ), true );
			$share          = is_numeric( $check['sales_share'] ?? null ) ? (float) $check['sales_share'] : null;
			$count          = (int) $check['count'];
			$issue          = self::product_issue( $check, $linked );
			$issues[]       = array(
				'id'    => (string) ( $check['id'] ?? '' ),
				'tier'  => $blocks_listing ? 2 : 3,
				'title' => $issue['title'],
				'why'   => $issue['why'],
				'link'  => self::products_link( $count, (string) ( $check['id'] ?? '' ), $linked ),
				'share' => $share,
				'count' => $count,
			);
		}
		// Tier first, then the larger share of sales, then more products. A store-wide issue affects
		// every product, so it ranks as all sales.
		$share = static fn( array $item ): float => $item['share'] ?? ( PHP_INT_MAX === $item['count'] ? 1.0 : 0.0 );
		usort( $issues, static fn( array $a, array $b ): int => array( $a['tier'], $share( $b ), $b['count'] ) <=> array( $b['tier'], $share( $a ), $a['count'] ) );
		return array_merge( $required, array_slice( $issues, 0, self::FIX_FIRST_ISSUES ) );
	}

	/**
	 * The Fix first card: items under a heading for what each group of issues stops, with a line on
	 * how it is ranked. A product issue's line starts with its share of sales. The heading over the
	 * required items carries a Required badge. Null when there is nothing to fix.
	 *
	 * @param array<int, array{id:string,ok:bool|null,blocks:string,group?:string,level:string,name:string,label?:string,why:string,fix:string,fix_url:string,conflict?:bool,pending?:bool,seen_at?:string}> $checks Store checks shown on the page.
	 * @param array<string, mixed>                                                                                                                                                                           $report Stored report.
	 * @return array{title:string,ranking:string,items:array<int, array{id:string,tier:string,required:bool,title:string,why:string,link:array{href:string,text:string}|null}>}|null
	 */
	private function fix_first( array $checks, array $report ): ?array {
		$items = $this->fix_first_items( $checks, $report );
		if ( array() === $items ) {
			return null;
		}
		$tiers = array(
			0 => __( 'Required for AI shopping', 'woocommerce' ),
			1 => __( 'Required for AI shopping', 'woocommerce' ),
			2 => __( 'Needed to list products', 'woocommerce' ),
			3 => __( 'Helps shoppers find products', 'woocommerce' ),
		);
		$days  = (int) ( is_array( $report['sales'] ?? null ) ? ( $report['sales']['days'] ?? 0 ) : 0 );
		$first = true;
		$list  = array();
		foreach ( $items as $item ) {
			$why = $item['why'];
			if ( null !== $item['share'] ) {
				// The first share says over which period; the rest only give the share.
				$why   = trim( self::sales_text( $item['share'], $first ? $days : 0 ) . ' ' . $why );
				$first = false;
			}
			$list[] = array(
				'id'       => $item['id'],
				'tier'     => $tiers[ $item['tier'] ],
				'required' => $item['tier'] < 2,
				'title'    => $item['title'],
				'why'      => $why,
				'link'     => $item['link'],
			);
		}
		return array(
			'title'   => __( 'Fix first', 'woocommerce' ),
			'ranking' => self::ranking_text( $report ),
			'items'   => $list,
		);
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
		/* translators: %d: number of days. */
		return sprintf( _n( 'Start with the fixes that affect the most sales. Sales figures are from the last %d day.', 'Start with the fixes that affect the most sales. Sales figures are from the last %d days.', $days, 'woocommerce' ), $days );
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
	 * "View N products", linked to the Products list filtered to them when each product's failures
	 * are stored; "N products" with no link otherwise.
	 *
	 * @param int    $count  Number of products.
	 * @param string $value  Stored failure value.
	 * @param bool   $linked Whether the list can be filtered.
	 * @return array{href:string,text:string}
	 */
	private static function products_link( int $count, string $value, bool $linked ): array {
		if ( ! $linked ) {
			return array(
				'href' => '',
				/* translators: %s: number of products. */
				'text' => sprintf( _n( '%s product', '%s products', $count, 'woocommerce' ), number_format_i18n( $count ) ),
			);
		}
		return array(
			'href' => ProductsList::url( $value ),
			/* translators: %s: number of products. */
			'text' => sprintf( _n( 'View %s product', 'View %s products', $count, 'woocommerce' ), number_format_i18n( $count ) ),
		);
	}

	/**
	 * A failing product check's copy: what to do ("Add a brand to 13 products"), why it matters, and the
	 * fix line. A check ReadinessCopy has no copy for keeps the report's.
	 *
	 * @param array<string, mixed> $check  Failing product check from the report.
	 * @param bool                 $linked Whether each product's failures are stored, so a few failing products can be named.
	 * @return array{title:string,why:string,fix:string}
	 */
	private static function product_issue( array $check, bool $linked ): array {
		$count = (int) ( $check['count'] ?? 0 );
		$id    = (string) ( $check['id'] ?? '' );
		$names = $count <= ReadinessCopy::NAMED_MAX && $linked ? self::failing_product_names( $id ) : array();
		$copy  = ReadinessCopy::product_check( $id, $count, $names );
		if ( null === $copy ) {
			return array(
				'title' => (string) ( $check['label'] ?? '' ),
				'why'   => (string) ( $check['why'] ?? '' ),
				'fix'   => (string) ( $check['fix'] ?? '' ),
			);
		}
		return array(
			'title' => $copy['action'],
			'why'   => $copy['why'],
			'fix'   => $copy['fix'],
		);
	}

	/**
	 * Names of the products that fail a check, from their stored failures: up to one more than
	 * ReadinessCopy::NAMED_MAX, so a count that changed since the scan doesn't match and the title
	 * falls back to the count.
	 *
	 * @param string $check_id Check id.
	 * @return string[]
	 */
	private static function failing_product_names( string $check_id ): array {
		$posts = get_posts(
			array(
				'post_type'        => 'product',
				'post_status'      => 'any',
				'posts_per_page'   => ReadinessCopy::NAMED_MAX + 1,
				'no_found_rows'    => true,
				'suppress_filters' => true,
				'meta_key'         => ProductFailures::META_KEY, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value'       => $check_id, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			)
		);
		return array_map(
			static fn( \WP_Post $post ): string => html_entity_decode( wp_strip_all_tags( get_the_title( $post ) ), ENT_QUOTES, 'UTF-8' ),
			$posts
		);
	}

	/**
	 * Protocols and files the store serves to agents, as information, never pass or fail: how many were
	 * found, then every item. Null with no items in the report.
	 *
	 * @param array $items Items from the report.
	 * @return array{title:string,text:string,more:string,items:array<int, array{name:string,found:bool}>}|null
	 */
	private static function declared( array $items ): ?array {
		$items = array_values( array_filter( $items, 'is_array' ) );
		if ( array() === $items ) {
			return null;
		}
		return array(
			'title' => self::declared_question(),
			/* translators: %s: how many items were found, such as "4 of 33 found". */
			'text'  => sprintf( __( 'Optional files that some AI assistants look for. You don’t need to fix anything. %s.', 'woocommerce' ), self::declared_count( $items ) ),
			/* translators: %d: number of items. */
			'more'  => sprintf( _n( 'Show %d item', 'Show all %d items', count( $items ), 'woocommerce' ), count( $items ) ),
			'items' => array_map(
				static fn( array $item ): array => array(
					'name'  => (string) ( $item['name'] ?? '' ),
					'found' => true === ( $item['found'] ?? null ),
				),
				$items
			),
		);
	}

	/**
	 * The declared items question's title, on its card and its Overview row.
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
		$found = array_filter( $items, static fn( $item ): bool => is_array( $item ) && true === ( $item['found'] ?? null ) );
		/* translators: 1: number of items found, 2: number of items looked for. */
		return sprintf( __( 'Found %1$s of %2$s', 'woocommerce' ), number_format_i18n( count( $found ) ), number_format_i18n( count( array_filter( $items, 'is_array' ) ) ) );
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
		$passed = count( array_filter( $checks, static fn( array $check ): bool => true === $check['ok'] ) );
		if ( 0 === $passed ) {
			return array( 'off', __( 'Not checked yet', 'woocommerce' ) );
		}
		return count( $checks ) === $passed ? array( 'info', __( 'All passed', 'woocommerce' ) ) : array( 'off', __( 'No issues', 'woocommerce' ) );
	}

	/**
	 * Store checks as rows. A failing check is headed by what to do, then why it matters and what was
	 * found, the fix line when there is one, and a link when WooCommerce has a screen for it; a check
	 * that blocks every sale is marked Required. One that couldn't run says why; a passed one keeps its
	 * passing title and why it matters.
	 *
	 * @param array<int, array{id:string,ok:bool|null,blocks:string,level:string,name:string,label?:string,why:string,fix:string,fix_url:string,conflict?:bool,seen_at?:string,source?:string}> $checks   Checks.
	 * @param bool|null                                                                                                                                                                         $launched Whether the store is launched.
	 * @return array<int, array<string, mixed>>
	 */
	private static function check_rows( array $checks, ?bool $launched ): array {
		$rows = array();
		foreach ( $checks as $check ) {
			$copy = self::is_setup_step( $check ) ? null : ReadinessCopy::store_check( $check['id'] );
			if ( null === $check['ok'] ) {
				$rows[] = self::row( $check['id'], 'unknown', $copy['title'] ?? $check['name'], $launched ? __( 'This couldn\'t be checked. The next check tries again.', 'woocommerce' ) : __( 'Launch your store to run this check.', 'woocommerce' ) );
				continue;
			}
			if ( $check['ok'] ) {
				// A setup step's why describes the failure, so a passed one shows only its name, as in Status.
				$source = ReadinessCopy::llms_txt_source( (string) ( $check['source'] ?? '' ) )['pass'] ?? '';
				$rows[] = self::row( $check['id'], 'pass', $copy['title'] ?? $check['name'], trim( ( $copy['why'] ?? '' ) . ' ' . $source ) );
				continue;
			}
			$failing  = self::failing_copy( $check );
			$required = self::is_required( $check );
			$conflict = ! empty( $check['conflict'] );
			// The URL comes from the stored report, so only http(s) links reach the page.
			$seen_url = $conflict && ! empty( $check['seen_at'] ) ? esc_url_raw( (string) $check['seen_at'], array( 'http', 'https' ) ) : '';
			$seen_at  = '' !== $seen_url
				? array(
					'href' => $seen_url,
					'text' => (string) $check['seen_at'],
				)
				: null;
			$badge    = $required ? self::required_badge() : ( $conflict ? array( 'off', __( 'Likely conflict', 'woocommerce' ) ) : null );
			$rows[]   = self::row( $check['id'], $required ? 'required' : 'open', $failing['title'], $failing['text'], $failing['fix'], self::fix_link( $failing['url'], $failing['link'] ?? '' ), $badge, $seen_at );
		}
		return $rows;
	}

	/**
	 * A failing check's fix link: where it goes, or null without a URL.
	 *
	 * @param string $url  Fix URL.
	 * @param string $text Link text, or '' to name where the URL goes.
	 * @return array{href:string,text:string}|null
	 */
	private static function fix_link( string $url, string $text = '' ): ?array {
		// Scanned checks' URLs come from the stored report, so only http(s) and relative links reach the page.
		$url = esc_url_raw( $url, array( 'http', 'https' ) );
		if ( '' === $url ) {
			return null;
		}
		return array(
			'href' => $url,
			'text' => '' !== $text ? $text : self::fix_link_text( $url ),
		);
	}

	/**
	 * The text of a failing check's fix link: where it goes.
	 *
	 * @param string $url Fix URL.
	 */
	private static function fix_link_text( string $url ): string {
		if ( str_starts_with( $url, admin_url( 'upload.php' ) ) ) {
			return __( 'Go to Media library', 'woocommerce' );
		}
		if ( str_starts_with( $url, admin_url( 'post.php' ) ) ) {
			return __( 'Edit page', 'woocommerce' );
		}
		if ( str_starts_with( $url, admin_url( 'admin.php?page=wc-settings&tab=site-visibility' ) ) ) {
			return __( 'Change site visibility', 'woocommerce' );
		}
		if ( str_starts_with( $url, admin_url( 'options-reading.php' ) ) ) {
			return __( 'Change Reading settings', 'woocommerce' );
		}
		$tabs = array(
			'account'  => __( 'Go to Accounts & Privacy', 'woocommerce' ),
			'checkout' => __( 'Go to Payments', 'woocommerce' ),
			'email'    => __( 'Go to Emails', 'woocommerce' ),
		);
		foreach ( $tabs as $tab => $text ) {
			if ( str_starts_with( $url, admin_url( 'admin.php?page=wc-settings&tab=' . $tab ) ) ) {
				return $text;
			}
		}
		if ( str_starts_with( $url, AgenticCommerceSettings::url( AgenticCommerceSettings::SHOPPING_SECTION ) . '#' ) ) {
			return __( 'Choose a page', 'woocommerce' );
		}
		if ( str_starts_with( $url, admin_url( 'site-health.php' ) ) ) {
			return __( 'View Site Health', 'woocommerce' );
		}
		if ( str_starts_with( $url, admin_url( 'plugins.php' ) ) ) {
			return __( 'View plugins', 'woocommerce' );
		}
		return str_starts_with( $url, admin_url() ) ? __( 'Open settings', 'woocommerce' ) : __( 'Learn how', 'woocommerce' );
	}

	/**
	 * The sections under Fix first: one per merchant question with the checks the last scan ran on
	 * what the store returns, the product checks before the buying question. Inside a section,
	 * problems come first and passed checks are folded.
	 *
	 * @param array<int, array{id:string,ok:bool|null,blocks:string,group?:string,level:string,name:string,label?:string,why:string,fix:string,fix_url:string,conflict?:bool,pending?:bool,seen_at?:string}> $checks   Scanned checks to show.
	 * @param bool                                                                                                                                                                                           $launched Whether the store is launched.
	 * @param array<string, mixed>                                                                                                                                                                           $report   Stored report.
	 * @return array<int, array<string, mixed>>
	 */
	private function sections( array $checks, bool $launched, array $report ): array {
		$sections    = array();
		$no_products = isset( $report['products'] ) && 0 === (int) $report['products'];
		foreach ( self::question_groups( $checks ) as $id => $group ) {
			if ( 'buy' === $id ) {
				$sections[] = $no_products ? self::no_products_section( 'products', self::products_question() ) : $this->product_section( $report );
			}
			if ( $no_products && 'understand' === $id ) {
				$sections[] = self::no_products_section( $id, $group[0] );
				continue;
			}
			$folded     = array_filter( $group[1], static fn( array $check ): bool => true === $check['ok'] );
			$sections[] = array(
				'id'       => $id,
				'title'    => $group[0],
				'badge'    => self::badge_data( self::group_badge( $group[1] ) ),
				'note'     => 'access' === $id && ! UcpUtils::is_enabled() ? __( 'AI shopping is off, so we didn’t check your connection URL.', 'woocommerce' ) : null,
				'channels' => array(),
				'rows'     => self::check_rows( array_values( array_diff_key( $group[1], $folded ) ), $launched ),
				'folded'   => self::check_rows( array_values( $folded ), $launched ),
				'empty'    => null,
			);
		}
		return $sections;
	}

	/**
	 * The store checks by the merchant question they answer, with each question's title.
	 *
	 * @param array<int, array{id:string,ok:bool|null,blocks:string,group?:string,level:string,name:string,label?:string,why:string,fix:string,fix_url:string,conflict?:bool,pending?:bool,seen_at?:string}> $checks Scanned checks to show.
	 * @return array<string, array{0:string,1:array<int, array{id:string,ok:bool|null,blocks:string,group?:string,level:string,name:string,label?:string,why:string,fix:string,fix_url:string,conflict?:bool,pending?:bool,seen_at?:string}>}>
	 */
	private static function question_groups( array $checks ): array {
		$groups = array(
			'access'     => array( __( 'Store access', 'woocommerce' ), array() ),
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
	 * The product question's title.
	 */
	private static function products_question(): string {
		return __( 'Can AI assistants list your products?', 'woocommerce' );
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
