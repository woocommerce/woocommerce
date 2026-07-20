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

	<h2><?php esc_html_e( 'Withdraw from contract', 'woocommerce' ); ?></h2>

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
			<li class="woocommerce-order-withdrawal-content__step"><?php esc_html_e( '1. Your details', 'woocommerce' ); ?></li>
			<li class="woocommerce-order-withdrawal-content__step woocommerce-order-withdrawal-content__step--current" aria-current="step"><?php esc_html_e( '2. Review and confirm', 'woocommerce' ); ?></li>
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

		<form class="woocommerce-OrderWithdrawalForm" method="post" novalidate>
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
			<li class="woocommerce-order-withdrawal-content__step woocommerce-order-withdrawal-content__step--current" aria-current="step"><?php esc_html_e( '1. Your details', 'woocommerce' ); ?></li>
			<li class="woocommerce-order-withdrawal-content__step"><?php esc_html_e( '2. Review and confirm', 'woocommerce' ); ?></li>
		</ol>

		<form class="woocommerce-OrderWithdrawalForm" method="post" novalidate>
			<?php foreach ( $form_field_keys as $field_key ) : ?>
				<?php
				$field         = $fields[ $field_key ] ?? array();
				$field_name    = isset( $field['name'] ) ? (string) $field['name'] : '';
				$field_id      = isset( $field['id'] ) ? (string) $field['id'] : $field_name;
				$field_type    = isset( $field['type'] ) ? (string) $field['type'] : 'text';
				$field_classes = array_merge( array( 'woocommerce-form-row', 'form-row' ), (array) ( $field['classes'] ?? array() ) );
				$description   = isset( $field['description'] ) ? (string) $field['description'] : '';

				if ( isset( $form_errors[ $field_key ] ) ) {
					$field_classes[] = 'woocommerce-invalid';
				}
				?>
				<p class="<?php echo esc_attr( implode( ' ', $field_classes ) ); ?>">
					<label for="<?php echo esc_attr( $field_id ); ?>"><?php echo esc_html( $field['label'] ?? '' ); ?>&nbsp;<span class="required" aria-hidden="true">*</span></label>
					<input type="<?php echo esc_attr( $field_type ); ?>" class="woocommerce-Input woocommerce-Input--<?php echo esc_attr( $field_type ); ?> input-text" name="<?php echo esc_attr( $field_name ); ?>" id="<?php echo esc_attr( $field_id ); ?>" value="<?php echo esc_attr( $data[ $field_key ] ?? '' ); ?>" aria-required="true" <?php echo isset( $form_errors[ $field_key ] ) ? 'aria-invalid="true"' : 'aria-invalid="false"'; ?> <?php echo ! empty( $field['autocomplete'] ) ? 'autocomplete="' . esc_attr( (string) $field['autocomplete'] ) . '"' : ''; ?> <?php echo '' !== $description ? 'aria-describedby="' . esc_attr( $field_id . '_description' ) . '"' : ''; ?> />
					<?php if ( '' !== $description ) : ?>
						<span id="<?php echo esc_attr( $field_id . '_description' ); ?>" class="woocommerce-order-withdrawal-content__field-description"><?php echo esc_html( $description ); ?></span>
					<?php endif; ?>
				</p>

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
			?>
			<fieldset class="<?php echo esc_attr( implode( ' ', $withdrawal_type_classes ) ); ?>">
				<legend><?php echo esc_html( $withdrawal_type_field['label'] ?? '' ); ?>&nbsp;<span class="required" aria-hidden="true">*</span></legend>
				<?php foreach ( $withdrawal_type_options as $option_value => $option_label ) : ?>
					<label for="<?php echo esc_attr( $withdrawal_type_id . '_' . $option_value ); ?>" class="woocommerce-order-withdrawal-content__radio-label">
						<input type="radio" class="input-radio" name="<?php echo esc_attr( $withdrawal_type_name ); ?>" id="<?php echo esc_attr( $withdrawal_type_id . '_' . $option_value ); ?>" value="<?php echo esc_attr( $option_value ); ?>" <?php checked( $data['withdrawal_type'] ?? '', $option_value ); ?> aria-required="true" <?php echo isset( $form_errors['withdrawal_type'] ) ? 'aria-invalid="true"' : 'aria-invalid="false"'; ?> />
						<?php echo esc_html( $option_label ); ?>
					</label>
				<?php endforeach; ?>
			</fieldset>

			<?php
			$additional_details_name = isset( $additional_details['name'] ) ? (string) $additional_details['name'] : '';
			$additional_details_id   = isset( $additional_details['id'] ) ? (string) $additional_details['id'] : $additional_details_name;
			?>
			<p class="woocommerce-form-row form-row form-row-wide">
				<label for="<?php echo esc_attr( $additional_details_id ); ?>"><?php echo esc_html( $additional_details['label'] ?? '' ); ?></label>
				<textarea class="woocommerce-Input woocommerce-Input--textarea input-text" name="<?php echo esc_attr( $additional_details_name ); ?>" id="<?php echo esc_attr( $additional_details_id ); ?>" rows="5" aria-describedby="<?php echo esc_attr( $additional_details_id . '_description' ); ?>"><?php echo esc_textarea( $data['additional_details'] ?? '' ); ?></textarea>
				<span id="<?php echo esc_attr( $additional_details_id . '_description' ); ?>" class="woocommerce-order-withdrawal-content__field-description"><?php echo esc_html( $additional_details['description'] ?? '' ); ?></span>
			</p>

			<p class="woocommerce-order-withdrawal-content__actions">
				<?php wp_nonce_field( $nonce_action, $nonce_field ); ?>
				<button type="submit" class="<?php echo esc_attr( $button_class ); ?>" name="<?php echo esc_attr( $action_field ); ?>" value="<?php echo esc_attr( $action_review ); ?>"><?php esc_html_e( 'Continue to review', 'woocommerce' ); ?></button>
			</p>
		</form>
	<?php endif; ?>
</div>
