<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\AgentReadiness;

use Automattic\WooCommerce\Internal\AgentReadiness\Checks\DuplicateGtinCheck;
use Automattic\WooCommerce\Internal\AgentReadiness\Checks\HiddenFromCatalogCheck;
use Automattic\WooCommerce\Internal\AgentReadiness\Checks\ImageTooSmallCheck;
use Automattic\WooCommerce\Internal\AgentReadiness\Checks\InvalidGtinCheck;
use Automattic\WooCommerce\Internal\AgentReadiness\Checks\MissingBrandCheck;
use Automattic\WooCommerce\Internal\AgentReadiness\Checks\MissingGtinCheck;
use Automattic\WooCommerce\Internal\AgentReadiness\Checks\MissingImagesCheck;
use Automattic\WooCommerce\Internal\AgentReadiness\Checks\MissingProductTextCheck;
use Automattic\WooCommerce\Internal\AgentReadiness\Checks\MissingRegularPriceCheck;
use Automattic\WooCommerce\Internal\AgentReadiness\Checks\MissingVariationImagesCheck;
use Automattic\WooCommerce\Internal\AgentReadiness\Checks\TitleTooLongCheck;
use Automattic\WooCommerce\Internal\AgentReadiness\Checks\UncategorisedCheck;
use Automattic\WooCommerce\Internal\AgentReadiness\Checks\VariationAttributesNotSetCheck;

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
	 * Transient holding the last report.
	 */
	public const REPORT_TRANSIENT = 'woocommerce_agent_readiness_report';

	/**
	 * Store-wide conditions.
	 *
	 * @var StoreConditions
	 */
	private StoreConditions $store_conditions;

	/**
	 * Scan reused for single-product checks, so its shared GTIN lookup runs once per request.
	 *
	 * @var ProductScan|null
	 */
	private ?ProductScan $product_scan = null;

	/**
	 * Initialize dependencies.
	 *
	 * @internal
	 *
	 * @param StoreConditions $store_conditions Store-wide conditions.
	 */
	final public function init( StoreConditions $store_conditions ): void {
		$this->store_conditions = $store_conditions;
	}

	/**
	 * The bundled checks, keyed by id.
	 *
	 * @return array<string, ReadinessRule>
	 */
	public function get_default_rules(): array {
		$rules = array();
		foreach (
			array(
				new MissingRegularPriceCheck(),
				new MissingImagesCheck(),
				new MissingProductTextCheck(),
				new MissingBrandCheck(),
				new HiddenFromCatalogCheck(),
				new InvalidGtinCheck(),
				new MissingGtinCheck(),
				new DuplicateGtinCheck(),
				new ImageTooSmallCheck(),
				new UncategorisedCheck(),
				new VariationAttributesNotSetCheck(),
				new MissingVariationImagesCheck(),
				new TitleTooLongCheck(),
			) as $rule
		) {
			$rules[ $rule->id() ] = $rule;
		}
		return $rules;
	}

	/**
	 * Field and effect per channel for each check, after the
	 * woocommerce_agent_readiness_rules filter. A check whose id is missing
	 * from the filtered array does not run.
	 *
	 * @return array<string, array<string, array{field:string,effect:string}>>
	 */
	public function get_channel_rules(): array {
		$defaults = array();
		foreach ( $this->get_default_rules() as $id => $rule ) {
			$defaults[ $id ] = $rule->channels();
		}

		/**
		 * Filters the per-channel field and effect of each agent readiness check.
		 *
		 * Keys are check ids; values map a channel id (chatgpt, google, copilot)
		 * to the feed field the check tests there and its effect: not_listable
		 * when the channel's spec requires the field, gaps when the product
		 * lists with a gap. Remove a check id to stop that check running.
		 *
		 * @since 11.4.0
		 *
		 * @param array $rules Field and effect by channel, keyed by check id.
		 */
		$filtered = apply_filters( 'woocommerce_agent_readiness_rules', $defaults );

		return is_array( $filtered ) ? $this->sanitize_channel_rules( $filtered, $defaults ) : $defaults;
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
	 * Run every check over the catalog.
	 *
	 * @return array{generated_at:string,products:int,store_checks:array<int,array<string,mixed>>,channels:array<string,array<string,mixed>>,checks:array<int,array<string,mixed>>,truncated:bool}
	 */
	public function get_report(): array {
		$channel_rules = $this->get_channel_rules();
		$rules         = array_intersect_key( $this->get_default_rules(), $channel_rules );

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
			'generated_at' => gmdate( 'c' ),
			'products'     => $scan->products(),
			'store_checks' => $this->store_conditions->all(),
			'channels'     => $scan->channel_totals(),
			'checks'       => $checks,
			'truncated'    => $scan->truncated(),
		);
	}

	/**
	 * The last stored report, or null when there is none.
	 *
	 * @return array<string, mixed>|null
	 */
	public function get_cached_report(): ?array {
		$report = get_transient( self::REPORT_TRANSIENT );
		return is_array( $report ) ? $report : null;
	}

	/**
	 * Run the report and store it for an hour.
	 *
	 * @return array<string, mixed>
	 */
	public function refresh_report(): array {
		$report = $this->get_report();
		set_transient( self::REPORT_TRANSIENT, $report, HOUR_IN_SECONDS );
		return $report;
	}

	/**
	 * Ids of the checks one product fails.
	 *
	 * @param \WC_Product $product Product.
	 * @return array<int, string>
	 */
	public function get_failing_check_ids( \WC_Product $product ): array {
		if ( null === $this->product_scan ) {
			$this->product_scan = new ProductScan();
		}
		$rules = array_intersect_key( $this->get_default_rules(), $this->get_channel_rules() );
		return $this->product_scan->failed_rule_ids( $product, $rules );
	}
}
