<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\AgentReadiness;

use Automattic\WooCommerce\Internal\Features\FeaturesController;
use Automattic\WooCommerce\Internal\RegisterHooksInterface;
use Automattic\WooCommerce\Internal\Ucp\UcpDiscovery;
use Automattic\WooCommerce\StoreApi\Utilities\Ucp\Utils as UcpUtils;

defined( 'ABSPATH' ) || exit;

/**
 * Sales readiness: when the store was last checked, Fix first,
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
	 * Setup steps by importance: what blocks AI shopping (live, HTTPS, readable permalinks), what
	 * stops orders or is asked for by AI shopping channels, then the optional steps.
	 */
	private const SETUP_ORDER = array( 'launched', 'https', 'ucp_discovery', 'payment_method', 'privacy_page', 'terms_page', 'refund_returns_page', 'search_visible', 'order_emails', 'guest_checkout', 'shipping_policy_page' );

	/**
	 * The admin_menu priority: between WooCommerce's Reports (20) and Settings (50).
	 */
	public const MENU_PRIORITY = 30;

	/**
	 * Product checks that Sales readiness leaves out: product image alt text repeats the images_have_alt store check.
	 */
	private const HIDDEN_PRODUCT_CHECKS = array( 'image_alt_text' );

	/**
	 * Product ids already loaded while building one view, by check id and limit. A check that appears
	 * in Fix first and on its card is queried once. view() clears this, so the next call reads current products.
	 *
	 * @var array<string, array<int, int[]>>
	 */
	private static $failing_ids = array();

	/**
	 * Whether Sales readiness hides this product check (it still appears in the support download).
	 *
	 * @param string $id Check id.
	 */
	public static function hides_product_check( string $id ): bool {
		return in_array( $id, self::HIDDEN_PRODUCT_CHECKS, true );
	}

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
	 * Add the page in the admin.
	 *
	 * @internal
	 */
	public function handle_init(): void {
		if ( ! is_admin() ) {
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
				'title'      => __( 'AI shopping health', 'woocommerce' ),
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
	 * The AI shopping badge: Needs attention while a step shown as Required is open, even with UCP off,
	 * otherwise Active or Off as UCP is. It counts the same steps as the card's Continue setup button.
	 *
	 * @param array<int, array{id:string,ok:bool,blocks:string,level:string,name:string,label:string,why:string,fix:string,fix_url:string}> $checks Store conditions.
	 * @return array{0:string,1:string} Badge class and text.
	 */
	public function shopping_badge( array $checks ): array {
		if ( array() !== array_filter( self::open_steps( $checks ), array( StoreConditions::class, 'shows_required' ) ) ) {
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
		return self::required_first( self::setup_order( array_filter( $checks, static fn( array $check ): bool => 'ucp_on' !== $check['id'] && ! $check['ok'] ) ) );
	}

	/**
	 * The setup steps already done, in the same order as the open ones.
	 *
	 * @param array<int, array{id:string,ok:bool,blocks:string,level:string,name:string,label:string,why:string,fix:string,fix_url:string}> $checks Store conditions.
	 * @return array<int, array{id:string,ok:bool,blocks:string,level:string,name:string,label:string,why:string,fix:string,fix_url:string}>
	 */
	public static function done_steps( array $checks ): array {
		return self::required_first( self::setup_order( array_filter( $checks, static fn( array $check ): bool => 'ucp_on' !== $check['id'] && $check['ok'] ) ) );
	}

	/**
	 * Required steps first, keeping setup order inside each group. A step the rules file marks
	 * Required moves ahead of the Optional ones, open or done.
	 *
	 * @template T of array{id:string,level:string}
	 * @param array<int|string, T> $checks Store conditions, already in setup order.
	 * @return list<T>
	 */
	private static function required_first( array $checks ): array {
		$required = array( StoreConditions::class, 'shows_required' );
		/** @var list<T> $sorted */ // phpcs:ignore Generic.Commenting.DocComment.MissingShort -- PHPStan loses the template type through array_filter.
		$sorted = array_merge( array_values( array_filter( $checks, $required ) ), array_values( array_filter( $checks, static fn( array $check ): bool => ! $required( $check ) ) ) );
		return $sorted;
	}

	/**
	 * Setup steps by importance: the ones that block AI shopping, then the Required ones, then the
	 * Optional ones (SETUP_ORDER). A step not in that list keeps its place after them.
	 *
	 * @template T of array{id:string}
	 * @param array<int|string, T> $checks Store conditions.
	 * @return list<T>
	 */
	private static function setup_order( array $checks ): array {
		$rank   = array_flip( self::SETUP_ORDER );
		$ranked = array();
		$rest   = array();
		foreach ( $checks as $check ) {
			if ( isset( $rank[ $check['id'] ] ) ) {
				$ranked[ $rank[ $check['id'] ] ] = $check;
			} else {
				$rest[] = $check;
			}
		}
		ksort( $ranked );
		/** @var list<T> $sorted */ // phpcs:ignore Generic.Commenting.DocComment.MissingShort -- PHPStan loses the template type through array_filter.
		$sorted = array_merge( array_values( $ranked ), $rest );
		return $sorted;
	}

	/**
	 * Everything the Sales readiness page and the AI tools Overview show, as data for the React views:
	 * when the store was last checked and the check button, Fix first, a section per question, the
	 * declared items, and the Overview's AI shopping card.
	 *
	 * @return array<string, mixed>
	 */
	public function view(): array {
		self::$failing_ids = array();
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
			$message = __( 'Checking your store', 'woocommerce' );
		}
		$truncated = null;
		$by_sales  = false;
		if ( null !== $report && ! empty( $report['truncated'] ) ) {
			$available = (int) ( $report['available'] ?? 0 );
			$by_sales  = $available > ProductScan::PRODUCT_LIMIT && ! empty( $report['bestsellers_first'] );
			$truncated = $available > ProductScan::PRODUCT_LIMIT
				? sprintf(
					$by_sales
						/* translators: 1: number of products checked, 2: number of products in the store. */
						? __( 'Product checks covered %1$s of your %2$s products: your bestsellers first, then the ones you updated most recently. Store conditions covered your whole store. Product details, and some Search and discovery checks, still sample product pages.', 'woocommerce' )
						/* translators: 1: number of products checked, 2: number of products in the store. */
						: __( 'Product checks covered the %1$s products you updated most recently, out of %2$s. Store conditions covered your whole store. Product details, and some Search and discovery checks, still sample product pages.', 'woocommerce' ),
					number_format_i18n( ProductScan::PRODUCT_LIMIT ),
					number_format_i18n( $available )
				)
				/* translators: %s: number of products checked. Reports from before the total was stored. */
				: sprintf( __( 'Product checks covered the %s products you updated most recently. Store conditions covered your whole store. Product details, and some Search and discovery checks, still sample product pages.', 'woocommerce' ), number_format_i18n( ProductScan::PRODUCT_LIMIT ) );
		}
		return array(
			'title'        => __( 'AI shopping health', 'woocommerce' ),
			'description'  => __( 'Check how well AI assistants can find and shop your store, and what to improve first.', 'woocommerce' ),
			'last_checked' => self::last_checked( $report ),
			'checked_note' => $truncated,
			'checked'      => null !== $report,
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
			// The support line's link: WordPress.com support on WordPress.com, else the one in WooCommerce > Home > Help.
			'support_url'  => UcpDiscovery::is_wordpress_com() ? 'https://wordpress.com/help/contact' : 'https://woocommerce.com/my-account/create-a-ticket/?utm_medium=product',
			'coming_soon'  => self::coming_soon_notice( $checks, $report ),
			'fix_first'    => null === $report ? null : $this->fix_first( $visible, $report ),
			// Setup steps live in the Setup checklist and, while one blocks sales, in Fix first; the cards hold what the scan read.
			'sections'     => null === $report ? array() : $this->sections( array_values( array_filter( $visible, static fn( array $check ): bool => ! self::is_setup_step( $check ) ) ), $launched, $report ),
			'declared'     => null === $report ? null : self::declared( is_array( $report['declared'] ?? null ) ? $report['declared'] : array(), $launched, self::checked_while_live( $report ) ),
			'overview'     => array(
				'shopping' => $this->shopping_card( $checks ),
				// After the description, when the last check stopped at the product limit.
				'based_on' => null === $truncated ? null : sprintf(
					$by_sales
						/* translators: %s: number of products checked. */
						? __( 'Based on %s products: your bestsellers first, then the ones you changed most recently.', 'woocommerce' )
						/* translators: %s: number of products checked. */
						: __( 'Based on your %s most recently changed products.', 'woocommerce' ),
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
	 * A product's saved name as entity-decoded plain text, '' when there is none or it no longer exists.
	 * Read from the post, which callers can prime, rather than loading the product. Not sanitized HTML:
	 * a name such as "Baker's <Dozen>" stays whole. Callers must escape for their output context
	 * (React text nodes need none; HTML or attributes need escaping).
	 *
	 * @since 11.4.0
	 *
	 * @param int $product_id Product ID from the report.
	 */
	public static function product_name( int $product_id ): string {
		$post = $product_id > 0 ? get_post( $product_id ) : null;
		if ( ! $post instanceof \WP_Post || ! in_array( $post->post_type, array( 'product', 'product_variation' ), true ) ) {
			return '';
		}
		return html_entity_decode( $post->post_title, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
	}

	/**
	 * The link on the product name in "On the Blue hoodie page, …", to that page. `at` is where the name
	 * starts in the line, in UTF-16 code units, the index the page uses to slice the text. A name that
	 * also appears earlier ("the") is still the one linked. Null when the line names no product.
	 *
	 * @param array  $check      Store check.
	 * @param int    $product_id Product ID from the report.
	 * @param string $product    The product's name, or ''.
	 * @param string $found      The found line.
	 * @return array{href:string,text:string,at:int}|null
	 */
	private static function product_link( array $check, int $product_id, string $product, string $found ): ?array {
		$href = '' !== $product ? get_permalink( $product_id ) : false;
		if ( ! is_string( $href ) || '' === $href ) {
			return null;
		}
		// The same line with a placeholder for the name shows where the name goes, in any language.
		$marker = "\u{E000}";
		$marked = ReadinessCopy::store_check( $check['id'], (string) ( $check['source'] ?? '' ), $marker, (string) ( $check['seen_at'] ?? '' ), true );
		$at     = null === $marked ? false : strpos( $marked['found'], $marker );
		if ( false === $at || substr( $found, $at, strlen( $product ) ) !== $product ) {
			return null;
		}
		return array(
			'href' => $href,
			'text' => $product,
			'at'   => self::utf16_units( substr( $found, 0, $at ) ),
		);
	}

	/**
	 * How many UTF-16 code units $text has. The page slices with JavaScript, which counts in those
	 * units, so an emoji before the product name is two, not one.
	 *
	 * @param string $text Text before the product name.
	 */
	private static function utf16_units( string $text ): int {
		$utf16 = mb_convert_encoding( $text, 'UTF-16LE', 'UTF-8' );
		return is_string( $utf16 ) ? intdiv( strlen( $utf16 ), 2 ) : 0;
	}

	/**
	 * What a failing store check says: what to do, why it matters and what was found, the fix line,
	 * and where to fix it. A setup step is worded as in Status; a scanned check takes its copy from
	 * ReadinessCopy, or from the report for a check that has none there.
	 *
	 * @param array{id:string,name:string,label?:string,why:string,fix?:string,fix_url:string,conflict?:bool,source?:string,product?:int,seen_at?:string} $check Store check.
	 * @return array{title:string,text:string,fix:string,url:string,link?:string,sr?:string,tool?:array{href:string,text:string,at?:int}|null}
	 */
	private static function failing_copy( array $check ): array {
		if ( self::is_setup_step( $check ) ) {
			$action = self::check_action( $check );
			// Policy pages share one button text, Edit page or Choose page, as in the Setup checklist, so the
			// button column stays narrow; screen readers also hear which policy it is for.
			$button = self::step_button( $check['id'], $action[3] );
			return array(
				'title' => $action[0],
				'text'  => $action[1],
				'fix'   => $action[4],
				'url'   => $action[2],
				'link'  => '' !== $button[1] ? $button[0] : $action[3],
				'sr'    => $button[1],
			);
		}
		$product_id = (int) ( $check['product'] ?? 0 );
		$product    = self::product_name( $product_id );
		$copy       = ReadinessCopy::store_check( $check['id'], (string) ( $check['source'] ?? '' ), $product, (string) ( $check['seen_at'] ?? '' ), $product_id > 0 );
		if ( null !== $copy ) {
			return array(
				'title' => $copy['action'],
				'text'  => $copy['found'],
				'fix'   => ReadinessCopy::llms_txt_source( (string) ( $check['source'] ?? '' ) )['fix'] ?? $copy['fix'],
				'url'   => '' !== $copy['url'] ? $copy['url'] : $check['fix_url'],
				'tool'  => null === $copy['found_link'] ? self::product_link( $check, $product_id, $product, $copy['found'] ) : $copy['found_link'],
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
			return sprintf( __( 'Last checked today at %s. Checks run each day.', 'woocommerce' ), wp_date( (string) get_option( 'time_format' ), $time ) );
		}
		/* translators: 1: date, such as November 3, 2026. 2: time of day, such as 10:42 am. */
		return sprintf( __( 'Last checked %1$s at %2$s. Checks run each day.', 'woocommerce' ), wp_date( (string) get_option( 'date_format' ), $time ), wp_date( (string) get_option( 'time_format' ), $time ) );
	}


	/**
	 * Ids of the store checks this page shows, so the support report can note the ones it leaves out.
	 *
	 * @param array<string, mixed>|null                                                                                                     $report Stored report.
	 * @param array<int, array{id:string,ok:bool,blocks:string,level:string,name:string,label:string,why:string,fix:string,fix_url:string}> $checks Store conditions.
	 * @return string[]
	 */
	public function shown_check_ids( ?array $report, array $checks ): array {
		$visible = $this->visible_store_checks( $report, $checks );
		// With no products, sections() shows one "no products" row in place of the Product details checks.
		if ( isset( $report['products'] ) && 0 === (int) $report['products'] ) {
			$visible = array_filter( $visible, static fn( array $check ): bool => 'understand' !== ( $check['group'] ?? '' ) );
		}
		return array_column( $visible, 'id' );
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
			'badge'    => self::badge_data( self::waiting_badge() ),
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
	 * The notice above Fix first while the store is in coming soon mode and the results shown are from
	 * a check that ran while it was live. A check that runs in coming soon mode replaces them, so then
	 * the cards say why the checks didn't run instead.
	 *
	 * @param array<int, array<string, mixed>> $checks Store conditions now.
	 * @param array<string, mixed>|null        $report Stored report.
	 * @return array{text:string,link:array{href:string,text:string}}|null
	 */
	private static function coming_soon_notice( array $checks, ?array $report ): ?array {
		$launched = array_column( $checks, null, 'id' )['launched'] ?? null;
		if ( null === $launched || false !== $launched['ok'] || null === $report ) {
			return null;
		}
		// Only when the stored results are from a live check; a coming soon check says why on the cards.
		if ( true !== self::checked_while_live( $report ) ) {
			return null;
		}
		return array(
			'text' => __( 'Your store is in coming soon mode, so AI assistants can’t find your products yet. These results are from when your store was live.', 'woocommerce' ),
			'link' => array(
				'href' => (string) $launched['fix_url'],
				// The same button as the AI shopping notice, from the content review.
				'text' => __( 'Change site visibility', 'woocommerce' ),
			),
		);
	}

	/**
	 * Whether the store was live when the stored report was built. Null when the report has no launched row.
	 *
	 * @param array<string, mixed>|null $report Stored report.
	 */
	public static function checked_while_live( ?array $report ): ?bool {
		if ( null === $report ) {
			return null;
		}
		$ok = array_column( is_array( $report['store_checks'] ?? null ) ? $report['store_checks'] : array(), 'ok', 'id' )['launched'] ?? null;
		return null === $ok ? null : (bool) $ok;
	}

	/**
	 * Whether a scan ran none of these checks, and none is still waiting for one: the store showed
	 * its coming soon page, or its host stopped it from opening its own pages. A check that had no
	 * product page to open is waiting for a product, not blocked.
	 *
	 * @param array<int, array{ok:bool|null,pending?:bool,no_product?:bool}> $checks The question's checks.
	 */
	public static function none_ran( array $checks ): bool {
		return array() !== $checks && array() === array_filter( $checks, static fn( array $check ): bool => null !== $check['ok'] || ! empty( $check['pending'] ) || ! empty( $check['no_product'] ) );
	}

	/**
	 * The row that stands in for a question's scanned checks when none of them could run, saying why.
	 *
	 * @param bool      $launched Whether the store is launched now.
	 * @param bool|null $was_live Whether the store was live when it was checked.
	 * @return array<string, mixed>
	 */
	private static function cant_check_row( bool $launched, ?bool $was_live = null ): array {
		$copy = self::cant_check_copy( $launched, $was_live );
		return array( 'waiting' => self::checks_waiting( $launched, $was_live ) ) + self::row( 'cant_check', 'unknown', $copy[0], $copy[1] );
	}

	/**
	 * Why none of the scanned checks ran, as a title and a line. The support report says the same.
	 *
	 * @param bool      $launched Whether the store is launched now.
	 * @param bool|null $was_live Whether the store was live when it was checked.
	 * @return array{0:string,1:string}
	 */
	public static function cant_check_copy( bool $launched, ?bool $was_live = null ): array {
		if ( ! $launched ) {
			return array( __( 'Your store is in coming soon mode', 'woocommerce' ), __( 'These checks run once your store is live.', 'woocommerce' ) );
		}
		// Checked in coming soon mode, then the store went live: nothing was blocked; the checks haven't run since.
		if ( false === $was_live ) {
			return array( __( 'Your store wasn’t live at the last check', 'woocommerce' ), __( 'These checks run at the next daily check, or you can run the check again now.', 'woocommerce' ) );
		}
		return array( __( 'Your pages couldn’t be checked', 'woocommerce' ), __( 'Your hosting setup blocked the check from opening them. Checks run again each day. If this keeps happening, contact your hosting provider.', 'woocommerce' ) );
	}

	/**
	 * Whether unchecked rows wait for the merchant (coming soon, or a check from before the store went live).
	 *
	 * @param bool      $launched Whether the store is launched now.
	 * @param bool|null $was_live Whether the store was live when it was checked.
	 */
	private static function checks_waiting( bool $launched, ?bool $was_live ): bool {
		return ! $launched || false === $was_live;
	}

	/**
	 * Why one check has no result, for its row and the support report: coming soon, checked before
	 * the store went live, no product page to check, or it couldn't run.
	 *
	 * @param array{no_product?:bool} $check    Store check with no result.
	 * @param bool                    $launched Whether the store is launched now.
	 * @param bool|null               $was_live Whether the store was live when it was checked.
	 * @return array{0:bool,1:string} Whether the check waits for the merchant (Not checked yet), and why.
	 */
	public static function unchecked_copy( array $check, bool $launched, ?bool $was_live ): array {
		if ( ! $launched ) {
			return array( true, __( 'Launch your store to run this check.', 'woocommerce' ) );
		}
		if ( false === $was_live ) {
			return array( true, __( 'Your store wasn’t live at the last check. This check runs at the next daily check, or you can run the check again now.', 'woocommerce' ) );
		}
		if ( ! empty( $check['no_product'] ) ) {
			return array( true, __( 'This check needs a product page. It runs once you have a published product that AI assistants can see.', 'woocommerce' ) );
		}
		return array( false, __( 'This check couldn’t run. It runs again each day, or you can run the check again now.', 'woocommerce' ) );
	}

	/**
	 * The note shown while AI shopping is off. The support report uses it for the UCP step.
	 */
	public static function ucp_off_note(): string {
		return __( 'Some checks only run when AI shopping is on, like whether AI assistants can connect to your store and send shoppers to your checkout.', 'woocommerce' );
	}

	/**
	 * What the page says about a failing store check, so the support report uses the same words.
	 *
	 * @param array{id:string,name:string,label?:string,why:string,fix?:string,fix_url:string,conflict?:bool,source?:string} $check Store check.
	 */
	public static function failing_text( array $check ): string {
		return self::failing_copy( $check )['text'];
	}

	/**
	 * One check row for the React views.
	 *
	 * @param string                                         $id      The check's id.
	 * @param string                                         $state   open, required, unknown or pass.
	 * @param string                                         $title   Title: what to do while it fails.
	 * @param string                                         $text    Why it matters, and what was found while it fails.
	 * @param string                                         $fix     What to do, or '' when the title says it.
	 * @param array{href:string,text:string,sr?:string}|null $link Where to fix it, or the products it affects. `sr` is what screen readers hear.
	 * @param array{0:string,1:string}|null                  $badge   Badge class and text: Required.
	 * @param array{href:string,text:string}|null            $seen_at The page a conflict was seen on.
	 * @param array{href:string,text:string,at?:int}|null    $text_link A name in the text to link, such as a tool to test with, and where it starts.
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
	 * @return array<int, array{id:string,tier:int,title:string,why:string,link:array{href:string,text:string,sr?:string}|null,share:float|null,store:bool,rank:float,count:int}>
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
				'link'  => self::fix_link( $copy['url'], $copy['link'] ?? '', $copy['sr'] ?? '' ),
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
			'link'    => self::ranking_link( $report ),
			'items'   => $list,
		);
	}

	/**
	 * Where to go so Fix first can rank by sales: turn on Analytics (a report with a null `sales` key was read
	 * without it), or import past orders in Analytics settings.
	 *
	 * @param array<string, mixed> $report Stored report.
	 * @return array{href:string,text:string}|null
	 */
	private static function ranking_link( array $report ): ?array {
		if ( array_key_exists( 'sales', $report ) && null === $report['sales'] ) {
			return array(
				'href' => admin_url( 'admin.php?page=wc-settings&tab=advanced&section=features' ),
				'text' => __( 'Turn on Analytics to see which fixes affect the most sales.', 'woocommerce' ),
			);
		}
		if ( is_array( $report['sales'] ?? null ) && false === ( $report['sales']['imported'] ?? true ) ) {
			return array(
				'href' => admin_url( 'admin.php?page=wc-admin&path=/analytics/settings' ),
				'text' => __( 'Import past orders', 'woocommerce' ),
			);
		}
		return null;
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
			return __( 'Start with the fixes that affect the most products. Sales rankings start once Analytics has imported your past orders.', 'woocommerce' );
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
	 * are stored; "N products" with no link otherwise. One product opens its edit page instead.
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
		$ids  = 1 === $count ? self::failing_product_ids( $value, 2 ) : array();
		$edit = 1 === count( $ids ) ? (string) get_edit_post_link( $ids[0], 'raw' ) : '';
		return array(
			'href' => '' !== $edit ? $edit : ProductsList::url( $value ),
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
		return array_map(
			static fn( int $id ): string => self::product_name( $id ),
			self::failing_product_ids( $check_id, ReadinessCopy::NAMED_MAX + 1 )
		);
	}

	/**
	 * Ids of up to $limit products that fail a check, from their stored failures.
	 *
	 * @param string $check_id Check id.
	 * @param int    $limit    Most ids to return.
	 * @return int[]
	 */
	private static function failing_product_ids( string $check_id, int $limit ): array {
		foreach ( self::$failing_ids[ $check_id ] ?? array() as $cached_limit => $ids ) {
			if ( $cached_limit >= $limit ) {
				return array_slice( $ids, 0, $limit );
			}
		}
		$ids                                      = array_map(
			'intval',
			get_posts(
				array(
					'post_type'        => 'product',
					'post_status'      => 'any',
					'posts_per_page'   => $limit,
					'fields'           => 'ids',
					'no_found_rows'    => true,
					'suppress_filters' => true,
					'meta_key'         => ProductFailures::META_KEY, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
					'meta_value'       => $check_id, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				)
			)
		);
		self::$failing_ids[ $check_id ][ $limit ] = $ids;
		return $ids;
	}

	/**
	 * Protocols and files the store serves to agents, as information, never pass or fail: how many were
	 * found, then every item under where it lives. Items the scan couldn't read are under Couldn't check,
	 * or Not checked yet while the checks wait for the merchant. Null with no items in the report.
	 *
	 * @param array     $items    Items from the report.
	 * @param bool|null $launched Whether the store is launched now.
	 * @param bool|null $was_live Whether the store was live when it was checked.
	 * @return array{title:string,badge:array{intent:string,text:string},text:string,waiting:bool,groups:array<int, array{title:string,items:array<int, array{name:string,found:bool|null}>}>}|null
	 */
	private static function declared( array $items, ?bool $launched = true, ?bool $was_live = true ): ?array {
		$items = array_values( array_filter( $items, 'is_array' ) );
		if ( array() === $items ) {
			return null;
		}
		$waiting = self::checks_waiting( (bool) $launched, $was_live );
		$titles  = array(
			'root'    => __( 'Files in your root folder', 'woocommerce' ),
			'address' => __( 'Files at standard addresses', 'woocommerce' ),
			'code'    => __( 'In your page code', 'woocommerce' ),
			'unknown' => $waiting ? __( 'Not checked yet', 'woocommerce' ) : __( 'Couldn’t check', 'woocommerce' ),
		);
		$groups  = array();
		foreach ( $items as $item ) {
			$found              = $item['found'] ?? null;
			$group              = null === $found ? 'unknown' : DeclaredToAgents::group( (string) ( $item['id'] ?? '' ) );
			$groups[ $group ][] = array(
				'name'  => (string) ( $item['name'] ?? '' ),
				'found' => null === $found ? null : true === $found,
			);
		}
		$found   = count( array_filter( $items, static fn( array $item ): bool => true === ( $item['found'] ?? null ) ) );
		$checked = count( $items ) - count( $groups['unknown'] ?? array() );
		// The count covers the items the scan could read, so a store in coming soon mode isn't told it shares none.
		$text = 0 === $checked
			? __( 'These are other files and code that some AI assistants use to learn about your store. None are required, so there’s nothing to fix.', 'woocommerce' )
			: sprintf(
				/* translators: 1: number of items found, 2: number of items checked. */
				_n(
					'These are other files and code that some AI assistants use to learn about your store. Your store shares %1$s of %2$s. None are required, so there’s nothing to fix.',
					'These are other files and code that some AI assistants use to learn about your store. Your store shares %1$s of these %2$s. None are required, so there’s nothing to fix.',
					$checked,
					'woocommerce'
				),
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
			'title'   => __( 'Extra details for AI assistants', 'woocommerce' ),
			'badge'   => self::badge_data( array( 'off', __( 'Optional', 'woocommerce' ) ) ),
			'text'    => $text,
			// Unread items wait for the merchant, rather than having failed to load.
			'waiting' => $waiting,
			// In the order of $titles, leaving out the groups with no items.
			'groups'  => array_map(
				static fn( string $key ): array => array(
					'title' => $titles[ $key ],
					'items' => $groups[ $key ],
				),
				array_keys( array_intersect_key( $titles, $groups ) )
			),
		);
	}

	/**
	 * The badge on a question: how many issues, that everything passed, or, when nothing failed but a check
	 * couldn't run (its row says why), Couldn't check. Not checked yet while the checks wait for the merchant:
	 * in coming soon mode, or with nothing to check, such as Store access while AI shopping is off.
	 *
	 * @param array<int, array{ok:bool|null,no_product?:bool}> $checks  The question's checks.
	 * @param bool                                             $waiting Whether the checks wait for the merchant, as in coming soon mode.
	 * @return array{0:string,1:string} Badge class and text.
	 */
	private static function group_badge( array $checks, bool $waiting = false ): array {
		$issues = count( array_filter( $checks, static fn( array $check ): bool => false === $check['ok'] ) );
		if ( $issues > 0 ) {
			/* translators: %d: number of issues. */
			return array( 'check', sprintf( _n( '%d issue', '%d issues', $issues, 'woocommerce' ), $issues ) );
		}
		$passed = count( array_filter( $checks, static fn( array $check ): bool => true === $check['ok'] ) );
		if ( $passed > 0 && count( $checks ) === $passed ) {
			return array( 'info', __( 'All passed', 'woocommerce' ) );
		}
		// Only checks still waiting for a product are unchecked: they wait for the merchant too.
		$waiting = $waiting || array() === array_filter( $checks, static fn( array $check ): bool => null === $check['ok'] && empty( $check['no_product'] ) );
		return $waiting ? self::waiting_badge() : self::cant_check_badge();
	}

	/**
	 * The Couldn't check badge, on a question where nothing failed but a check tried to run and couldn't.
	 *
	 * @return array{0:string,1:string} Badge class and text.
	 */
	private static function cant_check_badge(): array {
		return array( 'off', __( 'Couldn’t check', 'woocommerce' ) );
	}

	/**
	 * The Not checked yet badge, while a question's checks wait for the merchant: the store to go live,
	 * a check from before launch to run again, AI shopping to be on, or products to check.
	 *
	 * @return array{0:string,1:string} Badge class and text.
	 */
	private static function waiting_badge(): array {
		return array( 'off', __( 'Not checked yet', 'woocommerce' ) );
	}

	/**
	 * Store checks as rows. A failing check is headed by what to do, then why it matters and what was
	 * found, the fix line when there is one, and a link when WooCommerce has a screen for it; a check
	 * that blocks every sale is marked Required. One that couldn't run says why; a passed one keeps its
	 * passing title and why it matters.
	 *
	 * @param array<int, array{id:string,ok:bool|null,blocks:string,level:string,name:string,label?:string,why:string,fix:string,fix_url:string,conflict?:bool,seen_at?:string,source?:string}> $checks   Checks.
	 * @param bool|null                                                                                                                                                                         $launched Whether the store is launched.
	 * @param bool|null                                                                                                                                                                         $was_live Whether the store was live when it was checked.
	 * @return array<int, array<string, mixed>>
	 */
	private static function check_rows( array $checks, ?bool $launched, ?bool $was_live = true ): array {
		$rows = array();
		foreach ( $checks as $check ) {
			$step = self::is_setup_step( $check ) ? self::passed_step( $check ) : null;
			$copy = null !== $step ? array(
				'title' => $step[0],
				'why'   => $step[1],
			) : ReadinessCopy::store_check( $check['id'] );
			if ( null === $check['ok'] ) {
				// The title says what the check looks for, not that it passed.
				$title  = ReadinessCopy::store_check( $check['id'] )['action'] ?? $copy['title'] ?? $check['name'];
				$why    = self::unchecked_copy( $check, false !== $launched, $was_live );
				$rows[] = array( 'waiting' => $why[0] ) + self::row( $check['id'], 'unknown', $title, $why[1] );
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
					'text' => (string) ( $check['seen_at'] ?? '' ),
				)
				: null;
			$rows[]   = self::row( $check['id'], $required ? 'required' : 'open', $failing['title'], $failing['text'], $failing['fix'], self::fix_link( $failing['url'], $failing['link'] ?? '', $failing['sr'] ?? '' ), $required ? self::required_badge() : null, $seen_at, $failing['tool'] ?? null );
		}
		return $rows;
	}

	/**
	 * A failing check's fix link: where it goes, or null without a URL.
	 *
	 * @param string $url  Fix URL.
	 * @param string $text Link text, or '' to name where the URL goes.
	 * @param string $sr   What screen readers hear in place of the text, or ''.
	 * @return array{href:string,text:string,sr?:string}|null
	 */
	private static function fix_link( string $url, string $text = '', string $sr = '' ): ?array {
		// Scanned checks' URLs come from the stored report, so only http(s) and relative links reach the page.
		$url = esc_url_raw( $url, array( 'http', 'https' ) );
		if ( '' === $url ) {
			return null;
		}
		$link = array(
			'href' => $url,
			'text' => '' !== $text ? $text : self::fix_link_text( $url ),
		);
		if ( '' !== $sr ) {
			$link['sr'] = $sr;
		}
		return $link;
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
		$was_live    = self::checked_while_live( $report );
		$waiting     = self::checks_waiting( $launched, $was_live );
		foreach ( self::question_groups( $checks ) as $id => $group ) {
			if ( 'buy' === $id ) {
				$sections[] = $no_products ? self::no_products_section( 'products', self::products_question(), $unreadable ) : $this->product_section( $report );
			}
			if ( $no_products && 'understand' === $id ) {
				$sections[] = self::no_products_section( $id, $group[0], $unreadable );
				continue;
			}
			// When no check could run, one row says why in their place.
			$cant    = array() !== $report && self::none_ran( $group[1] );
			$checks  = $cant ? array() : $group[1];
			$folded  = array_filter( $checks, static fn( array $check ): bool => true === $check['ok'] );
			$rows    = self::check_rows( array_values( array_diff_key( $checks, $folded ) ), $launched, $was_live );
			$ucp_off = 'access' === $id && ! UcpUtils::is_enabled();
			// With AI shopping off, Store access may have nothing left to show: say what turning it on checks.
			$ucp_only   = $ucp_off && array() !== $report && ! $cant && array() === $checks;
			$sections[] = array(
				'id'       => $id,
				'title'    => $group[0],
				'badge'    => self::badge_data( $cant && array() === $rows ? ( $waiting ? self::waiting_badge() : self::cant_check_badge() ) : self::group_badge( $checks, $waiting ) ),
				'note'     => $ucp_off && ! $ucp_only ? self::ucp_off_note() : ( 'understand' === $id ? self::sample_note( $report ) : null ),
				'channels' => array(),
				'rows'     => $cant ? array_merge( $rows, array( self::cant_check_row( $launched, $was_live ) ) ) : $rows,
				'folded'   => self::check_rows( array_values( $folded ), $launched, $was_live ),
				'empty'    => $ucp_only ? array(
					'title' => __( 'These checks run once AI shopping is on, like whether AI assistants can connect to your store and send shoppers to your checkout.', 'woocommerce' ),
					'text'  => '',
					'link'  => array(
						'href' => AgenticCommerceSettings::url( AgenticCommerceSettings::SHOPPING_SECTION ),
						'text' => __( 'Turn on AI shopping', 'woocommerce' ),
					),
				) : null,
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
			$group = $check['group'] ?? '';
			if ( isset( $groups[ $group ] ) ) {
				$groups[ $group ][1][] = $check;
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
