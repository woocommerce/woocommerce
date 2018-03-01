<?php
/**
 * Admin View: Steps
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<ol class="wc-progress-steps">
	<?php foreach ( $this->steps as $step_key => $step ) : ?>
		<li class="<?php
			if ( $step_key === $this->step ) {
				echo 'active';
			} elseif ( array_search( $this->step, array_keys( $this->steps ) ) > array_search( $step_key, array_keys( $this->steps ) ) ) {
				echo 'done';
			}
		?>"><?php echo esc_html( $step['name'] ); ?></li>
	<?php endforeach; ?>
</ol>
