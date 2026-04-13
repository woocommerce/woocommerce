<?php

namespace Automattic\WooCommerce\Blocks\BlockTypes;

use Automattic\WooCommerce\Blocks\Utils\StyleAttributesUtils;
use Automattic\WooCommerce\Blocks\Utils\BlockHooksTrait;

/**
 * CustomerAccount class.
 */
class CustomerAccount extends AbstractBlock {
	use BlockHooksTrait;
	use EnableBlockJsonAssetsTrait;

	const TEXT_ONLY    = 'text_only';
	const ICON_ONLY    = 'icon_only';
	const DISPLAY_ALT  = 'alt';
	const DISPLAY_LINE = 'line';

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
			'version'  => '8.4.0',
		),
	);

	/**
	 * Initialize this block type.
	 */
	protected function initialize() {
		parent::initialize();
		/**
		 * The hooked_block_{$hooked_block_type} filter was added in WordPress 6.5.
		 * We are the only code adding the filter 'hooked_block_woocommerce/customer-account'.
		 * Using has_filter() for a compatibility check won't work because add_filter() is used in the same file.
		 */
		if ( version_compare( get_bloginfo( 'version' ), '6.5', '>=' ) ) {
			add_filter( 'hooked_block_woocommerce/customer-account', array( $this, 'modify_hooked_block_attributes' ), 10, 5 );
			add_filter( 'hooked_block_types', array( $this, 'register_hooked_block' ), 9, 4 );
		}
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
		$parsed_hooked_block['attrs']['iconStyle']    = 'line';
		$parsed_hooked_block['attrs']['iconClass']    = 'wc-block-customer-account__account-icon';

		$customer_account_block_font_size = wp_get_global_styles( array( 'blocks', 'woocommerce/customer-account', 'typography', 'fontSize' ) );

		if ( ! is_string( $customer_account_block_font_size ) ) {
			$navigation_block_font_size = wp_get_global_styles( array( 'blocks', 'core/navigation', 'typography', 'fontSize' ) );

			if ( is_string( $navigation_block_font_size ) ) {
				$parsed_hooked_block['attrs']['style']['typography']['fontSize'] = $navigation_block_font_size;
			}
		}

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
		$has_myaccount_page = get_option( 'woocommerce_myaccount_page_id' );
		$account_link       = $has_myaccount_page ? wc_get_account_endpoint_url( 'dashboard' ) : wp_login_url();
		$has_dropdown       = ! empty( $attributes['hasDropdownNavigation'] ) && is_user_logged_in() && $has_myaccount_page;

		$aria_label   = self::ICON_ONLY === $attributes['displayStyle'] ? ' aria-label="' . esc_attr( $this->render_label() ) . '"' : '';
		$label_markup = self::ICON_ONLY === $attributes['displayStyle'] ? '' : '<span class="label">' . wp_kses( $this->render_label(), array() ) . '</span>';

		if ( ! $has_dropdown ) {
			return $this->render_link( $attributes, $classes_and_styles, $account_link, $aria_label, $label_markup );
		}

		return $this->render_dropdown( $attributes, $classes_and_styles, $aria_label, $label_markup );
	}

	/**
	 * Render the block as a simple link (default behavior).
	 *
	 * @param array  $attributes        Block attributes.
	 * @param array  $classes_and_styles Classes and styles from block attributes.
	 * @param string $account_link      URL to link to.
	 * @param string $aria_label        Pre-computed aria-label attribute string.
	 * @param string $label_markup      Pre-computed label HTML markup.
	 *
	 * @return string Rendered block output.
	 */
	private function render_link( $attributes, $classes_and_styles, $account_link, $aria_label, $label_markup ) {
		$allowed_svg = $this->get_allowed_svg();

		ob_start();
		?>
		<div
			class="wp-block-woocommerce-customer-account <?php echo esc_attr( $classes_and_styles['classes'] ); ?>"
			style="<?php echo esc_attr( $classes_and_styles['styles'] ); ?>"
		>	
			<a
				class="wc-block-customer-account__link"
				href="<?php echo esc_url( $account_link ); ?>"
				<?php echo $aria_label; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			>
				<?php echo wp_kses( $this->render_icon( $attributes ), $allowed_svg ); ?>
				<?php echo $label_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</a>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Render the block as a dropdown navigation.
	 *
	 * @param array  $attributes        Block attributes.
	 * @param array  $classes_and_styles Classes and styles from block attributes.
	 * @param string $aria_label        Pre-computed aria-label attribute string.
	 * @param string $label_markup      Pre-computed label HTML markup.
	 *
	 * @return string Rendered block output.
	 */
	private function render_dropdown( $attributes, $classes_and_styles, $aria_label, $label_markup ) {
		$allowed_svg = $this->get_allowed_svg();

		$context = array(
			'isDropdownOpen' => false,
			'showAbove'      => false,
			'alignRight'     => false,
		);

		$menu_items    = wc_get_account_menu_items();
		$dropdown_html = $this->render_dropdown_menu( $menu_items );

		ob_start();
		?>
		<div
			class="wp-block-woocommerce-customer-account wc-block-customer-account--has-dropdown <?php echo esc_attr( $classes_and_styles['classes'] ); ?>"
			style="<?php echo esc_attr( $classes_and_styles['styles'] ); ?>"
			data-wp-interactive="woocommerce/customer-account/private"
			<?php echo wp_interactivity_data_wp_context( $context ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			data-wp-class--wc-block-customer-account--align-right="context.alignRight"
			data-wp-class--wc-block-customer-account--is-dropdown-open="context.isDropdownOpen"
			data-wp-class--wc-block-customer-account--show-above="context.showAbove"
			data-wp-on--focusout="actions.handleFocusOut"
			data-wp-on-document--click="actions.handleDocumentClick"
			data-wp-on-document--keydown="actions.handleKeydown"
		>
			<button
				type="button"
				class="wc-block-customer-account__toggle"
				<?php echo $aria_label; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				aria-haspopup="true"
				data-wp-bind--aria-expanded="context.isDropdownOpen"
				data-wp-on--click="actions.toggleDropdown"
			>
				<?php echo wp_kses( $this->render_icon( $attributes ), $allowed_svg ); ?>
				<?php echo $label_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<?php echo $this->render_caret_icon(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</button>
			<nav
				class="wc-block-customer-account__dropdown"
				aria-label="<?php echo esc_attr__( 'Account navigation', 'woocommerce' ); ?>"
				data-wp-bind--hidden="!context.isDropdownOpen"
			>
				<?php echo $dropdown_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</nav>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Render the dropdown menu content with three sections.
	 *
	 * @param array $menu_items Account menu items from wc_get_account_menu_items().
	 *
	 * @return string Rendered dropdown menu HTML.
	 */
	private function render_dropdown_menu( $menu_items ) {
		$sections = array();

		if ( isset( $menu_items['dashboard'] ) ) {
			$sections[] = $this->render_section( array( 'dashboard' => $menu_items['dashboard'] ) );
		}

		$nav_items = array_diff_key(
			$menu_items,
			array_flip( array( 'dashboard', 'customer-logout' ) )
		);
		if ( ! empty( $nav_items ) ) {
			$sections[] = $this->render_section( $nav_items );
		}

		if ( isset( $menu_items['customer-logout'] ) ) {
			$sections[] = $this->render_section( array( 'customer-logout' => $menu_items['customer-logout'] ) );
		}

		return implode( '<div class="wc-block-customer-account__dropdown-divider"></div>', $sections );
	}

	/**
	 * Render a dropdown section wrapping one or more menu items.
	 *
	 * @param array $items Associative array of endpoint => label pairs.
	 *
	 * @return string Rendered section HTML.
	 */
	private function render_section( $items ) {
		$output = '<div class="wc-block-customer-account__dropdown-section">';
		foreach ( $items as $endpoint => $label ) {
			$output .= $this->render_menu_item( $endpoint, $label );
		}
		$output .= '</div>';
		return $output;
	}

	/**
	 * Render a single dropdown menu item.
	 *
	 * @param string $endpoint The account endpoint key.
	 * @param string $label    The menu item label.
	 *
	 * @return string Rendered menu item HTML.
	 */
	private function render_menu_item( $endpoint, $label ) {
		$url = wc_get_account_endpoint_url( $endpoint );
		return '<a href="' . esc_url( $url ) . '" class="wc-block-customer-account__dropdown-item">'
			. esc_html( $label )
			. '</a>';
	}

	/**
	 * Render the caret/chevron icon for the dropdown toggle.
	 *
	 * @return string SVG markup for the caret icon.
	 */
	private function render_caret_icon() {
		return '<svg class="wc-block-customer-account__caret" width="10" height="6" viewBox="0 0 10 6" fill="none" xmlns="http://www.w3.org/2000/svg">
			<path d="M1 1L5 5L9 1" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
		</svg>';
	}

	/**
	 * Get the allowed SVG tags and attributes for wp_kses.
	 *
	 * @return array Allowed SVG elements and attributes.
	 */
	private function get_allowed_svg() {
		return array(
			'svg'    => array(
				'class'   => true,
				'xmlns'   => true,
				'width'   => true,
				'height'  => true,
				'viewbox' => true,
			),
			'path'   => array(
				'd'         => true,
				'fill'      => true,
				'fill-rule' => true,
				'clip-rule' => true,
			),
			'circle' => array(
				'cx'           => true,
				'cy'           => true,
				'r'            => true,
				'stroke'       => true,
				'stroke-width' => true,
				'fill'         => true,
			),
		);
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

		if ( self::DISPLAY_LINE === $attributes['iconStyle'] ) {
			return '<svg class="' . $attributes['iconClass'] . '" viewBox="1 1 29 29" fill="none" xmlns="http://www.w3.org/2000/svg">
				<circle
					cx="16"
					cy="10.5"
					r="3.5"
					stroke="currentColor"
					stroke-width="2"
					fill="none"
				/>
				<path
					fill-rule="evenodd"
					clip-rule="evenodd"
					d="M11.5 18.5H20.5C21.8807 18.5 23 19.6193 23 21V25.5H25V21C25 18.5147 22.9853 16.5 20.5 16.5H11.5C9.01472 16.5 7 18.5147 7 21V25.5H9V21C9 19.6193 10.1193 18.5 11.5 18.5Z"
					fill="currentColor"
				/>
			</svg>';
		}

		if ( self::DISPLAY_ALT === $attributes['iconStyle'] ) {
			return '<svg class="' . $attributes['iconClass'] . '" xmlns="http://www.w3.org/2000/svg" viewBox="-4 -4 25 25">
				<path
					d="M9 0C4.03579 0 0 4.03579 0 9C0 13.9642 4.03579 18 9 18C13.9642 18 18 13.9642 18 9C18 4.03579 13.9642 0 9 0ZM9 4.32C10.5347 4.32 11.7664 5.57056 11.7664 7.08638C11.7664 8.62109 10.5158 9.85277 9 9.85277C7.4653 9.85277 6.23362 8.60221 6.23362 7.08638C6.23362 5.57056 7.46526 4.32 9 4.32ZM9 10.7242C11.1221 10.7242 12.96 12.2021 13.7937 14.4189C12.5242 15.5559 10.8379 16.238 9 16.238C7.16207 16.238 5.49474 15.5369 4.20632 14.4189C5.05891 12.2021 6.87793 10.7242 9 10.7242Z"
					fill="currentColor"
				/>
			</svg>';
		}

		return '<svg class="' . $attributes['iconClass'] . '" xmlns="http://www.w3.org/2000/svg" viewBox="-5 -5 25 25">
			<path
				fill-rule="evenodd"
				clip-rule="evenodd"
				d="M8.00009 8.34785C10.3096 8.34785 12.1819 6.47909 12.1819 4.17393C12.1819 1.86876 10.3096 0 8.00009 0C5.69055 0 3.81824 1.86876 3.81824 4.17393C3.81824 6.47909 5.69055 8.34785 8.00009 8.34785ZM0.333496 15.6522C0.333496 15.8444 0.489412 16 0.681933 16H15.3184C15.5109 16 15.6668 15.8444 15.6668 15.6522V14.9565C15.6668 12.1428 13.7821 9.73911 10.0912 9.73911H5.90931C2.21828 9.73911 0.333645 12.1428 0.333645 14.9565L0.333496 15.6522Z"
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
}
