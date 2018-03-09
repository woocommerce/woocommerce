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
		<?php
		if ( $step_key === $this->step ) {
			$step_class = 'active';
		} elseif ( array_search( $this->step, array_keys( $this->steps ) ) > array_search( $step_key, array_keys( $this->steps ) ) ) {
			$step_class = 'done';
		}
		?>
		<li class="<?php echo esc_attr( $step_class ); ?>">
			<?php echo esc_html( $step['name'] ); ?>
		</li>
	<?php endforeach; ?>
</ol>
