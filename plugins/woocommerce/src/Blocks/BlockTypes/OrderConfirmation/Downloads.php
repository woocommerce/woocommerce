<?php

namespace Automattic\WooCommerce\Blocks\BlockTypes\OrderConfirmation;

use Automattic\WooCommerce\Blocks\Utils\StyleAttributesUtils;

/**
 * Downloads class.
 */
class Downloads extends AbstractOrderConfirmationBlock {

	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'order-confirmation-downloads';

	/**
	 * This renders the content of the block within the wrapper.
	 *
	 * @param \WC_Order $order Order object.
	 * @param string    $permission Permission level for viewing order details.
	 * @param array     $attributes Block attributes.
	 * @param string    $content Original block content.
	 * @return string
	 */
	protected function render_content( $order, $permission = false, $attributes = [], $content = '' ) {
		$show_downloads = $order && $order->has_downloadable_item() && $order->is_download_permitted();
		$downloads      = $order ? $order->get_downloadable_items() : [];

		if ( ! $permission || ! $show_downloads ) {
			return $this->render_content_fallback();
		}

		$classes_and_styles = StyleAttributesUtils::get_classes_and_styles_by_attributes( $attributes, [ 'border_color', 'border_radius', 'border_width', 'border_style', 'background_color', 'text_color' ] );

		return '
			<table cellspacing="0" class="wc-block-order-confirmation-downloads__table ' . esc_attr( $classes_and_styles['classes'] ) . '" style="' . esc_attr( $classes_and_styles['styles'] ) . '">
				<thead>
					<tr>
						' . $this->render_order_downloads_column_headers( $order ) . '
					</td>
				</thead>
				<tbody>
					' . $this->render_order_downloads( $order, $downloads ) . '
				</tbody>
			</table>
		';
	}

	/**
	 * Enqueue frontend assets for this block, just in time for rendering.
	 *
	 * @param array $attributes  Any attributes that currently are available from the block.
	 * @return string
	 */
	protected function get_inline_styles( array $attributes ) {
		$link_classes_and_styles       = StyleAttributesUtils::get_link_color_class_and_style( $attributes );
		$link_hover_classes_and_styles = StyleAttributesUtils::get_link_hover_color_class_and_style( $attributes );
		$border_classes_and_styles     = StyleAttributesUtils::get_classes_and_styles_by_attributes( $attributes, [ 'border_color', 'border_radius', 'border_width', 'border_style' ] );

		return '
			.wc-block-order-confirmation-downloads__table a {' . $link_classes_and_styles['style'] . '}
			.wc-block-order-confirmation-downloads__table a:hover, .wc-block-order-confirmation-downloads__table a:focus {' . $link_hover_classes_and_styles['style'] . '}
			.wc-block-order-confirmation-downloads__table {' . $border_classes_and_styles['styles'] . '}
			.wc-block-order-confirmation-downloads__table th, .wc-block-order-confirmation-downloads__table td {' . $border_classes_and_styles['styles'] . '}
		';
	}

	/**
	 * Enqueue frontend assets for this block, just in time for rendering.
	 *
	 * @param array     $attributes  Any attributes that currently are available from the block.
	 * @param string    $content    The block content.
	 * @param \WP_Block $block    The block object.
	 */
	protected function enqueue_assets( array $attributes, $content, $block ) {
		parent::enqueue_assets( $attributes, $content, $block );

		$styles = $this->get_inline_styles( $attributes );

		wp_add_inline_style( 'wc-blocks-style', $styles );
	}

	/**
	 * Render column headers for downloads table.
	 *
	 * @return string
	 */
	protected function render_order_downloads_column_headers() {
		$columns = wc_get_account_downloads_columns();
		$return  = '';

		foreach ( $columns as $column_id => $column_name ) {
			$return .= '<th class="' . esc_attr( $column_id ) . '"><span class="nobr">' . esc_html( $column_name ) . '</span></th>';
		}

		return $return;
	}

	/**
	 * Render downloads.
	 *
	 * @param \WC_Order $order Order object.
	 * @param array     $downloads Array of downloads.
	 * @return string
	 */
	protected function render_order_downloads( $order, $downloads ) {
		$return = '';
		foreach ( $downloads as $download ) {
			$return .= '<tr>' . $this->render_order_download_row( $download ) . '</tr>';
		}
		return $return;
	}

	/**
	 * Render a download row in the table.
	 *
	 * @param array $download Download data.
	 * @return string
	 */
	protected function render_order_download_row( $download ) {
		$return = '';

		foreach ( wc_get_account_downloads_columns() as $column_id => $column_name ) {
			$return .= '<td class="' . esc_attr( $column_id ) . '" data-title="' . esc_attr( $column_name ) . '">';

			if ( has_action( 'woocommerce_account_downloads_column_' . $column_id ) ) {
				$return .= $this->get_hook_content( 'woocommerce_account_downloads_column_' . $column_id, [ $download ] );
			} else {
				switch ( $column_id ) {
					case 'download-product':
						if ( $download['product_url'] ) {
							$return .= '<a href="' . esc_url( $download['product_url'] ) . '">' . esc_html( $download['product_name'] ) . '</a>';
						} else {
							$return .= esc_html( $download['product_name'] );
						}
						break;
					case 'download-file':
						$return .= '<a href="' . esc_url( $download['download_url'] ) . '" class="woocommerce-MyAccount-downloads-file button alt">' . esc_html( $download['download_name'] ) . '</a>';
						break;
					case 'download-remaining':
						$return .= is_numeric( $download['downloads_remaining'] ) ? esc_html( $download['downloads_remaining'] ) : esc_html__( '&infin;', 'woocommerce' );
						break;
					case 'download-expires':
						if ( ! empty( $download['access_expires'] ) ) {
							$return .= '<time datetime="' . esc_attr( gmdate( 'Y-m-d', strtotime( $download['access_expires'] ) ) ) . '" title="' . esc_attr( strtotime( $download['access_expires'] ) ) . '">' . esc_html( date_i18n( get_option( 'date_format' ), strtotime( $download['access_expires'] ) ) ) . '</time>';
						} else {
							$return .= esc_html__( 'Never', 'woocommerce' );
						}
						break;
				}
			}

			$return .= '</td>';
		}

		return $return;
	}
}
