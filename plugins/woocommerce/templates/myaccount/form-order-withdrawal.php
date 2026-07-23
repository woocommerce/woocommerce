<?php
/**
 * Order withdrawal form
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/myaccount/form-order-withdrawal.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 11.1.0
 */

defined( 'ABSPATH' ) || exit;

$screen                  = isset( $screen ) ? (string) $screen : 'form';
$data                    = isset( $data ) && is_array( $data ) ? $data : array();
$form_errors             = isset( $errors ) && is_array( $errors ) ? $errors : array();
$fields                  = isset( $fields ) && is_array( $fields ) ? $fields : array();
$withdrawal_type_options = isset( $withdrawal_type_options ) && is_array( $withdrawal_type_options ) ? $withdrawal_type_options : array();
$hidden_fields           = isset( $hidden_fields ) && is_array( $hidden_fields ) ? $hidden_fields : array();
$review_rows             = isset( $review_rows ) && is_array( $review_rows ) ? $review_rows : array();
$nonce_action            = isset( $nonce_action ) ? (string) $nonce_action : '';
$nonce_field             = isset( $nonce_field ) ? (string) $nonce_field : '';
$action_field            = isset( $action_field ) ? (string) $action_field : '';
$action_review           = isset( $action_review ) ? (string) $action_review : '';
$action_confirm          = isset( $action_confirm ) ? (string) $action_confirm : '';
$action_edit             = isset( $action_edit ) ? (string) $action_edit : '';
$form_action_url         = isset( $form_action_url ) ? (string) $form_action_url : '';
$shop_url                = isset( $shop_url ) ? (string) $shop_url : wc_get_page_permalink( 'shop' );
$form_field_keys         = array( 'first_name', 'last_name', 'email', 'email_confirmation', 'order_number' );
$withdrawal_type_field   = $fields['withdrawal_type'] ?? array();
$additional_details      = $fields['additional_details'] ?? array();
$button_classes          = array( 'woocommerce-Button', 'button' );
$theme_button_class      = wc_wp_theme_get_element_class_name( 'button' );

if ( $theme_button_class ) {
	$button_classes[] = $theme_button_class;
}

$button_class           = implode( ' ', $button_classes );
$secondary_button_class = implode( ' ', array_merge( $button_classes, array( 'woocommerce-order-withdrawal-content__button--secondary' ) ) );
?>

