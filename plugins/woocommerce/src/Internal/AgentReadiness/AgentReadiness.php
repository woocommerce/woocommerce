<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\AgentReadiness;

use Automattic\WooCommerce\Internal\AgentReadiness\Checks\DuplicateGtinCheck;
use Automattic\WooCommerce\Internal\AgentReadiness\Checks\ImageTooSmallCheck;
use Automattic\WooCommerce\Internal\AgentReadiness\Checks\MissingBrandCheck;
use Automattic\WooCommerce\Internal\AgentReadiness\Checks\MissingGtinCheck;
use Automattic\WooCommerce\Internal\AgentReadiness\Checks\MissingRegularPriceCheck;
use Automattic\WooCommerce\Internal\AgentReadiness\Checks\SimpleCheck;

defined( 'ABSPATH' ) || exit;

/**
 * Runs the readiness checks and returns the shared report format described in
 * this directory's README.
 *
 * @since 11.4.0
 */
class AgentReadiness {

	/**
	 * Product ids kept per check in the report.
	 */
	public const PRODUCT_IDS_LIMIT = 10;

	/**
	 * Option holding the last report, kept until the next scan replaces it.
	 */
	public const REPORT_OPTION = 'woocommerce_agent_readiness_report';

	/**
	 * Store-wide conditions.
	 *
	 * @var StoreConditions
	 */
	private StoreConditions $store_conditions;

	/**
	 * Store checks that read what the store returns.
	 *
	 * @var StoreOutputChecks
	 */
	private StoreOutputChecks $store_output_checks;

	/**
	 * What the store declares to agents.
	 *
	 * @var DeclaredToAgents
	 */
	private DeclaredToAgents $declared_to_agents;

	/**
	 * Rules from the published rules file.
	 *
	 * @var RemoteRules
	 */
	private RemoteRules $remote_rules;

	/**
	 * Scan reused for single-product checks, with the shared GTINs the last stored scan found.
	 *
	 * @var ProductScan|null
	 */
	private ?ProductScan $product_scan = null;

	/**
	 * Rules that run and their channel maps, built once per instance.
	 *
	 * @var array{rules:array<string, ReadinessRule>,channel_rules:array<string, array<string, array{field:string,effect:string}>>}|null
	 */
	private ?array $active_rules = null;

	/**
	 * Initialize dependencies.
	 *
	 * @internal
	 *
	 * @param StoreConditions       $store_conditions    Store-wide conditions.
	 * @param StoreOutputChecks     $store_output_checks Store checks that read what the store returns.
	 * @param RemoteRules           $remote_rules        Rules from the published rules file.
	 * @param DeclaredToAgents|null $declared_to_agents  What the store declares to agents, resolved from the container when omitted.
	 */
	final public function init( StoreConditions $store_conditions, StoreOutputChecks $store_output_checks, RemoteRules $remote_rules, ?DeclaredToAgents $declared_to_agents = null ): void {
		$this->store_conditions    = $store_conditions;
		$this->store_output_checks = $store_output_checks;
		$this->declared_to_agents  = $declared_to_agents ?? wc_get_container()->get( DeclaredToAgents::class );
		$this->remote_rules        = $remote_rules;
	}

	/**
	 * The bundled checks, keyed by id.
	 *
	 * @return array<string, ReadinessRule>
	 */
	public function get_default_rules(): array {
		$simple = SimpleCheck::table();
		$rules  = array();
		foreach (
			array(
				new MissingRegularPriceCheck(),
				$simple['missing_images'],
				$simple['missing_product_text'],
				new MissingBrandCheck(),
				$simple['invalid_gtin'],
				new MissingGtinCheck(),
				new DuplicateGtinCheck(),
				new ImageTooSmallCheck(),
				$simple['uncategorised'],
				$simple['variation_attributes_not_set'],
				$simple['missing_variation_images'],
				$simple['title_too_long'],
				$simple['image_alt_text'],
			) as $rule
		) {
			$rules[ $rule->id() ] = $rule;
		}
		return $rules;
	}

