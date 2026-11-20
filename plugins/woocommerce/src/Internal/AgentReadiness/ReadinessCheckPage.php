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
	 * Scanned checks that are Required although they don't keep AI assistants off the store's pages:
	 * with AI shopping on, AI assistants can't connect, search or check out without them. These checks
	 * are only shown while AI shopping is on. The rules file's `store_checks` can change any scanned check's level.
	 */
	private const REQUIRED_SCANNED = array( 'ucp_profile', 'ucp_checkout' );

	/**
	 * Scanned checks shown with a Warning instead of as Required when they fail: catalog search can fail
	 * now and then with nothing changed on the store, usually because of something in front of it.
	 */
	private const WARNING_SCANNED = array( 'ucp_endpoints_live' );

	/**
	 * Scanned checks that keep products from being listed but aren't Required: a store whose pages all
	 * need a login, such as a members-only store, chose that, and can still be ready for AI assistants that sign in.
	 */
	private const OPTIONAL_SCANNED = array( 'pages_public' );

	/**
	 * Checks that can only run when the check they name passed, so aren't shown when it failed.
	 */
	private const CHECK_NEEDS = array(
		'product_jsonld_from_core'       => 'product_jsonld_present',
		'product_jsonld_required_fields' => 'product_jsonld_present',
		'product_offer_jsonld'           => 'product_jsonld_present',
		'product_return_policy_schema'   => 'product_jsonld_present',
		'product_shipping_schema'        => 'product_jsonld_present',
		'product_buy_action_schema'      => 'product_jsonld_present',
		'llms_txt_valid'                 => 'llms_txt_present',
		'llms_txt_links_resolve'         => 'llms_txt_present',
	);

	/**
	 * Scanned checks left out while a setup step with the same cause fails, so it shows once: WordPress
	 * prints noindex and turns its own sitemap off while "Discourage search engines" is on, WooCommerce adds no returns policy
	 * to product pages until a refund and returns page is chosen, and UCP offers no checkout while
	 * guest checkout is off.
	 */
	private const SAME_CAUSE = array(
		'no_noindex_directives'        => 'search_visible',
		'sitemap_lists_products'       => 'search_visible',
		'sitemap_lastmod'              => 'search_visible',
		'product_return_policy_schema' => 'refund_returns_page',
		'ucp_checkout'                 => 'guest_checkout',
	);

	/**
	 * Checks that also show in another question's card, counted in each card's badge (Tracks counts them
	 * once): "Discourage search engines"
	 * keeps AI assistants from the store's pages (Store access, its group) and from finding it.
	 */
	private const ALSO_IN = array(
		'search_visible' => array( 'find' ),
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
		'warn'  => 'medium',
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
				'title'      => __( 'AI sales readiness', 'woocommerce' ),
				'path'       => self::PATH,
				'capability' => 'manage_woocommerce',
			)
		);
	}

	/**
	 * Link to the page.
	 */
	public static function url(): string {
		return admin_url( 'admin.php?page=wc-admin&path=' . self::PATH );
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
		// Pending on either side of the report read: a scan that finishes during the read must not
		// pair an old report with pending false, and a scan queued during the read must not be hidden.
		$pending  = $this->scheduled_scan->is_pending();
		$report   = $this->agent_readiness->get_cached_report();
		$pending  = $pending || $this->scheduled_scan->is_pending();
		$stalled  = $pending && $this->scheduled_scan->is_stalled();
		$checks   = $this->store_conditions->all();
		$launched = (bool) ( array_column( $checks, 'ok', 'id' )['launched'] ?? true );
		$visible  = $this->visible_store_checks( $report, $checks );
		$message  = '';
		if ( $stalled ) {
			$message = __( 'Checks can’t start because scheduled actions aren’t running on your site. Your hosting provider can usually turn them back on.', 'woocommerce' );
		} elseif ( $pending ) {
			$message = __( 'Checking your store.', 'woocommerce' );
		}
		$truncated = null;
		if ( null !== $report && ! empty( $report['truncated'] ) ) {
			$available = (int) ( $report['available'] ?? 0 );
			$truncated = $available > ProductScan::PRODUCT_LIMIT
				/* translators: 1: number of products checked, 2: number of products in the store. */
				? sprintf( __( 'Product checks covered the %1$s products you updated most recently, out of %2$s. Store conditions and discovery checks covered your whole store; Product details still sample product pages.', 'woocommerce' ), number_format_i18n( ProductScan::PRODUCT_LIMIT ), number_format_i18n( $available ) )
				/* translators: %s: number of products checked. Reports from before the total was stored. */
				: sprintf( __( 'Product checks covered the %s products you updated most recently. Store conditions and discovery checks covered your whole store; Product details still sample product pages.', 'woocommerce' ), number_format_i18n( ProductScan::PRODUCT_LIMIT ) );
		}
		return array(
			'title'        => __( 'AI sales readiness', 'woocommerce' ),
			'description'  => __( 'Check how well AI assistants can find and shop your store, and what to improve first.', 'woocommerce' ),
			'last_checked' => self::last_checked( $report ),
			'checked_note' => $truncated,
			'intro'        => null === $report ? __( 'Run a check to see what AI assistants get when they visit your store and read your products.', 'woocommerce' ) : null,
			'run'          => array(
				'label'   => null === $report ? __( 'Check your store', 'woocommerce' ) : __( 'Check again', 'woocommerce' ),
				'pending' => $pending,
				'stalled' => $stalled,
				'message' => $message,
				'link'    => $stalled ? array(
					'href' => admin_url( 'admin.php?page=wc-status&tab=action-scheduler&status=pending' ),
					'text' => __( 'View Scheduled Actions', 'woocommerce' ),
				) : null,
			),
			'fix_first'    => null === $report ? null : $this->fix_first( $visible, $report ),
			// Setup steps also show in the Setup checklist; here they sit in the card of the question they answer.
			'sections'     => null === $report ? array() : $this->sections( $visible, $launched, $report ),
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
	 * many setup steps are left, and a button to the settings. Before the UCP setting is first saved, it invites the merchant to set up AI
	 * shopping instead; once it has been turned off, it says so.
	 *
	 * @param array<int, array{id:string,ok:bool,blocks:string,level:string,name:string,label:string,why:string,fix:string,fix_url:string}> $checks Store conditions.
	 * @return array{title:string,badge:array{intent:string,text:string}|null,text:string,button:array{href:string,text:string}}
	 */
	private function shopping_card( array $checks ): array {
		$enabled  = UcpUtils::is_enabled();
		$left     = count( self::open_steps( $checks ) );
		$badge    = $this->shopping_badge( $checks );
		$blocked  = array() !== $this->store_conditions->failing_required( $checks );
		$can_shop = $enabled && ! $blocked;
		// As in the design: Continue setup while a required step is left, as the Setup checklist counts them, otherwise View settings.
		$button = array() !== array_filter( self::open_steps( $checks ), array( StoreConditions::class, 'shows_required' ) ) ? __( 'Continue setup', 'woocommerce' ) : __( 'View settings', 'woocommerce' );
		$set_up = $enabled || false !== get_option( $this->features_controller->feature_enable_option_name( UcpUtils::FEATURE_ID ) );
		if ( ! $set_up ) {
			$text   = __( 'Let shoppers find and buy your products through AI assistants.', 'woocommerce' );
			$button = __( 'Set up AI shopping', 'woocommerce' );
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
				'text' => $button,
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
	 * The name of the product a page check failed on, '' when there is none or it no longer exists.
	 *
	 * @param int $product_id Product ID from the report.
	 */
	private static function product_name( int $product_id ): string {
		$product = $product_id > 0 ? wc_get_product( $product_id ) : null;
		return $product instanceof \WC_Product ? wp_strip_all_tags( html_entity_decode( $product->get_name(), ENT_QUOTES | ENT_HTML5, 'UTF-8' ) ) : '';
	}

	/**
	 * What a failing store check says: what to do, why it matters and what was found, the fix line,
	 * and where to fix it. A setup step is worded as in Status; a scanned check takes its copy from
	 * ReadinessCopy, or from the report for a check that has none there.
	 *
	 * @param array{id:string,name:string,label?:string,why:string,fix?:string,fix_url:string,conflict?:bool,source?:string,product?:int,seen_at?:string} $check Store check.
	 * @return array{title:string,text:string,fix:string,url:string,link?:string,tool?:array{href:string,text:string}|null}
	 */
	private static function failing_copy( array $check ): array {
		if ( self::is_setup_step( $check ) ) {
			$action = self::check_action( $check );
			return array(
				'title' => $action[0],
				'text'  => $action[1],
				'fix'   => $action[4],
				'url'   => $action[2],
				'link'  => $action[3],
			);
		}
		$copy = ReadinessCopy::store_check( $check['id'], (string) ( $check['source'] ?? '' ), self::product_name( (int) ( $check['product'] ?? 0 ) ), (string) ( $check['seen_at'] ?? '' ) );
		if ( null !== $copy ) {
			return array(
				'title' => $copy['action'],
				'text'  => $copy['found'],
				'fix'   => ReadinessCopy::llms_txt_source( (string) ( $check['source'] ?? '' ) )['fix'] ?? $copy['fix'],
				'url'   => '' !== $copy['url'] ? $copy['url'] : $check['fix_url'],
				'tool'  => $copy['found_link'],
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
	 * Whether a failing store check blocks every sale, so it is marked Required: a required setup step,
	 * a scanned check that keeps AI assistants off the store's pages, or one in REQUIRED_SCANNED.
	 *
	 * @param array{id:string,blocks:string,group?:string,level:string,conflict?:bool} $check Store check.
	 */
	private static function is_required( array $check ): bool {
		if ( self::is_setup_step( $check ) ) {
			return StoreConditions::shows_required( $check );
		}
		return RemoteRules::store_check( $check['id'] )['required'] ?? ( in_array( $check['id'], self::REQUIRED_SCANNED, true ) || ( 'listing' === $check['blocks'] && 'access' === ( $check['group'] ?? '' ) && ! in_array( $check['id'], self::OPTIONAL_SCANNED, true ) ) );
	}

	/**
	 * Whether a store check joins Fix first when it fails: it is Required or keeps products from being
	 * listed. The rest are store-wide problems with no sales to rank by.
	 *
	 * @param array{id:string,blocks:string,group?:string,level:string,conflict?:bool} $check Store check.
	 */
	private static function joins_fix_first( array $check ): bool {
		return self::is_required( $check ) || 'listing' === $check['blocks'];
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
					if ( ! empty( $check['pending'] ) || ! empty( $check['skipped'] ) || ( ! UcpUtils::is_enabled() && 'ucp' === $check['blocks'] ) ) {
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
		$channels = array_filter( is_array( $report['channels'] ?? null ) ? $report['channels'] : array(), 'is_array' );
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
			'badge'    => self::badge_data( array() === $failing ? array( 'info', __( 'All passed', 'woocommerce' ) ) : self::group_badge( array_fill( 0, count( $failing ), array( 'ok' => false ) ) ) ),
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
	 * A product card when the scan checked nothing: an empty store, or products it cannot read yet.
	 *
	 * @param string $id         Section id.
	 * @param string $title      Section title.
	 * @param bool   $unreadable True when the store has products, but only hidden or unsupported ones.
	 * @return array<string, mixed>
	 */
	private static function no_products_section( string $id, string $title, bool $unreadable = false ): array {
		return array(
			'id'       => $id,
			'title'    => $title,
			'badge'    => self::badge_data( array( 'off', $unreadable ? __( 'Not checked', 'woocommerce' ) : __( 'No products', 'woocommerce' ) ) ),
			'note'     => null,
			'channels' => array(),
			'rows'     => array(),
			'folded'   => array(),
			'empty'    => $unreadable ? array(
				'title' => __( 'None of your products can be checked yet', 'woocommerce' ),
				'text'  => __( 'They are hidden from the catalog, or a type AI assistants can’t list yet.', 'woocommerce' ),
				'link'  => array(
					'href' => AgenticCommerceSettings::url( AgenticCommerceSettings::SHOPPING_SECTION ) . '#wc-agentic-commerce-visibility',
					'text' => __( 'View product visibility', 'woocommerce' ),
				),
			) : array(
				'title' => __( 'You don’t have any products yet', 'woocommerce' ),
				'text'  => __( 'These checks start once you add a product.', 'woocommerce' ),
				'link'  => array(
					'href' => admin_url( 'post-new.php?post_type=product' ),
					'text' => __( 'Add a product', 'woocommerce' ),
				),
			),
		);
	}

	/**
	 * Whether a scan ran none of these checks, and none is still waiting for one: the store showed
	 * its coming soon page, or its host stopped it from opening its own pages.
	 *
	 * @param array<int, array{ok:bool|null,pending?:bool}> $checks The question's checks.
	 */
	private static function none_ran( array $checks ): bool {
		return array() !== $checks && array() === array_filter( $checks, static fn( array $check ): bool => null !== $check['ok'] || ! empty( $check['pending'] ) );
	}

	/**
	 * The row that stands in for a question's scanned checks when none of them could run, saying why.
	 *
	 * @param bool $launched Whether the store is launched.
	 * @return array<string, mixed>
	 */
	private static function cant_check_row( bool $launched ): array {
		return self::row(
			'cant_check',
			'unknown',
			$launched ? __( 'We couldn’t check your pages', 'woocommerce' ) : __( 'Your store is in coming soon mode', 'woocommerce' ),
			$launched ? __( 'Your hosting setup blocked the check from opening them. Try again later, or contact your hosting provider.', 'woocommerce' ) : __( 'These checks run once your store is live.', 'woocommerce' )
		);
	}

	/**
	 * One check row for the React views.
	 *
	 * @param string                              $id      The check's id.
	 * @param string                              $state   open, required, unknown or pass.
	 * @param string                              $title   Title: what to do while it fails.
	 * @param string                              $text    Why it matters, and what was found while it fails.
	 * @param string                              $fix     What to do, or '' when the title says it.
	 * @param array{href:string,text:string}|null $link    Where to fix it, or the products it affects.
	 * @param array{0:string,1:string}|null       $badge   Badge class and text: Required.
	 * @param array{href:string,text:string}|null $seen_at The page a conflict was seen on.
	 * @param array{href:string,text:string}|null $text_link A name in the text to link, such as a tool to test with.
	 * @return array<string, mixed>
	 */
	private static function row( string $id, string $state, string $title, string $text, string $fix = '', ?array $link = null, ?array $badge = null, ?array $seen_at = null, ?array $text_link = null ): array {
		return array(
			'id'        => $id,
			'state'     => $state,
			'title'     => $title,
			'badge'     => null === $badge ? null : self::badge_data( $badge ),
			'text'      => trim( $text ),
			'fix'       => trim( $fix ),
			'link'      => $link,
			'seen_at'   => $seen_at,
			'text_link' => $text_link,
		);
	}

	/**
	 * Fix first: the required steps that fail, then the top FIX_FIRST_ISSUES other issues, ranked by
	 * what they stop (listing, then findability) and, within that, by the share of sales on the
	 * products they affect, then by how many products, then in page order.
	 *
	 * @param array<int, array{id:string,ok:bool|null,blocks:string,group?:string,level:string,name:string,label?:string,why:string,fix:string,fix_url:string,conflict?:bool,pending?:bool,seen_at?:string}> $checks Store checks shown on the page.
	 * @param array<string, mixed>                                                                                                                                                                           $report Stored report.
	 * @return array<int, array{id:string,tier:int,title:string,why:string,link:array{href:string,text:string}|null,share:float|null,store:bool,rank:float,count:int}>
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
			$item        = array(
				'id'    => $check['id'],
				'tier'  => $is_required ? 0 : 2,
				'title' => $copy['title'],
				'why'   => $copy['text'],
				'link'  => self::fix_link( $copy['url'], $copy['link'] ?? '' ),
				'share' => null,
				'store' => true,
				// A store-wide issue affects every product, so it ranks as all sales.
				'rank'  => 1.0,
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
			// A share dropped because the count changed since the scan still ranks, so fixing one product doesn't move the issue down.
			$rank  = $share ?? ( is_numeric( $check['ranked_share'] ?? null ) ? (float) $check['ranked_share'] : 0.0 );
			$count = (int) $check['count'];
			$issue = self::product_issue( $check, $linked );
			// Without sales to compare, an issue that keeps products out of more AI apps ranks higher.
			$apps     = count( array_filter( (array) ( $check['channels'] ?? array() ), static fn( $channel ): bool => in_array( $channel['effect'] ?? '', array( ReadinessRule::EFFECT_NOT_LISTABLE, ReadinessRule::EFFECT_GAPS ), true ) ) );
			$issues[] = array(
				'id'    => (string) ( $check['id'] ?? '' ),
				'tier'  => $blocks_listing ? 2 : 3,
				'title' => $issue['title'],
				'why'   => $issue['why'],
				'link'  => self::products_link( $count, (string) ( $check['id'] ?? '' ), $linked ),
				'share' => $share,
				'store' => false,
				'rank'  => $rank,
				'count' => 0.0 < $rank ? $count : $count * max( 1, $apps ),
			);
		}
		// Tier first, then the larger share of sales, then more products, then page order (usort isn't stable before PHP 8).
		$order = array_flip( array_keys( $issues ) );
		uksort( $issues, static fn( int $a, int $b ): int => array( $issues[ $a ]['tier'], $issues[ $b ]['rank'], $issues[ $b ]['count'], $order[ $a ] ) <=> array( $issues[ $b ]['tier'], $issues[ $a ]['rank'], $issues[ $a ]['count'], $order[ $b ] ) );
		return array_merge( $required, array_slice( $issues, 0, self::FIX_FIRST_ISSUES ) );
	}

	/**
	 * The Fix first card: items under a heading for what each group of issues stops, with a line on
	 * how it is ranked. Each issue says in bold what it affects. With nothing to fix,
	 * there is no card, as in the design.
	 *
	 * @param array<int, array{id:string,ok:bool|null,blocks:string,group?:string,level:string,name:string,label?:string,why:string,fix:string,fix_url:string,conflict?:bool,pending?:bool,seen_at?:string}> $checks Store checks shown on the page.
	 * @param array<string, mixed>                                                                                                                                                                           $report Stored report.
	 * @return array{title:string,ranking:string,link:array{href:string,text:string}|null,items:array<int, array{id:string,tier:string,badge:array{intent:string,text:string}|null,title:string,impact:string|null,why:string,link:array{href:string,text:string}|null}>}|null
	 */
	private function fix_first( array $checks, array $report ): ?array {
		$items = $this->fix_first_items( $checks, $report );
		if ( array() === $items ) {
			return null;
		}
		$tiers = array(
			0 => __( 'Required for AI shopping', 'woocommerce' ),
			2 => __( 'Needed to list products', 'woocommerce' ),
			3 => __( 'Helps shoppers find products', 'woocommerce' ),
		);
		// Store-wide issues say so only when the card compares sales; the ranking line gives the period.
		$by_sales = array() !== array_filter( $items, static fn( array $item ): bool => ( $item['share'] ?? 0.0 ) > 0.0 );
		$list     = array();
		foreach ( $items as $item ) {
			$list[] = array(
				'id'     => $item['id'],
				'tier'   => $tiers[ $item['tier'] ],
				// The same Required badge as the AI shopping Setup checklist, so this tier stands out.
				'badge'  => 0 === $item['tier'] ? self::badge_data( self::required_badge() ) : null,
				'title'  => $item['title'],
				'impact' => null !== $item['share'] ? self::sales_impact( $item['share'] ) : ( $by_sales && $item['store'] && 0 !== $item['tier'] ? __( 'Affects your whole store.', 'woocommerce' ) : null ),
				'why'    => $item['why'],
				'link'   => $item['link'],
			);
		}
		return array(
			'title'   => __( 'Fix first', 'woocommerce' ),
			'ranking' => self::ranking_text( $report, $items ),
			// With Analytics off, where to turn it on: a report with a null `sales` key was read without it.
			'link'    => array_key_exists( 'sales', $report ) && null === $report['sales'] ? array(
				'href' => admin_url( 'admin.php?page=wc-settings&tab=advanced&section=features' ),
				'text' => __( 'Turn on Analytics to see which fixes affect the most sales.', 'woocommerce' ),
			) : null,
			'items'   => $list,
		);
	}

	/**
	 * How Fix first is ranked, and what the sales shares compare. Sales are named only when an issue
	 * on the card has sales to compare.
	 *
	 * @param array<string, mixed>                $report Stored report.
	 * @param array<int, array{share:float|null}> $items  Fix first items.
	 */
	private static function ranking_text( array $report, array $items ): string {
		// A report from before sales were read has no `sales` key; the next check fills it in.
		if ( ! array_key_exists( 'sales', $report ) ) {
			return __( 'Start with the fixes that affect the most products. Check again to rank them by sales.', 'woocommerce' );
		}
		$sales = is_array( $report['sales'] ) ? $report['sales'] : null;
		if ( null === $sales ) {
			return __( 'Start with the fixes that affect the most products.', 'woocommerce' );
		}
		if ( false === ( $sales['imported'] ?? true ) ) {
			return __( 'Analytics hasn\'t imported all your past orders yet, so issues are ranked by how many products and AI apps they affect. Import them in Analytics > Settings.', 'woocommerce' );
		}
		$orders = (int) ( $sales['orders'] ?? 0 );
		$days   = (int) ( $sales['days'] ?? 0 );
		// No sales, or too few to rank by.
		if ( $orders < ProductSales::MIN_ORDERS ) {
			return __( 'Start with the fixes that affect the most products. Sales rankings start once your store has enough sales.', 'woocommerce' );
		}
		// No issue on the card with sales: the products sold nothing, store-wide issues filled the card, or counts changed since the check.
		if ( array() === array_filter( $items, static fn( array $item ): bool => ( $item['share'] ?? 0.0 ) > 0.0 ) ) {
			return __( 'Ranked by how many products and AI apps each issue affects.', 'woocommerce' );
		}
		/* translators: %d: number of days. */
		return sprintf( _n( 'Start with the fixes that affect the most sales. Sales figures are from the last %d day.', 'Start with the fixes that affect the most sales. Sales figures are from the last %d days.', $days, 'woocommerce' ), $days );
	}

	/**
	 * What a product issue costs in sales, shown in bold before its line: "Affects 18% of your sales.".
	 *
	 * @param float $share Share of sales, from 0 to 1.
	 */
	private static function sales_impact( float $share ): string {
		if ( 0.0 === $share ) {
			return __( 'These products had no recent sales.', 'woocommerce' );
		}
		if ( $share < 0.005 ) {
			return __( 'Affects less than 1% of your sales.', 'woocommerce' );
		}
		/* translators: %s: a whole number, such as 18. */
		return sprintf( __( 'Affects %s%% of your sales.', 'woocommerce' ), number_format_i18n( (int) round( $share * 100 ) ) );
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
			'text' => 1 === $count ? __( 'View product', 'woocommerce' ) : sprintf( _n( 'View %s product', 'View %s products', $count, 'woocommerce' ), number_format_i18n( $count ) ),
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
	 * found, then every item under where it lives. Items the scan couldn't read are under Not checked yet.
	 * Null with no items in the report.
	 *
	 * @param array $items Items from the report.
	 * @return array{title:string,badge:array{intent:string,text:string},text:string,groups:array<int, array{title:string,items:array<int, array{name:string,found:bool|null}>}>}|null
	 */
	private static function declared( array $items ): ?array {
		$items = array_values( array_filter( $items, 'is_array' ) );
		if ( array() === $items ) {
			return null;
		}
		$titles = array(
			'root'    => __( 'Files in your root folder', 'woocommerce' ),
			'address' => __( 'Files at standard addresses', 'woocommerce' ),
			'code'    => __( 'In your page code', 'woocommerce' ),
			'extra'   => __( 'Extra details for AI assistants', 'woocommerce' ),
			'unknown' => __( 'Not checked yet', 'woocommerce' ),
		);
		$groups = array_fill_keys( array_keys( $titles ), array() );
		foreach ( $items as $item ) {
			$found              = $item['found'] ?? null;
			$group              = null === $found ? 'unknown' : DeclaredToAgents::group( (string) ( $item['id'] ?? '' ) );
			$groups[ $group ][] = array(
				'name'  => (string) ( $item['name'] ?? '' ),
				'found' => null === $found ? null : true === $found,
			);
		}
		$found   = count( array_filter( $items, static fn( array $item ): bool => true === ( $item['found'] ?? null ) ) );
		$checked = count( $items ) - count( $groups['unknown'] );
		// The count covers the items the scan could read, so a store in coming soon mode isn't told it shares none.
		$text = 0 === $checked
			? __( 'These are other files and code that some AI assistants use to learn about your store. None are required, so there’s nothing to fix.', 'woocommerce' )
			: sprintf(
				/* translators: 1: number of items found, 2: number of items checked. */
				__( 'These are other files and code that some AI assistants use to learn about your store. Your store shares %1$s of these %2$s. None are required, so there’s nothing to fix.', 'woocommerce' ),
				number_format_i18n( $found ),
				number_format_i18n( $checked )
			);
		// Served llms.txt caches guidance for a few minutes for anonymous requests, so a fresh
		// policy/MCP/UCP change can briefly look missing until that cache expires.
		foreach ( $items as $item ) {
			if ( 'llms-txt-woocommerce-guidance' === ( $item['id'] ?? '' ) && false === ( $item['found'] ?? null ) ) {
				$text .= ' ' . __( 'If you just changed policy pages, MCP, or UCP, wait a few minutes and check again. WooCommerce caches its llms.txt briefly.', 'woocommerce' );
				break;
			}
		}
		return array(
			'title'  => __( 'What does your store share with AI assistants?', 'woocommerce' ),
			'badge'  => self::badge_data( array( 'off', __( 'Optional', 'woocommerce' ) ) ),
			'text'   => $text,
			'groups' => array_values(
				array_filter(
					array_map(
						static fn( string $key ): array => array(
							'title' => $titles[ $key ],
							'items' => $groups[ $key ],
						),
						array_keys( $titles )
					),
					static fn( array $group ): bool => array() !== $group['items']
				)
			),
		);
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
			$step = self::is_setup_step( $check ) ? self::passed_step( $check ) : null;
			$copy = null !== $step ? array(
				'title' => $step[0],
				'why'   => $step[1],
			) : ReadinessCopy::store_check( $check['id'] );
			if ( null === $check['ok'] ) {
				$rows[] = self::row( $check['id'], 'unknown', $copy['title'] ?? $check['name'], $launched ? __( 'This couldn\'t be checked. The next check tries again.', 'woocommerce' ) : __( 'Launch your store to run this check.', 'woocommerce' ) );
				continue;
			}
			if ( $check['ok'] ) {
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
			$warning  = ! $required && in_array( $check['id'], self::WARNING_SCANNED, true );
			$badge    = $required ? self::required_badge() : ( $warning ? array( 'warn', __( 'Warning', 'woocommerce' ) ) : null );
			$rows[]   = self::row( $check['id'], $required ? 'required' : 'open', $failing['title'], $failing['text'], $failing['fix'], self::fix_link( $failing['url'], $failing['link'] ?? '' ), $badge, $seen_at, $failing['tool'] ?? null );
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
		$targets = array(
			'upload.php'                              => __( 'View images', 'woocommerce' ),
			'post.php'                                => __( 'Edit page', 'woocommerce' ),
			'admin.php?page=wc-settings&tab=site-visibility' => __( 'Change site visibility', 'woocommerce' ),
			'options-reading.php'                     => __( 'Change Reading settings', 'woocommerce' ),
			'admin.php?page=wc-settings&tab=account'  => __( 'View account settings', 'woocommerce' ),
			'admin.php?page=wc-settings&tab=checkout' => __( 'View payment settings', 'woocommerce' ),
			'admin.php?page=wc-settings&tab=email'    => __( 'View email settings', 'woocommerce' ),
			'admin.php?page=wc-settings&tab=shipping' => __( 'View shipping settings', 'woocommerce' ),
			'admin.php?page=wc-settings&tab=products' => __( 'View product settings', 'woocommerce' ),
			'site-health.php'                         => __( 'View Site Health', 'woocommerce' ),
			'plugins.php'                             => __( 'View plugins', 'woocommerce' ),
		);
		foreach ( $targets as $path => $text ) {
			if ( str_starts_with( $url, admin_url( $path ) ) ) {
				return $text;
			}
		}
		if ( str_starts_with( $url, AgenticCommerceSettings::url( AgenticCommerceSettings::SHOPPING_SECTION ) . '#' ) ) {
			return __( 'Choose page', 'woocommerce' );
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
		$sections = array();
		// products counts only what the scan can check. Hidden and unsupported products are counted apart, so they are not an empty store.
		$no_products = isset( $report['products'] ) && 0 === (int) $report['products'];
		$unreadable  = $no_products && ( (int) ( $report['hidden_from_catalog'] ?? 0 ) > 0 || (int) ( $report['unsupported_types'] ?? 0 ) > 0 );
		foreach ( self::question_groups( $checks ) as $id => $group ) {
			if ( 'buy' === $id ) {
				$sections[] = $no_products ? self::no_products_section( 'products', self::products_question(), $unreadable ) : $this->product_section( $report );
			}
			if ( $no_products && 'understand' === $id ) {
				$sections[] = self::no_products_section( $id, $group[0], $unreadable );
				continue;
			}
			// When no scanned check could run, one row says why in their place; setup steps still show.
			$scanned    = array_filter( $group[1], static fn( array $check ): bool => ! self::is_setup_step( $check ) );
			$cant       = array() !== $report && self::none_ran( $scanned );
			$checks     = $cant ? array_diff_key( $group[1], $scanned ) : $group[1];
			$folded     = array_filter( $checks, static fn( array $check ): bool => true === $check['ok'] );
			$rows       = self::check_rows( array_values( array_diff_key( $checks, $folded ) ), $launched );
			$sections[] = array(
				'id'       => $id,
				'title'    => $group[0],
				'badge'    => self::badge_data( $cant && array() === $rows ? array( 'off', __( 'Couldn’t check', 'woocommerce' ) ) : self::group_badge( $checks ) ),
				'note'     => 'access' === $id && ! UcpUtils::is_enabled() ? __( 'Some checks only run when AI shopping is on, like whether AI assistants can connect to your store and send shoppers to your checkout.', 'woocommerce' ) : ( 'understand' === $id ? self::sample_note( $report ) : null ),
				'channels' => array(),
				'rows'     => $cant ? array_merge( $rows, array( self::cant_check_row( $launched ) ) ) : $rows,
				'folded'   => self::check_rows( array_values( $folded ), $launched ),
				'empty'    => null,
			);
		}
		return $sections;
	}

	/**
	 * Which product pages the Product details checks read. Null for a report from before they read several.
	 *
	 * @param array<string, mixed> $report Stored report.
	 */
	private static function sample_note( array $report ): ?string {
		if ( ! array_key_exists( 'pages_best_seller', $report ) ) {
			return null;
		}
		return $report['pages_best_seller']
			? __( 'These checks look at a selection of your products, including your bestsellers and newest additions. If one product has an issue, others may too.', 'woocommerce' )
			: __( 'These checks look at a selection of your products, including your newest additions. If one product has an issue, others may too.', 'woocommerce' );
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
			'find'       => array( __( 'Search and discovery', 'woocommerce' ), array() ),
			'understand' => array( __( 'Product details', 'woocommerce' ), array() ),
			'buy'        => array( __( 'Buying through AI assistants', 'woocommerce' ), array() ),
		);
		foreach ( $checks as $check ) {
			// A report from before checks named their question has no group; the next scan fills it in.
			foreach ( array_merge( array( $check['group'] ?? '' ), self::ALSO_IN[ $check['id'] ] ?? array() ) as $group ) {
				if ( isset( $groups[ $group ] ) ) {
					$groups[ $group ][1][] = $check;
				}
			}
		}
		return $groups;
	}

	/**
	 * The product question's title.
	 */
	private static function products_question(): string {
		return __( 'Listings in AI shopping apps', 'woocommerce' );
	}
}
