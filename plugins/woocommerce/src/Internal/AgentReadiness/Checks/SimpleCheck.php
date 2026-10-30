<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\AgentReadiness\Checks;

use Automattic\WooCommerce\Internal\AgentReadiness\FeedChannels;
use Automattic\WooCommerce\Internal\AgentReadiness\ProductFields;
use Automattic\WooCommerce\Internal\AgentReadiness\ProductScan;

defined( 'ABSPATH' ) || exit;

/**
 * A check that is only data: its texts, channel map and a test of one product.
 * table() lists them; checks that change per product have their own class.
 *
 * @since 11.4.0
 */
final class SimpleCheck extends AbstractReadinessCheck {

	public const TITLE_MAX_LENGTH = 150;

	/**
	 * Check id.
	 *
	 * @var string
	 */
	private string $id;

	/**
	 * Merchant-facing label, why it matters, and what to do.
	 *
	 * @var array{label:string,why:string,fix:string}
	 */
	private array $texts;

	/**
	 * Field and effect by channel.
	 *
	 * @var array<string, array{field:string,effect:string}>
	 */
	private array $channels;

	/**
	 * Whether a product has the problem.
	 *
	 * @var \Closure(\WC_Product):bool
	 */
	private \Closure $test;

	/**
	 * Build a check.
	 *
	 * @param string                                           $id       Check id.
	 * @param array{label:string,why:string,fix:string}        $texts    Merchant-facing texts.
	 * @param array<string, array{field:string,effect:string}> $channels Field and effect by channel.
	 * @param \Closure                                         $test     Whether a product has the problem: fn( \WC_Product ): bool.
	 */
	public function __construct( string $id, array $texts, array $channels, \Closure $test ) {
		$this->id       = $id;
		$this->texts    = $texts;
		$this->channels = $channels;
		$this->test     = $test;
	}

	/**
	 * The data-only checks, by id.
	 *
	 * @return array<string, self>
	 */
	public static function table(): array {
		$checks = array(
			new self(
				'missing_images',
				array(
					'label' => __( 'Products with no main image', 'woocommerce' ),
					'why'   => __( 'Every channel\'s spec requires an image link, so the product can\'t be listed without one.', 'woocommerce' ),
					'fix'   => __( 'Add a main product image.', 'woocommerce' ),
				),
				self::fields( array( 'image_url', 'image_link', 'imageLink' ), self::EFFECT_NOT_LISTABLE ),
				static fn( \WC_Product $product ): bool => 0 === ProductFields::image_id( $product )
			),
			new self(
				'missing_product_text',
				array(
					'label' => __( 'Products with no description', 'woocommerce' ),
					'why'   => __( 'ChatGPT\'s spec requires a description, so the product can\'t be listed there without one. Google and Copilot list it with a warning.', 'woocommerce' ),
					'fix'   => __( 'Write a description that says what the product is and who it is for.', 'woocommerce' ),
				),
				self::fields( array( 'description', 'description', 'description' ), self::EFFECT_GAPS, FeedChannels::CHATGPT ),
				static fn( \WC_Product $product ): bool => '' === ProductFields::text( $product )
			),
			new self(
				'invalid_gtin',
				array(
					'label' => __( 'Products with an invalid GTIN', 'woocommerce' ),
					'why'   => __( 'Google\'s spec requires a submitted GTIN to be valid, so the product can\'t be listed there with a wrong one.', 'woocommerce' ),
					'fix'   => __( 'Correct the GTIN; it must be 8, 12, 13 or 14 digits with a valid check digit. Convert an ISBN-10 to its ISBN-13.', 'woocommerce' ),
				),
				self::fields( array( 'gtin', 'gtin', 'gtin' ), self::EFFECT_GAPS, FeedChannels::GOOGLE ),
				static fn( \WC_Product $product ): bool => ProductFields::needs_identifiers( $product )
					&& in_array( false, array_map( array( ProductFields::class, 'is_valid_gtin' ), ProductFields::gtins( $product ) ), true )
			),
			new self(
				'uncategorised',
				array(
					'label' => __( 'Products with only the default category', 'woocommerce' ),
					'why'   => __( 'Agents narrow searches by category first; an uncategorised product is harder to find.', 'woocommerce' ),
					'fix'   => __( 'Assign a category.', 'woocommerce' ),
				),
				self::fields( array( 'product_category', 'product_type', 'productType' ), self::EFFECT_GAPS ),
				static fn( \WC_Product $product ): bool => empty( array_diff( array_map( 'intval', $product->get_category_ids() ), array( (int) get_option( 'default_product_cat', 0 ) ) ) )
			),
			new self(
				'variation_attributes_not_set',
				array(
					'label' => __( 'Variable products with variations not tied to an option', 'woocommerce' ),
					'why'   => __( 'A variation set to "any" can\'t be listed as a distinct item with its own color or size.', 'woocommerce' ),
					'fix'   => __( 'Set the options on each variation.', 'woocommerce' ),
				),
				self::fields( array( 'variant_dict', 'color, size', 'color, sizes' ), self::EFFECT_GAPS ),
				\Closure::fromCallable( array( self::class, 'has_unset_variation_option' ) )
			),
			new self(
				'missing_variation_images',
				array(
					'label' => __( 'Variable products with variations missing images', 'woocommerce' ),
					'why'   => __( 'Each variation lists as its own item; without its own image it shows the parent image, which may not match the color or finish.', 'woocommerce' ),
					'fix'   => __( 'Add an image to each variation.', 'woocommerce' ),
				),
				self::fields( array( 'image_url', 'image_link', 'imageLink' ), self::EFFECT_GAPS ),
				static function ( \WC_Product $product ): bool {
					foreach ( ProductFields::variations( $product ) as $variation ) {
						if ( (int) $variation->get_image_id( 'edit' ) <= 0 ) {
							return true;
						}
					}
					return false;
				}
			),
			new self(
				'title_too_long',
				array(
					'label' => __( 'Products with titles over 150 characters', 'woocommerce' ),
					'why'   => __( 'Google truncates titles over 150 characters; ChatGPT and Copilot cap titles at 150.', 'woocommerce' ),
					'fix'   => __( 'Shorten the title, keeping the brand and product type first.', 'woocommerce' ),
				),
				self::on_every_channel( 'title', self::EFFECT_GAPS ),
				static fn( \WC_Product $product ): bool => mb_strlen( $product->get_name() ) > self::TITLE_MAX_LENGTH
			),
			new self(
				'image_alt_text',
				array(
					'label' => __( 'Products whose main image has no alt text', 'woocommerce' ),
					'why'   => __( 'AI agents and screen readers read alt text to tell what an image shows. No channel\'s feed asks for it, so it doesn\'t change the channel counts.', 'woocommerce' ),
					'fix'   => __( 'Describe the main image in its Alternative text field in the media library.', 'woocommerce' ),
				),
				array(),
				static function ( \WC_Product $product ): bool {
					$image_id = ProductFields::image_id( $product );
					return $image_id > 0 && '' === trim( (string) get_post_meta( $image_id, '_wp_attachment_image_alt', true ) );
				}
			),
		);
		$by_id  = array();
		foreach ( $checks as $check ) {
			$by_id[ $check->id() ] = $check;
		}
		return $by_id;
	}

