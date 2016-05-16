<?php
/**
 * Admin View: Page - Reports
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<div class="wrap woocommerce">
	<div class="icon32 icon32-woocommerce-reports" id="icon-woocommerce"><br /></div>
	<h1><?php echo sprintf( esc_html__( '%s Report', 'woocommerce' ), $reports[ $current_tab ]['title'] ); ?></h1>
	<h2 class="nav-tab-wrapper woo-nav-tab-wrapper">
		<?php
			foreach ( $reports as $key => $report_group ) {
				echo '<a href="' . admin_url( 'admin.php?page=wc-reports&tab=' . urlencode( $key ) ) . '" class="nav-tab ';
				if ( $current_tab == $key ) {
					echo 'nav-tab-active';
				}
				echo '">' . esc_html( $report_group[ 'title' ] ) . '</a>';
			}

			do_action( 'wc_reports_tabs' );
		?>
	</h2>
	<?php if ( sizeof( $reports[ $current_tab ]['reports'] ) > 1 ) {
		?>
		<ul class="subsubsub">
			<li><?php

				$links = array();

				foreach ( $reports[ $current_tab ]['reports'] as $key => $report ) {

					$link = '<a href="admin.php?page=wc-reports&tab=' . urlencode( $current_tab ) . '&amp;report=' . urlencode( $key ) . '" class="';

					if ( $key == $current_report ) {
						$link .= 'current';
					}

					$link .= '">' . $report['title'] . '</a>';

					$links[] = $link;

				}

				echo implode( ' | </li><li>', $links );

			?></li>
		</ul>
		<br class="clear" />
		<?php
	}

	if ( isset( $reports[ $current_tab ][ 'reports' ][ $current_report ] ) ) {

		$report = $reports[ $current_tab ][ 'reports' ][ $current_report ];

		if ( ! isset( $report['hide_title'] ) || $report['hide_title'] != true ) {
			echo '<h2>' . esc_html( $report['title'] ) . '</h2>';
		} else {
			echo '<h2 class="screen-reader-text">' . esc_html( $report['title'] ) . '</h2>';
		}

		if ( $report['description'] ) {
			echo '<p>' . $report['description'] . '</p>';
		}

		if ( $report['callback'] && ( is_callable( $report['callback'] ) ) ) {
			call_user_func( $report['callback'], $current_report );
		}
	}
	?>
</div>
