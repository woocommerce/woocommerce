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
	 * Badge intents of the design system, by the badge class the PHP pages use.
	 */
	private const INTENTS = array(
		'check' => 'medium',
		'info'  => 'informational',
		'pass'  => 'stable',
		'off'   => 'none',
		'fix'   => 'medium',
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
		return self::group_badge( array_merge( $this->visible_store_checks( $report, $checks ), array_fill( 0, count( self::failing_product_checks( $report ) ), array( 'ok' => false ) ) ) );
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
		$open = array_values( array_filter( $checks, static fn( array $check ): bool => 'ucp_on' !== $check['id'] && ! $check['ok'] ) );
		// Required first; usort keeps the order within each level.
		usort( $open, static fn( array $a, array $b ): int => ( StoreConditions::LEVEL_REQUIRED === $b['level'] ) <=> ( StoreConditions::LEVEL_REQUIRED === $a['level'] ) );
		return $open;
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
			$message = __( 'The check has not started because scheduled actions do not seem to be running on this site. It runs once they do. See WooCommerce > Status > Scheduled Actions.', 'woocommerce' );
		} elseif ( $pending ) {
			$message = __( 'Checking your store.', 'woocommerce' );
		}
		$truncated = null !== $report && ! empty( $report['truncated'] )
			/* translators: %s: number of products checked. */
			? sprintf( __( 'Checked your %s most recently changed products.', 'woocommerce' ), number_format_i18n( ProductScan::PRODUCT_LIMIT ) )
			: null;
		return array(
			'title'        => __( 'Sales readiness', 'woocommerce' ),
			'description'  => __( 'See how AI assistants see your store, and what to improve.', 'woocommerce' ),
			'last_checked' => self::last_checked( $report ),
			'checked_note' => $truncated,
			'intro'        => null === $report ? __( 'Run a check to see what AI assistants get when they visit your store and read your products.', 'woocommerce' ) : null,
			'run'          => array(
				'label'   => null === $report ? __( 'Run check', 'woocommerce' ) : __( 'Check again', 'woocommerce' ),
				'pending' => $pending,
				'stalled' => $stalled,
				'message' => $message,
			),
			'fix_first'    => null === $report ? null : $this->fix_first( $visible, $report ),
			'sections'     => null === $report ? array() : $this->sections( $visible, $launched, $report ),
			'declared'     => null === $report ? null : self::declared( is_array( $report['declared'] ?? null ) ? $report['declared'] : array() ),
			'overview'     => array(
				'shopping' => $this->shopping_card( $checks ),
				'view_all' => array(
					'href' => self::url(),
					'text' => __( 'View all checks', 'woocommerce' ),
				),
			),
		);
	}

	/**
	 * The Overview's AI shopping card: its badge, whether AI assistants can shop the store and how
	 * many setup steps are left, and Manage, or Set up AI shopping while it is off.
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
		if ( ! $enabled && ! $blocked ) {
			$text = __( 'Make your products available to AI shopping assistants.', 'woocommerce' );
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
			'badge'  => 'off' === $badge[0] ? null : self::badge_data( $badge ),
			'text'   => $text,
			'button' => array(
				'href' => AgenticCommerceSettings::url( AgenticCommerceSettings::SHOPPING_SECTION ),
				'text' => $enabled ? __( 'Manage', 'woocommerce' ) : __( 'Set up AI shopping', 'woocommerce' ),
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
	 * The product question: how many products each channel can list, then each failing product
	 * check with what to do and a link to its products.
	 *
	 * @param array<string, mixed> $report Stored report.
	 * @return array<string, mixed>
	 */
	private function product_section( array $report ): array {
		$failing  = self::failing_product_checks( $report );
		$channels = is_array( $report['channels'] ?? null ) ? $report['channels'] : array();
		$linked   = ! empty( $report['failures_stored'] );
		$rows     = array();
		foreach ( $failing as $check ) {
			$issue  = self::product_issue( $check, $channels );
			$rows[] = self::row( 'open', $issue[0], array( $issue[1] ), self::products_link( (int) $check['count'], (string) ( $check['id'] ?? '' ), $linked ) );
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
			'empty'    => array() === $failing ? __( 'Every product passed every check.', 'woocommerce' ) : null,
		);
	}

	/**
	 * One check row for the React views.
	 *
	 * @param string                              $state    open, required, unknown or pass.
	 * @param string                              $title    Title.
	 * @param array<int, string>                  $lines    Lines under the title; empty ones are dropped.
	 * @param array{href:string,text:string}|null $link     Where to fix it, or the products it affects.
	 * @param string                              $affects  "Affects: …" line, or ''.
	 * @param array{href:string,text:string}|null $seen_at  The page a conflict was seen on.
	 * @return array<string, mixed>
	 */
	private static function row( string $state, string $title, array $lines, ?array $link = null, string $affects = '', ?array $seen_at = null ): array {
		return array(
			'state'   => $state,
			'title'   => $title,
			'affects' => $affects,
			'lines'   => array_values( array_filter( $lines, static fn( string $line ): bool => '' !== trim( $line ) ) ),
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
	 * @return array<int, array{tier:int,title:string,why:string,impact:array{0:string,1:string}|null,link:array{href:string,text:string}|null,share:float|null,count:int}>
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
			$is_required = $is_step && StoreConditions::LEVEL_REQUIRED === ( $check['level'] ?? '' );
			// Agents can't buy without these, or can't reach the store's pages at all.
			$every_sale = in_array( $check['id'], self::STOPS_EVERY_SALE, true ) || ( 'listing' === $check['blocks'] && 'access' === ( $check['group'] ?? '' ) );
			// Only store-wide problems join Fix first; the rest have no sales to rank by.
			if ( ! $is_required && ! $every_sale && 'listing' !== $check['blocks'] ) {
				continue;
			}
			$item = array(
				'tier'   => $is_required ? 0 : ( $every_sale ? 1 : 2 ),
				'title'  => $action[0],
				'why'    => $action[1],
				// The tier heading already says a non-required step stops every sale.
				'impact' => $is_required ? array( 'check', __( 'Required', 'woocommerce' ) ) : ( $every_sale ? null : array( 'off', __( 'All products', 'woocommerce' ) ) ),
				'link'   => self::fix_link( $action[2] ),
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
				'title'  => $issue[0],
				'why'    => $issue[1],
				'impact' => null === $share
					/* translators: %s: number of products. */
					? array( 'off', sprintf( _n( '%s product', '%s products', $count, 'woocommerce' ), number_format_i18n( $count ) ) )
					: array( 'off', self::share_badge( $share ) ),
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
	 * The Fix first card: items under a heading for what each group of issues stops, with a line on
	 * how it is ranked. A product issue's line starts with its share of sales. Null when there is
	 * nothing to fix.
	 *
	 * @param array<int, array{id:string,ok:bool|null,blocks:string,group?:string,level:string,name:string,label?:string,why:string,fix:string,fix_url:string,conflict?:bool,pending?:bool,seen_at?:string}> $checks Store checks shown on the page.
	 * @param array<string, mixed>                                                                                                                                                                           $report Stored report.
	 * @return array{title:string,ranking:string,items:array<int, array{tier:string,title:string,why:string,impact:array{intent:string,text:string}|null,link:array{href:string,text:string}|null}>}|null
	 */
	private function fix_first( array $checks, array $report ): ?array {
		$items = $this->fix_first_items( $checks, $report );
		if ( array() === $items ) {
			return null;
		}
		$tiers = array(
			0 => __( 'Stops every sale', 'woocommerce' ),
			1 => __( 'Stops every sale', 'woocommerce' ),
			2 => __( 'Products that can\'t be listed', 'woocommerce' ),
			3 => __( 'Products that are harder to find', 'woocommerce' ),
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
				'tier'   => $tiers[ $item['tier'] ],
				'title'  => $item['title'],
				'why'    => $why,
				'impact' => null === $item['impact'] ? null : self::badge_data( $item['impact'] ),
				'link'   => $item['link'],
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
			'text'  => sprintf( __( 'Optional files some AI tools look for. Nothing to fix. %s.', 'woocommerce' ), self::declared_count( $items ) ),
			/* translators: %d: number of items. */
			'more'  => sprintf( _n( 'Show the %d item', 'Show the %d items', count( $items ), 'woocommerce' ), count( $items ) ),
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
		return sprintf( __( '%1$s of %2$s found', 'woocommerce' ), number_format_i18n( count( $found ) ), number_format_i18n( count( array_filter( $items, 'is_array' ) ) ) );
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
	 * @return array<int, array<string, mixed>>
	 */
	private static function check_rows( array $checks, ?bool $launched ): array {
		$rows = array();
		foreach ( $checks as $check ) {
			if ( null === $check['ok'] ) {
				$rows[] = self::row( 'unknown', $check['name'], array( $launched ? __( 'This couldn\'t be checked. The next check tries again.', 'woocommerce' ) : __( 'Launch your store to run this check.', 'woocommerce' ) ) );
				continue;
			}
			if ( $check['ok'] ) {
				// A check's why describes the failure, so a passed one shows only its name, as in Status.
				$rows[] = self::row( 'pass', $check['name'], array() );
				continue;
			}
			if ( self::is_setup_step( $check ) ) {
				// A setup step, from StoreConditions: worded as in Status.
				$action   = self::check_action( $check );
				$required = StoreConditions::LEVEL_REQUIRED === $check['level'];
				$rows[]   = self::row( $required ? 'required' : 'open', $action[0], array( $action[1] ), self::fix_link( $action[2] ), self::affects_text( $check ) );
				continue;
			}
			$seen_at = ! empty( $check['conflict'] ) && ! empty( $check['seen_at'] )
				? array(
					'href' => (string) $check['seen_at'],
					'text' => (string) $check['seen_at'],
				)
				: null;
			$rows[]  = self::row( 'open', self::check_title( $check ), array( $check['why'], $check['fix'] ), self::fix_link( $check['fix_url'] ), self::affects_text( $check ), $seen_at );
		}
		return $rows;
	}

	/**
	 * A failing check's fix link: where it goes, or null without a URL.
	 *
	 * @param string $url Fix URL.
	 * @return array{href:string,text:string}|null
	 */
	private static function fix_link( string $url ): ?array {
		if ( '' === $url ) {
			return null;
		}
		return array(
			'href' => $url,
			'text' => self::fix_link_text( $url ),
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
			return __( 'Go to Site visibility', 'woocommerce' );
		}
		return str_starts_with( $url, admin_url() ) ? __( 'Open settings', 'woocommerce' ) : __( 'Learn how', 'woocommerce' );
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
		$sections = array();
		foreach ( self::question_groups( $checks ) as $id => $group ) {
			if ( 'buy' === $id ) {
				$sections[] = $this->product_section( $report );
			}
			$folded     = array_filter( $group[1], static fn( array $check ): bool => true === $check['ok'] );
			$sections[] = array(
				'id'       => $id,
				'title'    => $group[0],
				'badge'    => self::badge_data( self::group_badge( $group[1] ) ),
				'note'     => 'access' === $id && ! UcpUtils::is_enabled() ? __( 'Agent shopping is off, so the UCP checks don\'t run.', 'woocommerce' ) : null,
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
	 * The product question's title.
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