<div class="woocommerce-order-withdrawal-content">
	<?php wc_print_notices(); ?>

	<?php if ( 'confirmation' === $screen ) : ?>
		<p><strong><?php esc_html_e( 'Your withdrawal has been submitted.', 'woocommerce' ); ?></strong></p>
		<p class="woocommerce-order-withdrawal-content__note">
			<?php
			printf(
				/* translators: %s: Email address. */
				esc_html__( 'We\'ve emailed an acknowledgment to %s with your details and the date and time of submission. Keep it as proof of your withdrawal.', 'woocommerce' ),
				esc_html( $data['email'] ?? '' )
			);
			?>
		</p>
		<p class="woocommerce-order-withdrawal-content__note"><?php esc_html_e( 'We\'ll review your request and contact you about next steps, including any refund due.', 'woocommerce' ); ?></p>
		<p><a href="<?php echo esc_url( $shop_url ); ?>"><?php esc_html_e( 'Back to the shop', 'woocommerce' ); ?></a></p>
	<?php elseif ( 'review' === $screen ) : ?>
		<p class="woocommerce-order-withdrawal-content__intro"><?php esc_html_e( 'Check your details before confirming.', 'woocommerce' ); ?></p>
		<p class="woocommerce-order-withdrawal-content__note"><?php esc_html_e( 'Nothing has been sent yet. Your withdrawal is submitted when you select "Confirm withdrawal".', 'woocommerce' ); ?></p>

		<ol class="woocommerce-order-withdrawal-content__steps" aria-label="<?php esc_attr_e( 'Order withdrawal progress', 'woocommerce' ); ?>">
			<li class="woocommerce-order-withdrawal-content__step"><?php esc_html_e( 'Your details', 'woocommerce' ); ?></li>
			<li class="woocommerce-order-withdrawal-content__step woocommerce-order-withdrawal-content__step--current" aria-current="step"><?php esc_html_e( 'Review and confirm', 'woocommerce' ); ?></li>
		</ol>

		<dl class="woocommerce-order-withdrawal-content__review">
			<?php foreach ( $review_rows as $review_row ) : ?>
				<div class="woocommerce-order-withdrawal-content__review-row">
					<dt><?php echo esc_html( $review_row['label'] ?? '' ); ?></dt>
					<dd><?php echo nl2br( esc_html( $review_row['value'] ?? '' ) ); ?></dd>
				</div>
			<?php endforeach; ?>
		</dl>

		<p class="woocommerce-order-withdrawal-content__note">
			<?php
			printf(
				/* translators: %s: Email address. */
				esc_html__( 'After you confirm, we\'ll email an acknowledgment to %s with these details and the date and time of submission. Keep it as proof of your withdrawal.', 'woocommerce' ),
				esc_html( $data['email'] ?? '' )
			);
			?>
		</p>

		<form class="woocommerce-OrderWithdrawalForm" action="<?php echo esc_url( $form_action_url ); ?>" method="post" novalidate>
			<?php foreach ( $hidden_fields as $hidden_field ) : ?>
				<input type="hidden" name="<?php echo esc_attr( $hidden_field['name'] ?? '' ); ?>" value="<?php echo esc_attr( $hidden_field['value'] ?? '' ); ?>" />
			<?php endforeach; ?>

			<p class="woocommerce-order-withdrawal-content__actions">
				<?php wp_nonce_field( $nonce_action, $nonce_field ); ?>
				<button type="submit" class="<?php echo esc_attr( $button_class ); ?>" name="<?php echo esc_attr( $action_field ); ?>" value="<?php echo esc_attr( $action_confirm ); ?>"><?php esc_html_e( 'Confirm withdrawal', 'woocommerce' ); ?></button>
				<button type="submit" class="<?php echo esc_attr( $secondary_button_class ); ?>" name="<?php echo esc_attr( $action_field ); ?>" value="<?php echo esc_attr( $action_edit ); ?>"><?php esc_html_e( 'Edit details', 'woocommerce' ); ?></button>
			</p>
		</form>
	<?php else : ?>
		<p class="woocommerce-order-withdrawal-content__intro"><?php esc_html_e( 'Tell us you want to withdraw from an order placed on this store. You do not need to give a reason.', 'woocommerce' ); ?></p>
		<p class="woocommerce-order-withdrawal-content__note"><?php esc_html_e( 'Some items, like personalized products, may not be eligible. We review every request and reply by email.', 'woocommerce' ); ?></p>

		<ol class="woocommerce-order-withdrawal-content__steps" aria-label="<?php esc_attr_e( 'Order withdrawal progress', 'woocommerce' ); ?>">
			<li class="woocommerce-order-withdrawal-content__step woocommerce-order-withdrawal-content__step--current" aria-current="step"><?php esc_html_e( 'Your details', 'woocommerce' ); ?></li>
			<li class="woocommerce-order-withdrawal-content__step"><?php esc_html_e( 'Review and confirm', 'woocommerce' ); ?></li>
		</ol>

		<form class="woocommerce-OrderWithdrawalForm" action="<?php echo esc_url( $form_action_url ); ?>" method="post" novalidate>
			<?php foreach ( $form_field_keys as $field_key ) : ?>
				<?php
				$field      = $fields[ $field_key ] ?? array();
				$field_name = isset( $field['name'] ) ? (string) $field['name'] : '';

				if ( '' === $field_name ) {
					continue;
				}

				$field['return'] = true;
				$field_html      = woocommerce_form_field( $field_name, $field, $data[ $field_key ] ?? '' );

				if ( isset( $form_errors[ $field_key ] ) ) {
					$field_error_html = sprintf(
						'<span id="%1$s" class="woocommerce-order-withdrawal-content__field-error">%2$s</span>',
						esc_attr( $field_name . '_error' ),
						esc_html( $form_errors[ $field_key ] )
					);
					$field_html       = str_replace( '</p>', $field_error_html . '</p>', $field_html );
				}
				?>
				<?php echo $field_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

				<?php if ( 'last_name' === $field_key ) : ?>
					<div class="clear"></div>
				<?php endif; ?>
			<?php endforeach; ?>

			<?php
			$withdrawal_type_name    = isset( $withdrawal_type_field['name'] ) ? (string) $withdrawal_type_field['name'] : '';
			$withdrawal_type_id      = isset( $withdrawal_type_field['id'] ) ? (string) $withdrawal_type_field['id'] : $withdrawal_type_name;
			$withdrawal_type_classes = array( 'woocommerce-form-row', 'form-row', 'form-row-wide', 'woocommerce-order-withdrawal-content__radio-field' );

			if ( isset( $form_errors['withdrawal_type'] ) ) {
				$withdrawal_type_classes[] = 'woocommerce-invalid';
			}

			$withdrawal_type_error_attributes = array(
				'aria-invalid' => 'false',
			);

			if ( isset( $form_errors['withdrawal_type'] ) ) {
				$withdrawal_type_error_attributes = array(
					'aria-invalid'      => 'true',
					'aria-errormessage' => $withdrawal_type_name . '_error',
				);
			}
			?>
			<fieldset class="<?php echo esc_attr( implode( ' ', $withdrawal_type_classes ) ); ?>">
				<legend><?php echo esc_html( $withdrawal_type_field['label'] ?? '' ); ?>&nbsp;<span class="required" aria-hidden="true">*</span></legend>
				<?php foreach ( $withdrawal_type_options as $option_value => $option_label ) : ?>
					<label for="<?php echo esc_attr( $withdrawal_type_id . '_' . $option_value ); ?>" class="woocommerce-order-withdrawal-content__radio-label">
						<input type="radio" class="input-radio" name="<?php echo esc_attr( $withdrawal_type_name ); ?>" id="<?php echo esc_attr( $withdrawal_type_id . '_' . $option_value ); ?>" value="<?php echo esc_attr( $option_value ); ?>" <?php checked( $data['withdrawal_type'] ?? '', $option_value ); ?> aria-required="true" <?php echo wc_implode_html_attributes( $withdrawal_type_error_attributes ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> />
						<?php echo esc_html( $option_label ); ?>
					</label>
				<?php endforeach; ?>
			</fieldset>
			<?php if ( isset( $form_errors['withdrawal_type'] ) ) : ?>
				<span id="<?php echo esc_attr( $withdrawal_type_name . '_error' ); ?>" class="woocommerce-order-withdrawal-content__field-error"><?php echo esc_html( $form_errors['withdrawal_type'] ); ?></span>
			<?php endif; ?>

			<?php if ( ! empty( $additional_details['name'] ) ) : ?>
				<?php
				$additional_details_name      = (string) $additional_details['name'];
				$additional_details['return'] = true;
				$additional_details_html      = woocommerce_form_field( $additional_details_name, $additional_details, $data['additional_details'] ?? '' );

				if ( isset( $form_errors['additional_details'] ) ) {
					$additional_details_error_html = sprintf(
						'<span id="%1$s" class="woocommerce-order-withdrawal-content__field-error">%2$s</span>',
						esc_attr( $additional_details_name . '_error' ),
						esc_html( $form_errors['additional_details'] )
					);
					$additional_details_html       = str_replace( '</p>', $additional_details_error_html . '</p>', $additional_details_html );
				}
				?>
				<?php echo $additional_details_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php endif; ?>
			<span class="woocommerce-order-withdrawal-content__note"><?php esc_html_e( 'No reason needed. If you selected specific items, list them here.', 'woocommerce' ); ?></span>

			<p class="woocommerce-order-withdrawal-content__actions">
				<?php wp_nonce_field( $nonce_action, $nonce_field ); ?>
				<button type="submit" class="<?php echo esc_attr( $button_class ); ?>" name="<?php echo esc_attr( $action_field ); ?>" value="<?php echo esc_attr( $action_review ); ?>"><?php esc_html_e( 'Continue to review', 'woocommerce' ); ?></button>
			</p>
		</form>
	<?php endif; ?>
</div>