	/**
	 * Field and effect per channel for each check that runs. With a stored
	 * rules file, the file is the list: an id it leaves out does not run, an
	 * id code does not know is dropped, and an id whose entry is malformed
	 * runs with its bundled channels. A check no channel asks for is named
	 * with an empty channel map. With no file, the bundled checks
	 * run. The woocommerce_agent_readiness_rules filter runs last, and a check
	 * whose id is missing from the filtered array does not run.
	 *
	 * @return array<string, array<string, array{field:string,effect:string}>>
	 */
	public function get_channel_rules(): array {
		$bundled = array();
		foreach ( $this->get_default_rules() as $id => $rule ) {
			$bundled[ $id ] = $rule->channels();
		}
		if ( $this->remote_rules->has_stored_file() ) {
			$from_file = array();
			foreach ( $this->sanitize_channel_rules( $this->remote_rules->channel_rules(), $bundled ) as $id => $channels ) {
				$from_file[ $id ] = array() !== $channels ? $channels : $bundled[ $id ];
			}
			// A file with no check this code can run does not turn the bundled checks off.
			$defaults = array() !== $from_file ? $from_file : $bundled;
		} else {
			$defaults = $bundled;
		}

		/**
		 * Filters the per-channel field and effect of each agent readiness check.
		 *
		 * Keys are check ids; values map a channel id (chatgpt, google, copilot)
		 * to the feed field the check tests there and its effect: not_listable
		 * when the channel's spec requires the field, gaps when the product
		 * lists with a gap. Remove a check id to stop that check running. Add
		 * back an id the rules file left out to run that bundled check.
		 *
		 * @since 11.4.0
		 *
		 * @param array $rules Field and effect by channel, keyed by check id.
		 */
		$filtered = apply_filters( 'woocommerce_agent_readiness_rules', $defaults );

		if ( ! is_array( $filtered ) ) {
			return $defaults;
		}
		// A check left with no valid channel doesn't run, as if the filter had removed it, unless it never had one.
		return array_filter(
			$this->sanitize_channel_rules( $filtered, $bundled ),
			static fn( array $channels, $id ): bool => ! empty( $channels ) || empty( $bundled[ $id ] ),
			ARRAY_FILTER_USE_BOTH
		);
	}

	/**
	 * Keep only known checks, known channels and valid effects from a filtered rules array.
	 *
	 * @param array                                                           $filtered Filtered rules.
	 * @param array<string, array<string, array{field:string,effect:string}>> $defaults Bundled rules.
	 * @return array<string, array<string, array{field:string,effect:string}>>
	 */
	private function sanitize_channel_rules( array $filtered, array $defaults ): array {
		$channels = FeedChannels::all();
		$effects  = array( ReadinessRule::EFFECT_NOT_LISTABLE, ReadinessRule::EFFECT_GAPS );
		$rules    = array();
		foreach ( $filtered as $id => $rule_channels ) {
			if ( ! isset( $defaults[ $id ] ) || ! is_array( $rule_channels ) ) {
				continue;
			}
			$rules[ $id ] = array();
			foreach ( $rule_channels as $channel => $spec ) {
				$field = is_array( $spec ) ? ( $spec['field'] ?? null ) : null;
				if ( ! isset( $channels[ $channel ] ) || ! is_string( $field ) || ! in_array( $spec['effect'] ?? null, $effects, true ) ) {
					continue;
				}
				$rules[ $id ][ $channel ] = array(
					'field'  => $field,
					'effect' => $spec['effect'],
				);
			}
		}
		return $rules;
	}

	/**
	 * Run every check over the catalog.
	 *
	 * @return array{generated_at:string,products:int,hidden_from_catalog:int,unsupported_types:int,store_checks:array<int,array<string,mixed>>,declared:array<int,array{id:string,name:string,found:bool|null}>,channels:array<string,array<string,mixed>>,checks:array<int,array<string,mixed>>,truncated:bool}
	 */
	public function get_report(): array {
		return $this->build_report();
	}

