<?php
/**
 * Generate documentation for hooks in WC
 */
class WC_HookFinder {
	private static $current_file           = '';
	private static $files_to_scan          = array();
	private static $pattern_custom_actions = '/do_action(.*?);/i';
	private static $pattern_custom_filters = '/apply_filters(.*?);/i';
	private static $found_files            = array();
	private static $custom_hooks_found     = '';

	private static function get_files( $pattern, $flags = 0, $path = '' ) {

		if ( ! $path && ( $dir = dirname( $pattern ) ) != '.' ) {

			if ( '\\' == $dir || '/' == $dir ) {
				$dir = '';
			}

			return self::get_files( basename( $pattern ), $flags, $dir . '/' );

		} // End IF Statement

		$paths = glob( $path . '*', GLOB_ONLYDIR | GLOB_NOSORT );
		$files = glob( $path . $pattern, $flags );

		if ( is_array( $paths ) ) {
			foreach ( $paths as $p ) {
				$found_files = array();
				$retrieved_files = (array) self::get_files( $pattern, $flags, $p . '/' );
				foreach ( $retrieved_files as $file ) {
					if ( ! in_array( $file, self::$found_files ) ) {
						$found_files[] = $file;
					}
				}

				self::$found_files = array_merge( self::$found_files, $found_files );

				if ( is_array( $files ) && is_array( $found_files ) ) {
					$files = array_merge( $files, $found_files );
				}
			} // End FOREACH Loop
		}
		return $files;
	}

	private static function get_hook_link( $hook, $details = array() ) {
		if ( ! empty( $details['class'] ) ) {
			$link = 'http://docs.woocommerce.com/wc-apidocs/source-class-' . $details['class'] . '.html#' . $details['line'];
		} elseif ( ! empty( $details['function'] ) ) {
			$link = 'http://docs.woocommerce.com/wc-apidocs/source-function-' . $details['function'] . '.html#' . $details['line'];
		} else {
			$link = 'https://github.com/woocommerce/woocommerce/search?utf8=%E2%9C%93&q=' . $hook;
		}

		return '<a href="' . $link . '">' . $hook . '</a>';
	}

