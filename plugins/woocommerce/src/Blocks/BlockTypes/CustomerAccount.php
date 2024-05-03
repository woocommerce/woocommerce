<?php

namespace Automattic\WooCommerce\Blocks\BlockTypes;

use Automattic\WooCommerce\Blocks\Utils\StyleAttributesUtils;
use Automattic\WooCommerce\Blocks\Utils\BlockHooksTrait;

/**
 * CustomerAccount class.
 */
class CustomerAccount extends AbstractBlock {
	use BlockHooksTrait;

	const TEXT_ONLY   = 'text_only';
	const ICON_ONLY   = 'icon_only';
	const DISPLAY_ALT = 'alt';

	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'customer-account';

	/**
	 * Block Hook API placements.
	 *
	 * @var array
	 */
	protected $hooked_block_placements = array(
		array(
			'position' => 'after',
			'anchor'   => 'core/navigation',
			'area'     => 'header',
			'callback' => 'should_unhook_block',
		),
	);

	/**
	 * Initialize this block type.
	 */
	protected function initialize() {
		parent::initialize();
		add_filter( 'hooked_block_types', array( $this, 'register_hooked_block' ), 9, 4 );
		add_filter( 'hooked_block_woocommerce/customer-account', array( $this, 'modify_hooked_block_attributes' ), 10, 5 );
	}

	/**
	 * Callback for the Block Hooks API to modify the attributes of the hooked block.
	 *
	 * @param array|null                      $parsed_hooked_block The parsed block array for the given hooked block type, or null to suppress the block.
	 * @param string                          $hooked_block_type   The hooked block type name.
	 * @param string                          $relative_position   The relative position of the hooked block.
	 * @param array                           $parsed_anchor_block The anchor block, in parsed block array format.
	 * @param WP_Block_Template|WP_Post|array $context             The block template, template part, `wp_navigation` post type,
	 *                                                             or pattern that the anchor block belongs to.
	 * @return array|null
	 */
	public function modify_hooked_block_attributes( $parsed_hooked_block, $hooked_block_type, $relative_position, $parsed_anchor_block, $context ) {
		$parsed_hooked_block['attrs']['displayStyle'] = 'icon_only';

		/*
		* The Mini Cart block (which is hooked into the header) has a margin of 0.5em on the left side.
		* We want to match that margin for the Customer Account block so it looks consistent.
		*/
		$parsed_hooked_block['attrs']['style']['spacing']['margin']['left'] = '0.5em';
		return $parsed_hooked_block;
	}

	/**
	 * Callback for the Block Hooks API to determine if the block should be auto-inserted.
	 *
	 * @param array                             $hooked_blocks An array of block slugs hooked into a given context.
	 * @param string                            $position      Position of the block insertion point.
	 * @param string                            $anchor_block  The block acting as the anchor for the inserted block.
	 * @param array|\WP_Post|\WP_Block_Template $context       Where the block is embedded.
	 *
	 * @return array
	 */
	protected function should_unhook_block( $hooked_blocks, $position, $anchor_block, $context ) {
		$block_name      = $this->namespace . '/' . $this->block_name;
		$block_is_hooked = in_array( $block_name, $hooked_blocks, true );

		if ( $block_is_hooked ) {
			$active_theme   = wp_get_theme()->get( 'Name' );
			$exclude_themes = array( 'Twenty Twenty-Two', 'Twenty Twenty-Three' );

			if ( in_array( $active_theme, $exclude_themes, true ) ) {
				$key = array_search( $block_name, $hooked_blocks, true );
				unset( $hooked_blocks[ $key ] );
			}
		}

		return $hooked_blocks;
	}

