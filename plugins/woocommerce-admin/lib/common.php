<?php

/**
 * Retrieves the root plugin path.
 *
 * @return string Root path to the gutenberg custom fields plugin.
 */
function woo_dash_dir_path() {
	return plugin_dir_path( dirname(__FILE__ ) );
}

/**
 * Retrieves a URL to a file in the gutenberg custom fields plugin.
 *
 * @param  string $path Relative path of the desired file.
 *
 * @return string       Fully qualified URL pointing to the desired file.
 */
function woo_dash_url( $path ) {
	return plugins_url( $path, dirname( __FILE__ ) );
}