	/**
	 * Run every check over the catalog.
	 *
	 * @param callable|null $on_tested Called with each product id and its failures.
	 * @return array{generated_at:string,products:int,hidden_from_catalog:int,unsupported_types:int,store_checks:array<int,array<string,mixed>>,declared:array<int,array{id:string,name:string,found:bool|null}>,channels:array<string,array<string,mixed>>,checks:array<int,array<string,mixed>>,truncated:bool,shared_gtins?:array<int,string>}
	 */
	private function build_report( ?callable $on_tested = null ): array {
		// Rebuild the rules and the scan for each report: a long-running process may have fetched a new rules file or changed GTINs since.
		$this->active_rules = null;
		$this->product_scan = null;
		$active             = $this->get_active_rules();
		$rules              = $active['rules'];
		$channel_rules      = $active['channel_rules'];

		$scan = new ProductScan();
		if ( null === $on_tested ) {
			$scan->evaluate( $rules, $channel_rules, self::PRODUCT_IDS_LIMIT );
		} else {
			$scan->evaluate_each( $rules, $channel_rules, self::PRODUCT_IDS_LIMIT, $on_tested );
		}

		$checks = array();
		foreach ( $rules as $id => $rule ) {
			$result   = $scan->result( $id );
			$checks[] = array(
				'id'          => $id,
				'label'       => $rule->label(),
				'why'         => $rule->why(),
				'fix'         => $rule->fix(),
				'channels'    => $channel_rules[ $id ],
				'count'       => $result['count'],
				'product_ids' => $result['product_ids'],
				'fix_url'     => admin_url( 'edit.php?post_type=product' ),
			);
		}

		$responses = $this->store_output_checks->responses();

		$report = array(
			'generated_at'        => gmdate( 'c' ),
			'products'            => $scan->products(),
			'hidden_from_catalog' => $scan->hidden_from_catalog(),
			'unsupported_types'   => $scan->unsupported_types(),
			'store_checks'        => array_merge( $this->store_conditions->all(), $this->store_output_checks->all_for( $responses ) ),
			'declared'            => $this->declared_to_agents->all( $responses ),
			'channels'            => $scan->channel_totals(),
			'checks'              => $checks,
			'truncated'           => $scan->truncated(),
		);
		if ( null !== $on_tested && null !== $scan->shared_gtins() ) {
			// Stored for checks of one product, so a save doesn't read every published product.
			$report['shared_gtins'] = $scan->shared_gtins();
		}
		return $report;
	}

	/**
	 * The last stored report, or null when there is none.
	 *
	 * @return array<string, mixed>|null
	 */
	public function get_cached_report(): ?array {
		$report = get_option( self::REPORT_OPTION );
		if ( ! is_array( $report ) ) {
			return null;
		}
		unset( $report['shared_gtins'] );
		return self::with_saved_products( $report );
	}

	/**
	 * Whether a report has been stored.
	 */
	public function has_report(): bool {
		return is_array( get_option( self::REPORT_OPTION ) );
	}

