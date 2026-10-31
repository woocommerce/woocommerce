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
	 * Rules from the published rules file.
	 *
	 * @var RemoteRules
	 */
	private RemoteRules $remote_rules;

	/**
	 * Scan reused for single-product checks, so its shared GTIN lookup runs once per request.
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
	 * @param StoreConditions   $store_conditions    Store-wide conditions.
	 * @param StoreOutputChecks $store_output_checks Store checks that read what the store returns.
	 * @param RemoteRules       $remote_rules        Rules from the published rules file.
	 */
	final public function init( StoreConditions $store_conditions, StoreOutputChecks $store_output_checks, RemoteRules $remote_rules ): void {
		$this->store_conditions    = $store_conditions;
		$this->store_output_checks = $store_output_checks;
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
	 * rules file, the file is the list: an id it leaves out does not run, and
	 * an id code does not know is dropped. With no file, the bundled checks
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
			$from_file = $this->without_empty( $this->sanitize_channel_rules( $this->remote_rules->channel_rules(), $bundled ) );
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
				if ( ! isset( $channels[ $channel ] ) || ! is_array( $spec ) || ! in_array( $spec['effect'] ?? null, $effects, true ) ) {
					continue;
				}
				$rules[ $id ][ $channel ] = array(
					'field'  => (string) ( $spec['field'] ?? '' ),
					'effect' => $spec['effect'],
				);
			}
		}
		return $rules;
	}

	/**
	 * Drop checks left with no valid channel.
	 *
	 * @param array<string, array<string, array{field:string,effect:string}>> $rules Channel map by check id.
	 * @return array<string, array<string, array{field:string,effect:string}>>
	 */
	private function without_empty( array $rules ): array {
		return array_filter(
			$rules,
			static function ( array $channels ): bool {
				return array() !== $channels;
			}
		);
	}

	/**
	 * Run every check over the catalog.
	 *
	 * @return array{generated_at:string,products:int,hidden_from_catalog:int,unsupported_types:int,store_checks:array<int,array<string,mixed>>,channels:array<string,array<string,mixed>>,checks:array<int,array<string,mixed>>,truncated:bool}
	 */
	public function get_report(): array {
		// Rebuild the rules and the scan for each report: a long-running process may have fetched a new rules file or changed GTINs since.
		$this->active_rules = null;
		$this->product_scan = null;
		$active             = $this->get_active_rules();
		$rules              = $active['rules'];
		$channel_rules      = $active['channel_rules'];

		$scan = new ProductScan();
		$scan->evaluate( $rules, $channel_rules, self::PRODUCT_IDS_LIMIT );

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

		return array(
			'generated_at'        => gmdate( 'c' ),
			'products'            => $scan->products(),
			'hidden_from_catalog' => $scan->hidden_from_catalog(),
			'unsupported_types'   => $scan->unsupported_types(),
			'store_checks'        => array_merge( $this->store_conditions->all(), $this->store_output_checks->all() ),
			'channels'            => $scan->channel_totals(),
			'checks'              => $checks,
			'truncated'           => $scan->truncated(),
		);
	}

	/**
	 * The last stored report, or null when there is none.
	 *
	 * @return array<string, mixed>|null
	 */
	public function get_cached_report(): ?array {
		$report = get_option( self::REPORT_OPTION );
		return is_array( $report ) ? $report : null;
	}

	/**
	 * Run the report and store it. Not autoloaded: only the readiness screens read it.
	 *
	 * @return array<string, mixed>
	 */
	public function refresh_report(): array {
		$report = $this->get_report();
		update_option( self::REPORT_OPTION, $report, false );
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
	 * are read once per report, so call get_report() again after changing GTINs.
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
