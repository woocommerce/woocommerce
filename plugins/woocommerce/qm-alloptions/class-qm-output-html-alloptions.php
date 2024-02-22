<?php
/**
 * Output class
 *
 * Class QM_Output_AllOptions
 */
class QM_Output_Html_AllOptions extends QM_Output_Html {

	public function __construct( QM_Collector $collector ) {
		parent::__construct( $collector );
		add_filter( 'qm/output/title', array( $this, 'admin_title' ), 101 );
		add_filter( 'qm/output/menu_class', array( $this, 'admin_class' ) );
		add_filter( 'qm/output/menus', array( $this, 'admin_menu' ), 101 );
	}

	/**
	 * Outputs data in the footer
	 */
	public function output() {
		$data = $this->collector->get_data();
		?>
		<div class="qm qm-non-tabular" id="qm-<?php echo esc_attr( $this->collector->id ); ?>" role="tabpanel" aria-labelledby="qm-<?php echo esc_attr( $this->collector->id ); ?>-caption" tabindex="-1">
			<?php
			if ( $this->size_is_concerning() ) {
				// 1000000 set in alloptions-limit.php#L71
				$percentage = round( ( $data->total_size_comp / 1000000 ) * 100 );
				echo '<section class="qm-notice"><p>';
				printf(
					wp_kses(
						/* translators: 1. percentage */
						__( '⚠️ The total size of autoloaded options is at %d%% of maximum. <a href="https://docs.wpvip.com/technical-references/code-quality-and-best-practices/working-with-wp_options/#h-identify-and-resolve-problems-with-alloptions">Review and clean up options</a> listed below to avoid impacting performance or uptime.', 'qm-monitor' ),
						[ 'a' => [ 'href' => true ] ]
					),
					intval( $percentage )
				);
				echo '</p></section>';
			}
			?>
			<div class="qm-boxed">
			<section>
				<table>
					<caption class="screen-reader-text"><?php esc_html_e( 'Show total size of autoloaded options and its impact.', 'qm-monitor' ); ?></caption>
					<thead>
						<tr>
							<th scope="col"><h3><?php esc_html_e( 'Total Size', 'qm-monitor' ); ?></h3></th>
							<th scope="col" class="qm-num"><?php esc_html_e( 'Size (bytes)', 'qm-monitor' ); ?></th>
							<th scope="col" class="qm-num"><?php esc_html_e( 'Size (human)', 'qm-monitor' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Impact', 'qm-monitor' ); ?></th>
						</tr>
					</thead>
					<tr>
						<th scope="row"><?php esc_html_e( 'Uncompressed', 'qm-monitor' ); ?></td>
						<td class="qm-num"><?php echo esc_html( $data->total_size ); ?></td>
						<td class="qm-num"><?php echo esc_html( size_format( $data->total_size, 2 ) ); ?></td>
						<td><?php esc_html_e( 'Consumes PHP memory', 'qm-monitor' ); ?></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Compressed', 'qm-monitor' ); ?></td>
						<td class="qm-num"><?php echo esc_html( $data->total_size_comp ); ?></td>
						<td class="qm-num"><?php echo esc_html( size_format( $data->total_size_comp, 2 ) ); ?></td>
						<td><?php echo wp_kses( __( 'At 1000000 bytes, an error page will be shown to prevent overrunning the database. <a href="https://docs.wpvip.com/technical-references/code-quality-and-best-practices/working-with-wp_options/#h-identify-and-resolve-problems-with-alloptions">Read more</a>', 'qm-monitor' ), [ 'a' => [ 'href' => true ] ] ); ?></td>
					</tr>
				</table>
			</section>
			<section>
				<?php
					esc_html_e( 'To un-autoload an option, you can use the following command:', 'qm-monitor' );
					echo '<br><code>wp option autoload set &lt;option_name&gt; no</code><br>';
					esc_html_e( 'In some cases, the code which sets the option will need to be updated.', 'qm-monitor' );
				?>
			</section>
			</div>
			<p><?php esc_html_e( 'Starred (*) options are commonly large core options which should not be changed.', 'qm-monitor' ); ?></p>
			<table>
				<caption class="screen-reader-text"><?php esc_html_e( 'Show size of each value in autoloaded options.', 'qm-monitor' ); ?></caption>
				<thead>
					<tr>
						<th scope="col"><?php esc_html_e( 'Option name', 'qm-monitor' ); ?></th>
						<th scope="col" class="qm-num"><?php esc_html_e( 'Size (bytes)', 'qm-monitor' ); ?></th>
						<th scope="col" class="qm-num"><?php esc_html_e( 'Size (human)', 'qm-monitor' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php
					foreach ( $data->options as $option ) {
						echo '<tr>';
						printf(
							'<th scope="row" class="qm-ltr">%1$s%2$s</td><td class="qm-ltr qm-num">%3$d</td><td class="qm-ltr qm-num">%4$s</td>',
							esc_html( $option->name ),
							( $this->option_is_core( $option->name ) ? ' *' : '' ),
							esc_html( $option->size ),
							esc_html( size_format( $option->size, 2 ) )
						);
						echo '</tr>';
					}
					?>
				</tbody>
			</table>
		</div>
		<?php
	}

	/**
	 * Adds data to top admin bar
	 *
	 * @param array $title
	 * @return array
	 */
	public function admin_title( array $title ) {

		if ( $this->size_is_concerning() ) {
			$data               = $this->collector->get_data();
			list( $num, $unit ) = explode( ' ', size_format( $data->total_size_comp, 1 ) );

			$title[] = sprintf(
				/* translators: 1. size 2. size unit */
				_x( '%1$s<small> %2$s opts</small>', 'size of alloptions', 'qm-monitor' ),
				$num,
				$unit
			);
		}

		return $title;
	}

	/**
	 * @param array $class
	 * @return array
	 */
	public function admin_class( array $class ) {
		if ( $this->size_is_concerning() ) {
			$class[] = 'qm-warning';
		}
		return $class;
	}

	/**
	 * @param array $menu
	 * @return array
	 */
	public function admin_menu( array $menu ) {
		$title = __( 'Autoloaded Options', 'qm-monitor' );

		if ( $this->size_is_concerning() ) {
			$title = __( 'Autoloaded Options ⚠️', 'qm-monitor' );
		}

		$menu[] = $this->menu( array(
			'id'    => 'qm-alloptions',
			'href'  => '#qm-alloptions',
			'title' => $title,
		));

		return $menu;
	}

	/**
	 * Check if size is at warning threshold
	 *
	 * 80% of the error page threshold defined in alloptions-limit.php#L71
	 *
	 * @return bool
	 */
	private function size_is_concerning() {
		$data = $this->collector->get_data();
		return ( $data->total_size_comp > 800000 );
	}

	/**
	 * Check if option is a commonly large core option
	 *
	 * @return bool
	 */
	private function option_is_core( $option_name ) {
		$commonly_large_opts = [
			'rewrite_rules',
			'widget_block',
		];
		if ( in_array( $option_name, $commonly_large_opts, true ) ) {
			return true;
		}
		global $wpdb;
		if ( $option_name === $wpdb->prefix . 'user_roles' ) {
			return true;
		}

		return false;
	}
}
