<?php
/**
 * Admin View: Dashboard - Finish Setup
 *
 * @package WooCommerce\Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Dashboard setup widget view variables.
 *
 * @var array{title:string, content:string, button_label:string, image_url:string, image_alt:string} $task_header Task header data.
 * @var bool $task_is_in_progress Whether the task is in progress.
 * @var string $task_in_progress_label Task in-progress label.
 */
?>
<div class="dashboard-widget-finish-setup" data-current-step="<?php echo esc_attr( (string) ( $step_number - 1 ) ); ?>" data-total-steps="<?php echo esc_attr( (string) $tasks_count ); ?>">
	<div class="description">
		<div class="dashboard-widget-finish-setup__content">
			<div class="dashboard-widget-finish-setup__meta">
				<span class='progress-wrapper'>
					<svg class="circle-progress" width="17" height="17" version="1.1" xmlns="http://www.w3.org/2000/svg">
						<circle r="6.5" cx="10" cy="10" fill="transparent" stroke-dasharray="40.859" stroke-dashoffset="0"></circle>
						<circle class="bar" r="6.5" cx="190" cy="10" fill="transparent" stroke-dasharray="40.859" stroke-dashoffset="<?php echo esc_attr( $circle_dashoffset ); ?>" transform='rotate(-90 100 100)'></circle>
					</svg>
					<span><?php esc_html_e( 'Step', 'woocommerce' ); ?> <?php echo esc_html( $step_number ); ?> <?php esc_html_e( 'of', 'woocommerce' ); ?> <?php echo esc_html( $tasks_count ); ?></span>
				</span>
			</div>
			<h3 class="dashboard-widget-finish-setup__title">
				<?php echo esc_html( $task_header['title'] ); ?>
				<?php if ( $task_is_in_progress && $task_in_progress_label ) : ?>
					<span class="dashboard-widget-finish-setup__in-progress"><?php echo esc_html( $task_in_progress_label ); ?></span>
				<?php endif; ?>
			</h3>
			<p><?php echo esc_html( $task_header['content'] ); ?></p>
			<div><a href='<?php echo esc_url( $button_link ); ?>' class='button button-primary'><?php echo esc_html( $task_header['button_label'] ); ?></a></div>
		</div>
		<img
			class="dashboard-widget-finish-setup__image"
			src="<?php echo esc_url( $task_header['image_url'] ); ?>"
			alt="<?php echo esc_attr( $task_header['image_alt'] ); ?>"
		/>
	</div>
	<div class="clear"></div>
</div>

<script type="text/javascript">
	/*global jQuery */
	(function( $ ) {
		const widget = $( '.dashboard-widget-finish-setup' );
		const currentStep = widget.data( 'current-step' );
		const totalSteps = widget.data( 'total-steps' );

		$( function() {
			window.wcTracks.recordEvent( 'wcadmin_setup_widget_view', {
				completed_tasks: currentStep,
				total_tasks: totalSteps,
			} );
		});


		$( '.dashboard-widget-finish-setup a' ).on( 'click', function() {
			window.wcTracks.recordEvent( 'wcadmin_setup_widget_click', {
				completed_tasks: currentStep,
				total_tasks: totalSteps,
			} );
		});
	})( jQuery );
</script>