	/**
	 * Render the block.
	 *
	 * @param array    $attributes Block attributes.
	 * @param string   $content Block content.
	 * @param WP_Block $block Block instance.
	 *
	 * @return string Rendered block output.
	 */
	protected function render( $attributes, $content, $block ) {
		$classes_and_styles = StyleAttributesUtils::get_classes_and_styles_by_attributes( $attributes );

		$account_link = get_option( 'woocommerce_myaccount_page_id' ) ? wc_get_account_endpoint_url( 'dashboard' ) : wp_login_url();

		$allowed_svg = array(
			'svg'  => array(
				'class'   => true,
				'xmlns'   => true,
				'width'   => true,
				'height'  => true,
				'viewbox' => true,
			),
			'path' => array(
				'd'    => true,
				'fill' => true,
			),
		);

		// Only provide aria-label if the display style is icon only.
		$aria_label = self::ICON_ONLY === $attributes['displayStyle'] ? 'aria-label="' . esc_attr( $this->render_label() ) . '"' : '';

		$label_markup = self::ICON_ONLY === $attributes['displayStyle'] ? '' : '<span class="label">' . wp_kses( $this->render_label(), array() ) . '</span>';

		return "<div class='wp-block-woocommerce-customer-account " . esc_attr( $classes_and_styles['classes'] ) . "' style='" . esc_attr( $classes_and_styles['styles'] ) . "'>
			<a " . $aria_label . " href='" . esc_attr( $account_link ) . "'>
				" . wp_kses( $this->render_icon( $attributes ), $allowed_svg ) . $label_markup . '
			</a>
		</div>';
	}

	/**
	 * Gets the icon to render depending on the iconStyle and displayStyle.
	 *
	 * @param array $attributes Block attributes.
	 *
	 * @return string Label to render on the block
	 */
	private function render_icon( $attributes ) {
		if ( self::TEXT_ONLY === $attributes['displayStyle'] ) {
			return '';
		}

		if ( self::DISPLAY_ALT === $attributes['iconStyle'] ) {
			return '<svg class="' . $attributes['iconClass'] . '" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 18 18" width="18" height="18">
				<path
					d="M9 0C4.03579 0 0 4.03579 0 9C0 13.9642 4.03579 18 9 18C13.9642 18 18 13.9642 18 9C18 4.03579 13.9642 0 9
					 	0ZM9 4.32C10.5347 4.32 11.7664 5.57056 11.7664 7.08638C11.7664 8.62109 10.5158 9.85277 9 9.85277C7.4653
					 	9.85277 6.23362 8.60221 6.23362 7.08638C6.23362 5.57056 7.46526 4.32 9 4.32ZM9 10.7242C11.1221 10.7242
					  	12.96 12.2021 13.7937 14.4189C12.5242 15.5559 10.8379 16.238 9 16.238C7.16207 16.238 5.49474 15.5369
					   	4.20632 14.4189C5.05891 12.2021 6.87793 10.7242 9 10.7242Z"
					fill="currentColor"
				/>
			</svg>';
		}

		return '<svg class="' . $attributes['iconClass'] . '" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" width="16" height="16">
			<path
				d="M8.00009 8.34785C10.3096 8.34785 12.1819 6.47909 12.1819 4.17393C12.1819 1.86876 10.3096 0 8.00009 0C5.69055
				 	0 3.81824 1.86876 3.81824 4.17393C3.81824 6.47909 5.69055 8.34785 8.00009 8.34785ZM0.333496 15.6522C0.333496
				  	15.8444 0.489412 16 0.681933 16H15.3184C15.5109 16 15.6668 15.8444 15.6668 15.6522V14.9565C15.6668 12.1428
				   	13.7821 9.73911 10.0912 9.73911H5.90931C2.21828 9.73911 0.333645 12.1428 0.333645 14.9565L0.333496 15.6522Z"
				fill="currentColor"
			/>
		</svg>';
	}

	/**
	 * Gets the label to render depending on the displayStyle.
	 *
	 * @return string Label to render on the block.
	 */
	private function render_label() {
		return get_current_user_id()
			? __( 'My Account', 'woocommerce' )
			: __( 'Login', 'woocommerce' );
	}

	/**
	 * Get the frontend script handle for this block type.
	 *
	 * @param string $key Data to get, or default to everything.
	 *
	 * @return null This block has no frontend script.
	 */
	protected function get_block_type_script( $key = null ) {
		return null;
	}
}