	public static function process_hooks() {
		self::$files_to_scan = array();

		self::$files_to_scan['Template Files']     = self::get_files( '*.php', GLOB_MARK, '../templates/' );
		self::$files_to_scan['Template Functions'] = array( '../includes/wc-template-functions.php', '../includes/wc-template-hooks.php' );
		self::$files_to_scan['Shortcodes']         = self::get_files( '*.php', GLOB_MARK, '../includes/shortcodes/' );
		self::$files_to_scan['Widgets']            = self::get_files( '*.php', GLOB_MARK, '../includes/widgets/' );
		self::$files_to_scan['Data Stores']        = self::get_files( '*.php', GLOB_MARK, '../includes/data-stores' );
		self::$files_to_scan['Core Classes']       = array_merge(
			self::get_files( '*.php', GLOB_MARK, '../includes/' ),
			self::get_files( '*.php', GLOB_MARK, '../includes/abstracts/' ),
			self::get_files( '*.php', GLOB_MARK, '../includes/customizer/' ),
			self::get_files( '*.php', GLOB_MARK, '../includes/emails/' ),
			self::get_files( '*.php', GLOB_MARK, '../includes/export/' ),
			self::get_files( '*.php', GLOB_MARK, '../includes/gateways/' ),
			self::get_files( '*.php', GLOB_MARK, '../includes/import/' ),
			self::get_files( '*.php', GLOB_MARK, '../includes/shipping/' )
		);

		self::$files_to_scan = array_filter( self::$files_to_scan );

		$scanned = array();

		ob_start();

		$index = array();

		foreach ( self::$files_to_scan as $heading => $files ) {
			$index[] = '<a href="#hooks-' . str_replace( ' ', '-', strtolower( $heading ) ) . '">' . $heading . '</a>';
		}

		echo '<div id="content">';
		echo '<h1>Action and Filter Hook Reference</h1>';
		echo '<div class="description">
			<p>This is simply a list of action and filter hooks found within WooCommerce files. View the source to see supported params and usage.</p>
			<p>' . implode( ', ', $index ) . '</p>
		</div>';

		foreach ( self::$files_to_scan as $heading => $files ) {
			self::$custom_hooks_found = array();

			foreach ( $files as $f ) {
				self::$current_file = basename( $f );
				$tokens             = token_get_all( file_get_contents( $f ) );
				$token_type         = false;
				$current_class      = '';
				$current_function   = '';

				if ( in_array( self::$current_file, $scanned ) ) {
					continue;
				}

				$scanned[] = self::$current_file;

				foreach ( $tokens as $index => $token ) {
					if ( is_array( $token ) ) {
						$trimmed_token_1 = trim( $token[1] );
						if ( T_CLASS == $token[0] ) {
							$token_type = 'class';
						} elseif ( T_FUNCTION == $token[0] ) {
							$token_type = 'function';
						} elseif ( 'do_action' === $token[1] ) {
							$token_type = 'action';
						} elseif ( 'apply_filters' === $token[1] ) {
							$token_type = 'filter';
						} elseif ( $token_type && ! empty( $trimmed_token_1 ) ) {
							switch ( $token_type ) {
								case 'class' :
									$current_class = $token[1];
								break;
								case 'function' :
									$current_function = $token[1];
								break;
								case 'filter' :
								case 'action' :
									$hook = trim( $token[1], "'" );
									$hook = str_replace( '_FUNCTION_', strtoupper( $current_function ), $hook );
									$hook = str_replace( '_CLASS_', strtoupper( $current_class ), $hook );
									$hook = str_replace( '$this', strtoupper( $current_class ), $hook );
									$hook = str_replace( array( '.', '{', '}', '"', "'", ' ', ')', '(' ), '', $hook );
									$loop = 0;

									// Keep adding to hook until we find a comma or colon
									while ( 1 ) {
										$loop ++;
										$prev_hook = is_string( $tokens[ $index + $loop - 1 ] ) ? $tokens[ $index + $loop - 1 ] : $tokens[ $index + $loop - 1 ][1];
										$next_hook = is_string( $tokens[ $index + $loop ] ) ? $tokens[ $index + $loop ] : $tokens[ $index + $loop ][1];

										if ( in_array( $next_hook, array( '.', '{', '}', '"', "'", ' ', ')', '(' ) ) ) {
											continue;
										}

										if ( in_array( $next_hook, array( ',', ';' ) ) ) {
											break;
										}

										$hook_first = substr( $next_hook, 0, 1 );
										$hook_last  = substr( $next_hook, -1, 1 );

										if ( '{' === $hook_first || '}' === $hook_last || '$' === $hook_first || ')' === $hook_last || '>' === substr( $prev_hook, -1, 1 ) ) {
											$next_hook = strtoupper( $next_hook );
										}

										$next_hook = str_replace( array( '.', '{', '}', '"', "'", ' ', ')', '(' ), '', $next_hook );

										$hook .= $next_hook;
									}

									if ( isset( self::$custom_hooks_found[ $hook ] ) ) {
										self::$custom_hooks_found[ $hook ]['file'][] = self::$current_file;
									} else {
										self::$custom_hooks_found[ $hook ] = array(
											'line'     => $token[2],
											'class'    => $current_class,
											'function' => $current_function,
											'file'     => array( self::$current_file ),
											'type'     => $token_type,
										);
									}
								break;
							}
							$token_type = false;
						}
					}
				}
			}

			foreach ( self::$custom_hooks_found as $hook => $details ) {
				if ( ! strstr( $hook, 'woocommerce' ) && ! strstr( $hook, 'product' ) && ! strstr( $hook, 'wc_' ) ) {
					//unset( self::$custom_hooks_found[ $hook ] );
				}
			}

			ksort( self::$custom_hooks_found );

			if ( ! empty( self::$custom_hooks_found ) ) {
				echo '<div class="panel panel-default"><div class="panel-heading"><h2 id="hooks-' . str_replace( ' ', '-', strtolower( $heading ) ) . '">' . $heading . '</h2></div>';

				echo '<table class="summary table table-bordered table-striped"><thead><tr><th>Hook</th><th>Type</th><th>File(s)</th></tr></thead><tbody>';

				foreach ( self::$custom_hooks_found as $hook => $details ) {
					echo '<tr>
						<td>' . self::get_hook_link( $hook, $details ) . '</td>
						<td>' . $details['type'] . '</td>
						<td>' . implode( ', ', array_unique( $details['file'] ) ) . '</td>
					</tr>' . "\n";
				}

				echo '</tbody></table></div>';
			}
		}

		echo '</div><div id="footer">';

		$html   = file_get_contents( '../wc-apidocs/tree.html' );
		$header = explode( '<div id="content">', $html );
		$header = str_replace( '<li class="active">', '<li>', current( $header ) );
		$header = str_replace( '<li class="hooks">', '<li class="active">', $header );
		$header = str_replace( 'Tree | ', 'Hook Reference | ', $header );
		$footer = explode( '<div id="footer">', $html );

		file_put_contents( '../wc-apidocs/hook-docs.html', $header . ob_get_clean() . end( $footer ) );
		echo "Hook docs generated :)\n";
	}
}

WC_HookFinder::process_hooks();
