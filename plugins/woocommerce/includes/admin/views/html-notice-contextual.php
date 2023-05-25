<?php
/**
 * Admin View: Custom Notices
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<div class="woocommerce-contextual-notice woocommerce-contextual-notice-<?php echo esc_attr( $severity ); ?>">
  <?php echo wp_kses_post( $notice_html ); ?>

  <?php if ( ! empty( $button_url ) && ! empty( $button_text ) ): ?>
  <div class="woocommerce-contextual-notice-cta" >
  	<a class="components-button is-secondary"
      href="<?php echo esc_url( $button_url ); ?>">
      <?php echo esc_html( $button_text ) ?>
    </a>
  </div>
  <?php endif; ?>
</div>