	/**
	 * A stored report with its product counts as they stand now, so a product fixed since the scan no longer counts.
	 *
	 * @param array<string, mixed> $report Stored report.
	 * @return array<string, mixed>
	 */
	private static function with_saved_products( array $report ): array {
		if ( empty( $report['failures_stored'] ) ) {
			return $report;
		}
		$counts = ProductFailures::counts();
		$checks = is_array( $report['checks'] ?? null ) ? $report['checks'] : array();
		// One query for the example products' meta, instead of one per product.
		$example_ids = array();
		foreach ( $checks as $check ) {
			$example_ids = array_merge( $example_ids, array_map( 'intval', is_array( $check ) ? (array) ( $check['product_ids'] ?? array() ) : array() ) );
		}
		if ( array() !== $example_ids ) {
			update_meta_cache( 'post', array_unique( $example_ids ) );
		}
		foreach ( $checks as $index => $check ) {
			if ( ! is_array( $check ) || ! isset( $check['id'] ) ) {
				continue;
			}
			$id                                        = (string) $check['id'];
			$report['checks'][ $index ]['count']       = $counts[ $id ] ?? 0;
			$report['checks'][ $index ]['product_ids'] = array_values(
				array_filter(
					(array) ( $check['product_ids'] ?? array() ),
					static fn( $product_id ): bool => in_array( $id, ProductFailures::for_product( (int) $product_id ), true )
				)
			);
		}
		// Every product the checks cover has a checked row, so products added, fixed or removed since the scan count now.
		$products           = $counts[ ProductFailures::CHECKED ] ?? 0;
		$report['products'] = $products;
		foreach ( is_array( $report['channels'] ?? null ) ? $report['channels'] : array() as $id => $channel ) {
			if ( ! is_array( $channel ) ) {
				continue;
			}
			$not_listable              = $counts[ ProductFailures::channel_value( ProductScan::NOT_LISTABLE, (string) $id ) ] ?? 0;
			$with_gaps                 = $counts[ ProductFailures::channel_value( ProductScan::WITH_GAPS, (string) $id ) ] ?? 0;
			$report['channels'][ $id ] = array_merge(
				$channel,
				array(
					'listable'     => max( 0, $products - $not_listable ),
					'not_listable' => $not_listable,
					'with_gaps'    => $with_gaps,
					'complete'     => max( 0, $products - $not_listable - $with_gaps ),
				)
			);
		}
		return $report;
	}

	/**
	 * Store checks as they stand now: the store conditions read live, then the
	 * store output checks from the stored report, since those run only in the scan.
	 * Output checks are always recommended, whatever the stored report says.
	 *
	 * @param array<string, mixed>|null                                                                                                               $report Stored report.
	 * @param array<int, array{id:string,ok:bool|null,blocks:string,level:string,name:string,label:string,why:string,fix:string,fix_url:string}>|null $live   Store conditions already read, or null to read them.
	 * @return array<int, array{id:string,ok:bool|null,blocks:string,group?:string,level:string,name:string,label:string,why:string,fix:string,fix_url:string,conflict?:bool,pending?:bool}>
	 */
	public function get_store_checks( ?array $report, ?array $live = null ): array {
		$checks   = $live ?? $this->store_conditions->all();
		$live_ids = array_column( $checks, 'id' );
		foreach ( is_array( $report['store_checks'] ?? null ) ? $report['store_checks'] : array() as $check ) {
			if ( ! is_array( $check ) || ! isset( $check['id'], $check['name'] ) || in_array( $check['id'], $live_ids, true ) ) {
				continue;
			}
			$checks[] = array(
				'id'       => (string) $check['id'],
				'ok'       => isset( $check['ok'] ) ? (bool) $check['ok'] : null,
				'blocks'   => (string) ( $check['blocks'] ?? '' ),
				'group'    => (string) ( $check['group'] ?? '' ),
				'level'    => StoreConditions::LEVEL_RECOMMENDED,
				'name'     => (string) $check['name'],
				'label'    => (string) ( $check['label'] ?? '' ),
				'why'      => (string) ( $check['why'] ?? '' ),
				'fix'      => (string) ( $check['fix'] ?? '' ),
				'fix_url'  => (string) ( $check['fix_url'] ?? '' ),
				'conflict' => ! empty( $check['conflict'] ),
				'pending'  => ! empty( $check['pending'] ),
			);
		}
		return $checks;
	}

	/**
	 * The stored report with setup checks read now, or null when there is none.
	 * Output checks stay as the scan stored them.
	 *
	 * @return array<string, mixed>|null
	 */
	public function get_served_report(): ?array {
		$report = $this->get_cached_report();
		if ( null !== $report ) {
			$report['store_checks'] = $this->get_store_checks( $report );
		}
		return $report;
	}

