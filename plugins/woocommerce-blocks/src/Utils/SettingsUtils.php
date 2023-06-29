<?php
namespace Automattic\WooCommerce\Blocks\Utils;

use WC_Admin_Settings;

/**
 * WooSettingsUtils class
 */
class SettingsUtils {

	/**
	 * Input field for permalink settings/pages.
	 *
	 * @param array $value Input value.
	 */
	public static function permalink_input_field( $value ) {
		$field_description = WC_Admin_Settings::get_field_description( $value );
		$description       = $field_description['description'];
		$tooltip_html      = $field_description['tooltip_html'];
		?>
		<tr valign="top">
			<th scope="row" class="titledesc">
				<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?> <?php echo wp_kses_post( $tooltip_html ); ?></label>
			</th>
			<td class="forminp forminp-text">
				<span class="code" style="width: 400px; display:flex; align-items:center; gap:5px;">
					<code class="permalink-custom" style="vertical-align: middle;">
						<?php echo esc_html( get_site_url( null, '/' ) ); ?>
					</code>
					<input
						name="<?php echo esc_attr( $value['field_name'] ); ?>"
						id="<?php echo esc_attr( $value['id'] ); ?>"
						type="text"
						required
						style="vertical-align: middle;"
						value="<?php echo esc_attr( $value['value'] ); ?>"
						class="<?php echo esc_attr( $value['class'] ); ?>"
						placeholder="<?php echo esc_attr( $value['placeholder'] ); ?>"
						/><?php echo esc_html( $value['suffix'] ); ?>
				</span>
				<?php echo wp_kses_post( $description ); ?>
			</td>
		</tr>
		<?php
	}
}
