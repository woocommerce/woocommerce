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

	        if ($dir == '\\' || $dir == '/') { $dir = ''; } // End IF Statement

	        return self::get_files(basename( $pattern ), $flags, $dir . '/' );

	    } // End IF Statement

	    $paths = glob( $path . '*', GLOB_ONLYDIR | GLOB_NOSORT );
	    $files = glob( $path . $pattern, $flags );

	    if ( is_array( $paths ) ) {
		    foreach ( $paths as $p ) {
			    $found_files = array();
		   		$retrieved_files = (array) self::get_files( $pattern, $flags, $p . '/' );
		   		foreach ( $retrieved_files as $file ) {
			   		if ( ! in_array( $file, self::$found_files ) )
			   			$found_files[] = $file;
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
			$link = 'http://docs.woothemes.com/wc-apidocs/source-class-' . $details['class'] . '.html#' . $details['line'];
		} elseif ( ! empty( $details['function'] ) ) {
			$link = 'http://docs.woothemes.com/wc-apidocs/source-function-' . $details['function'] . '.html#' . $details['line'];
		} else {
			$link = 'https://github.com/woothemes/woocommerce/search?utf8=%E2%9C%93&q=' . $hook;
		}

		return '<a href="' . $link . '">' . $hook . '</a>';
	}

	public static function process_hooks() {
		// If we have one, get the PHP files from it.
		$template_files 	= self::get_files( '*.php', GLOB_MARK, '../templates/' );
		$template_files[]	= '../includes/wc-template-functions.php';
		$template_files[]	= '../includes/wc-template-hooks.php';

		$shortcode_files 	= self::get_files( '*.php', GLOB_MARK, '../includes/shortcodes/' );
		$widget_files	 	= self::get_files( '*.php', GLOB_MARK, '../includes/widgets/' );
		$admin_files 		= self::get_files( '*.php', GLOB_MARK, '../includes/admin/' );
		$class_files 		= self::get_files( '*.php', GLOB_MARK, '../includes/' );
		$other_files		= array(
			'../woocommerce.php'
		);

		self::$files_to_scan = array(
			'Template Hooks'  => $template_files,
			'Shortcode Hooks' => $shortcode_files,
			'Widget Hooks'    => $widget_files,
			'Class Hooks'     => $class_files,
			'Admin Hooks'     => $admin_files,
			'Other Hooks'     => $other_files,
		);

		$scanned = array();

		ob_start();

		echo '<div id="content">';
		echo '<h1>Action and Filter Hook Reference</h1>';

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
						if ( $token[0] == T_CLASS ) {
							$token_type = 'class';
						} elseif ( $token[0] == T_FUNCTION ) {
							$token_type = 'function';
						} elseif ( $token[1] === 'do_action' ) {
							$token_type = 'action';
						} elseif ( $token[1] === 'apply_filters' ) {
							$token_type = 'filter';
						} elseif ( $token_type && ! empty( trim( $token[1] ) ) ) {
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
									if ( isset( self::$custom_hooks_found[ $hook ] ) ) {
										self::$custom_hooks_found[ $hook ]['file'][] = self::$current_file;
									} else {
    									self::$custom_hooks_found[ $hook ] = array(
											'line'     => $token[2],
											'class'    => $current_class,
											'function' => $current_function,
											'file'     => array( self::$current_file ),
											'type'     => $token_type
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
					unset( self::$custom_hooks_found[ $hook ] );
				}
			}

			ksort( self::$custom_hooks_found );

			if ( ! empty( self::$custom_hooks_found ) ) {
				echo '<div class="panel panel-default"><div class="panel-heading"><h2>' . $heading . '</h2></div>';

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
		$header = current( explode( '<div id="content">', $html ) );
		$header = str_replace( '<li class="active">', '<li>', $header );
		$header = str_replace( '<li class="hooks">', '<li class="active">', $header );
		$header = str_replace( 'Tree | ', 'Hook Reference | ', $header );
		$footer = end( explode( '<div id="footer">', $html ) );

		file_put_contents( '../wc-apidocs/hook-docs.html', $header . ob_get_clean() . $footer );
		echo "Hook docs generated :)\n";
	}
}

WC_HookFinder::process_hooks();
