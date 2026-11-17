<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\AgentReadiness;

defined( 'ABSPATH' ) || exit;

/**
 * A readiness check: one product data problem tested against the product data
 * specs of AI shopping channels.
 *
 * Per channel a rule names the feed field it tests and whether the channel's
 * spec requires it (not_listable) or the product lists with a gap (gaps). A
 * channel whose spec does not ask for the field is left out.
 *
 * @since 11.4.0
 */
interface ReadinessRule {

	public const EFFECT_NOT_LISTABLE = 'not_listable';
	public const EFFECT_GAPS         = 'gaps';

	/**
	 * Stable id, used as the key in the report and the rules filter.
	 */
	public function id(): string;

	/**
	 * Merchant-facing label naming the problem.
	 */
	public function label(): string;

	/**
	 * Why it matters for shopping agents.
	 */
	public function why(): string;

	/**
	 * What the merchant should do about it.
	 */
	public function fix(): string;

	/**
	 * Default field and effect by FeedChannels id.
	 *
	 * @return array<string, array{field:string,effect:string}>
	 */
	public function channels(): array;

	/**
	 * Field and effect by channel for one product that fails the rule. Usually
	 * the given map; a rule may soften the effect for a product that still
	 * lists in part.
	 *
	 * @param \WC_Product                                      $product  Product that fails the rule.
	 * @param array<string, array{field:string,effect:string}> $channels The rule's channel map, after the rules filter.
	 * @return array<string, array{field:string,effect:string}>
	 */
	public function channels_for( \WC_Product $product, array $channels ): array;

	/**
	 * Whether the product has this problem.
	 *
	 * @param \WC_Product $product Published product.
	 * @param ProductScan $scan    Catalog-wide lookups.
	 */
	public function fails( \WC_Product $product, ProductScan $scan ): bool;
}
