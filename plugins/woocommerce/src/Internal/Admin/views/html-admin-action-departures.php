<?php
/**
 * Scheduled actions departures board.
 *
 * @package WooCommerce\Admin
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;
?>
<div class="wrap">
	<p><a href="<?php echo esc_url( $list_url ); ?>"><?php esc_html_e( '← Back to scheduled actions', 'woocommerce' ); ?></a></p>
	<div class="wc-departures">
		<header class="wc-departures__header">
			<div>
				<p class="wc-departures__eyebrow"><?php esc_html_e( 'WooCommerce · Background operations', 'woocommerce' ); ?></p>
				<h1><?php self::render_flaps( __( 'Departures', 'woocommerce' ) ); ?></h1>
				<p class="wc-departures__subtitle"><?php esc_html_e( 'Small tasks. Great journeys.', 'woocommerce' ); ?></p>
			</div>
			<div class="wc-departures__snapshot">
				<span><?php esc_html_e( 'Board updated', 'woocommerce' ); ?></span>
				<strong><?php echo esc_html( wp_date( 'H:i:s', $now ) ); ?></strong>
				<span><?php echo esc_html( wp_date( 'M j, Y', $now ) . ' · ' . wp_timezone_string() ); ?></span>
			</div>
		</header>
		<div class="wc-departures__toolbar">
			<nav aria-label="<?php esc_attr_e( 'Action status', 'woocommerce' ); ?>">
				<?php foreach ( $statuses as $key => $name ) : ?>
					<a href="<?php echo esc_url( add_query_arg( 'status', $key, $board_url ) ); ?>" <?php echo $key === $status ? 'aria-current="page"' : ''; ?>><?php echo esc_html( $name ); ?></a>
				<?php endforeach; ?>
			</nav>
			<a class="wc-departures__refresh" href="<?php echo esc_url( add_query_arg( 'status', $status, $board_url ) ); ?>"><?php esc_html_e( 'Refresh board ↻', 'woocommerce' ); ?></a>
		</div>
		<div class="wc-departures__table-wrap" role="region" aria-label="<?php esc_attr_e( 'Scheduled action departures', 'woocommerce' ); ?>" tabindex="0">
			<table class="wc-departures__table">
				<caption class="screen-reader-text"><?php esc_html_e( 'Scheduled times are shown in the site timezone, not actual start or completion times.', 'woocommerce' ); ?></caption>
				<thead><tr>
					<th scope="col"><?php esc_html_e( 'Scheduled', 'woocommerce' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Flight', 'woocommerce' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Destination / hook', 'woocommerce' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Status', 'woocommerce' ); ?></th>
				</tr></thead>
				<tbody>
					<?php foreach ( $rows as $row ) : ?>
						<tr>
							<td class="wc-departures__time">
								<?php if ( $row['date'] && $row['date']->getTimestamp() > 0 ) : ?>
									<time datetime="<?php echo esc_attr( $row['date']->format( DATE_ATOM ) ); ?>">
										<?php self::render_flaps( wp_date( 'H:i', $row['date']->getTimestamp() ) ); ?>
										<span class="wc-departures__date"><?php echo esc_html( wp_date( 'M j, Y', $row['date']->getTimestamp() ) ); ?></span>
									</time>
								<?php else : ?>
									<?php esc_html_e( 'When available', 'woocommerce' ); ?>
								<?php endif; ?>
							</td>
							<td class="wc-departures__flight"><?php echo esc_html( 'AS ' . str_pad( (string) $row['id'], 6, '0', STR_PAD_LEFT ) ); ?></td>
							<td class="wc-departures__destination">
								<a href="<?php echo esc_url( add_query_arg( 's', $row['hook'], $list_url ) ); ?>"><code><?php echo esc_html( $row['hook'] ); ?></code></a>
								<span class="wc-departures__group"><?php echo esc_html( $row['group'] ? $row['group'] : __( 'No group', 'woocommerce' ) ); ?></span>
							</td>
							<td class="wc-departures__status wc-departures__status--<?php echo esc_attr( $status ); ?>"><?php self::render_flaps( $row['label'] ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php if ( empty( $rows ) ) : ?>
			<div class="wc-departures__empty">
				<span aria-hidden="true">✈</span>
				<h2><?php esc_html_e( 'A quiet terminal.', 'woocommerce' ); ?></h2>
				<p><?php esc_html_e( 'No actions with this status. Enjoy the moment.', 'woocommerce' ); ?></p>
			</div>
		<?php endif; ?>
		<footer class="wc-departures__footer">
			<p><?php esc_html_e( 'A snapshot, not a timetable guarantee. All times use your site timezone.', 'woocommerce' ); ?></p>
			<a href="<?php echo esc_url( add_query_arg( 'status', $status, $list_url ) ); ?>">
				<?php echo esc_html( $has_more ? __( 'More departures in the action list →', 'woocommerce' ) : __( 'Open the action list →', 'woocommerce' ) ); ?>
			</a>
		</footer>
	</div>
</div>
