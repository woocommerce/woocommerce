<?php
/**
 * HTML for a report with date filters in admin.
 */
?>
<div id="poststuff" class="woocommerce-reports-wide">
	<div class="postbox">
		<h3 class="stats_range">
			<ul>
				<?php
					foreach ( $ranges as $range => $name )
						echo '<li class="' . ( $current_range == $range ? 'active' : '' ) . '"><a href="' . remove_query_arg( array( 'start_date', 'end_date' ), add_query_arg( 'range', $range ) ) . '">' . $name . '</a></li>';
				?>
				<li class="custom <?php echo $current_range == 'custom' ? 'active' : ''; ?>">
					<?php _e( 'Custom:', 'woocommerce' ); ?>
					<form method="GET">
						<div>
							<input type="text" size="9" placeholder="yyyy-mm-dd" value="<?php if ( ! empty( $_GET['start_date'] ) ) echo esc_attr( $_GET['start_date'] ); ?>" name="start_date" class="range_datepicker from" />
							<input type="text" size="9" placeholder="yyyy-mm-dd" value="<?php if ( ! empty( $_GET['end_date'] ) ) echo esc_attr( $_GET['end_date'] ); ?>" name="end_date" class="range_datepicker to" />
							<input type="hidden" name="range" value="custom" />
							<input type="hidden" name="page" value="<?php if ( ! empty( $_GET['page'] ) ) echo esc_attr( $_GET['page'] ) ?>" />
							<input type="hidden" name="tab" value="<?php if ( ! empty( $_GET['tab'] ) ) echo esc_attr( $_GET['tab'] ) ?>" />
							<input type="submit" class="button" value="<?php _e( 'Go', 'woocommerce' ); ?>" />
						</div>
					</form>
				</li>
			</ul>
		</h3>
		<div class="inside chart-with-sidebar">
			<div class="chart-sidebar">
				<ul class="chart-legend">
					<?php foreach ( $this->get_chart_legend() as $legend ) : ?>
						<li style="border-color: <?php echo $legend['color']; ?>">
							<?php echo $legend['title']; ?>
						</li>
					<?php endforeach; ?>
				</ul>
				<ul class="chart-widgets">
					<?php foreach ( $this->get_chart_widgets() as $widget ) : ?>
						<li class="chart-widget">
							<h4><?php echo $widget['title']; ?></h4>
							<?php call_user_func( $widget['callback'] ); ?>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
			<div class="main">
				<?php $this->get_main_chart(); ?>
			</div>
		</div>
	</div>
</div>