	/**
	 * Run the report and store it. Not autoloaded: only the readiness screens read it.
	 *
	 * @return array<string, mixed>
	 */
	public function refresh_report(): array {
		// Captured before the scan. A product saved while it runs is absent here, so its new rows stay.
		$had_rows = ProductFailures::stored_product_ids();
		$tested   = array();
		$report   = $this->build_report(
			static function ( int $product_id, array $failures ) use ( &$tested ): void {
				$tested[] = $product_id;
				ProductFailures::store( $product_id, $failures );
			}
		);
		ProductFailures::keep_only( $had_rows, $tested );
		$report['failures_stored'] = true;
		update_option( self::REPORT_OPTION, $report, false );
		unset( $report['shared_gtins'] );
		return $report;
	}

	/**
	 * Ids of the checks one product fails. A variation is judged by its parent,
	 * as the scan counts it; other products the scan leaves out (other types,
	 * hidden from the shop catalog, unpublished) have no issues.
	 *
	 * @param \WC_Product $product Product or variation.
	 * @return array<int, string>
	 */
	public function get_failing_check_ids( \WC_Product $product ): array {
		$result = $this->check_product( $product );
		return null === $result ? array() : array_map( 'strval', array_keys( $result['checks'] ) );
	}

	/**
	 * One product as the scan judges it: each failing check with its field and
	 * effect per channel for this product, and the product's status on each
	 * channel. Null for products the scan leaves out (grouped, external,
	 * unpublished). A variation is judged by its parent.
	 *
	 * For extensions that rank or fix products one at a time; the stored
	 * report keeps only counts and a few example ids per check. Shared GTINs
	 * come from the last stored scan, so a GTIN shared since then, on this
	 * product or another, is flagged at the next scan. Core refuses an exact
	 * duplicate on save, so this is one that differs only by separators or
	 * leading zeros, or a direct meta write. Before the first scan, or past
	 * ProductScan::SHARED_GTINS_LIMIT, they're read from the catalog.
	 *
	 * @param \WC_Product $product Product or variation.
	 * @return array{product_id:int,checks:array<string, array<string, array{field:string,effect:string}>>,statuses:array<string, string>}|null
	 */
	public function check_product( \WC_Product $product ): ?array {
		if ( $product instanceof \WC_Product_Variation ) {
			$parent = wc_get_product( $product->get_parent_id() );
			if ( ! $parent instanceof \WC_Product ) {
				return null;
			}
			$product = $parent;
		}
		if ( ! ProductScan::covers( $product ) ) {
			return null;
		}
		if ( null === $this->product_scan ) {
			$this->product_scan = new ProductScan();
			$stored             = get_option( self::REPORT_OPTION );
			if ( is_array( $stored ) && is_array( $stored['shared_gtins'] ?? null ) ) {
				$this->product_scan->use_shared_gtins( $stored['shared_gtins'] );
			}
		}
		$active = $this->get_active_rules();
		$checks = $this->product_scan->failures( $product, $active['rules'], $active['channel_rules'] );
		return array(
			'product_id' => $product->get_id(),
			'checks'     => $checks,
			'statuses'   => ProductScan::statuses( array_values( $checks ) ),
		);
	}

	/**
	 * The checks that run after the rules filter, with their channel maps.
	 * Built once, so the feed path does not rebuild them per product.
	 *
	 * @return array{rules:array<string, ReadinessRule>,channel_rules:array<string, array<string, array{field:string,effect:string}>>}
	 */
	private function get_active_rules(): array {
		if ( null === $this->active_rules ) {
			$channel_rules      = $this->get_channel_rules();
			$this->active_rules = array(
				'rules'         => array_intersect_key( $this->get_default_rules(), $channel_rules ),
				'channel_rules' => $channel_rules,
			);
		}
		return $this->active_rules;
	}
}