	/**
	 * Feed fields for ChatGPT, Google and Copilot, in that order, with one effect
	 * and optionally one channel where the product can't be listed.
	 *
	 * @param array<int, string> $fields          Field per channel.
	 * @param string             $effect          EFFECT_NOT_LISTABLE or EFFECT_GAPS.
	 * @param string             $not_listable_on Channel id whose spec requires the field, or ''.
	 * @return array<string, array{field:string,effect:string}>
	 */
	private static function fields( array $fields, string $effect, string $not_listable_on = '' ): array {
		$channels = array();
		foreach ( array( FeedChannels::CHATGPT, FeedChannels::GOOGLE, FeedChannels::COPILOT ) as $i => $channel ) {
			$channels[ $channel ] = array(
				'field'  => $fields[ $i ],
				'effect' => $channel === $not_listable_on ? self::EFFECT_NOT_LISTABLE : $effect,
			);
		}
		return $channels;
	}

	/**
	 * Whether a variable product has no variation attribute, or a variation set to "any".
	 *
	 * @param \WC_Product $product Published product.
	 */
	private static function has_unset_variation_option( \WC_Product $product ): bool {
		if ( ! $product instanceof \WC_Product_Variable ) {
			return false;
		}
		$variation_attributes = array_filter(
			$product->get_attributes(),
			static fn( $attribute ): bool => $attribute instanceof \WC_Product_Attribute && $attribute->get_variation()
		);
		if ( empty( $variation_attributes ) ) {
			return true;
		}
		foreach ( ProductFields::variations( $product ) as $variation ) {
			if ( in_array( '', array_map( 'strval', $variation->get_attributes() ), true ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Check id.
	 */
	public function id(): string {
		return $this->id;
	}

	/**
	 * Merchant-facing label.
	 */
	public function label(): string {
		return $this->texts['label'];
	}

	/**
	 * Why it matters.
	 */
	public function why(): string {
		return $this->texts['why'];
	}

	/**
	 * What to do about it.
	 */
	public function fix(): string {
		return $this->texts['fix'];
	}

	/**
	 * Field and effect by channel.
	 */
	public function channels(): array {
		return $this->channels;
	}

	/**
	 * Whether the product has this problem.
	 *
	 * @param \WC_Product $product Published product.
	 * @param ProductScan $scan    Catalog-wide lookups.
	 */
	public function fails( \WC_Product $product, ProductScan $scan ): bool {
		return ( $this->test )( $product );
	}
}
