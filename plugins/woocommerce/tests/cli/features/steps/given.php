<?php

use Behat\Gherkin\Node\PyStringNode,
    Behat\Gherkin\Node\TableNode,
    WP_CLI\Process;

$steps->Given( '/^an empty directory$/',
	function ( $world ) {
		$world->create_run_dir();
	}
);

$steps->Given( '/^an empty cache/',
	function ( $world ) {
		$world->variables['SUITE_CACHE_DIR'] = FeatureContext::create_cache_dir();
	}
);

$steps->Given( '/^an? ([^\s]+) file:$/',
	function ( $world, $path, PyStringNode $content ) {
		$content = (string) $content . "\n";
		$full_path = $world->variables['RUN_DIR'] . "/$path";
		Process::create( \WP_CLI\utils\esc_cmd( 'mkdir -p %s', dirname( $full_path ) ) )->run_check();
		file_put_contents( $full_path, $content );
	}
);

$steps->Given( '/^WP files$/',
	function ( $world ) {
		$world->download_wp();
	}
);

$steps->Given( '/^wp-config\.php$/',
	function ( $world ) {
		$world->create_config();
	}
);

$steps->Given( '/^a database$/',
	function ( $world ) {
		$world->create_db();
	}
);

$steps->Given( '/^a WP install$/',
	function ( $world ) {
		$world->install_wp();
	}
);

$steps->Given( "/^a WP install in '([^\s]+)'$/",
	function ( $world, $subdir ) {
		$world->install_wp( $subdir );
	}
);

$steps->Given( '/^a WP multisite (subdirectory|subdomain)?\s?install$/',
	function ( $world, $type = 'subdirectory' ) {
		$world->install_wp();
		$subdomains = ! empty( $type ) && 'subdomain' === $type ? 1 : 0;
		$world->proc( 'wp core install-network', array( 'title' => 'WP CLI Network', 'subdomains' => $subdomains ) )->run_check();
	}
);

$steps->Given( '/^these installed and active plugins:$/',
	function( $world, $stream ) {
		$plugins = implode( ' ', array_map( 'trim', explode( PHP_EOL, (string)$stream ) ) );
		$world->proc( "wp plugin install $plugins --activate" )->run_check();
	}
);

$steps->Given( '/^a custom wp-content directory$/',
	function ( $world ) {
		$wp_config_path = $world->variables['RUN_DIR'] . "/wp-config.php";

		$wp_config_code = file_get_contents( $wp_config_path );

		$world->move_files( 'wp-content', 'my-content' );
		$world->add_line_to_wp_config( $wp_config_code,
			"define( 'WP_CONTENT_DIR', dirname(__FILE__) . '/my-content' );" );

		$world->move_files( 'my-content/plugins', 'my-plugins' );
		$world->add_line_to_wp_config( $wp_config_code,
			"define( 'WP_PLUGIN_DIR', __DIR__ . '/my-plugins' );" );

		file_put_contents( $wp_config_path, $wp_config_code );
	}
);

$steps->Given( '/^download:$/',
	function ( $world, TableNode $table ) {
		foreach ( $table->getHash() as $row ) {
			$path = $world->replace_variables( $row['path'] );
			if ( file_exists( $path ) ) {
				// assume it's the same file and skip re-download
				continue;
			}

			Process::create( \WP_CLI\Utils\esc_cmd( 'curl -sSL %s > %s', $row['url'], $path ) )->run_check();
		}
	}
);

$steps->Given( '/^save (STDOUT|STDERR) ([\'].+[^\'])?as \{(\w+)\}$/',
	function ( $world, $stream, $output_filter, $key ) {

		$stream = strtolower( $stream );

		if ( $output_filter ) {
			$output_filter = '/' . trim( str_replace( '%s', '(.+[^\b])', $output_filter ), "' " ) . '/';
			if ( false !== preg_match( $output_filter, $world->result->$stream, $matches ) )
				$output = array_pop( $matches );
			else
				$output = '';
		} else {
			$output = $world->result->$stream;
		}
		$world->variables[ $key ] = trim( $output, "\n" );
	}
);

$steps->Given( '/^a new Phar(?: with version "([^"]+)")$/',
	function ( $world, $version ) {
		$world->build_phar( $version );
	}
);

$steps->Given( '/^save the (.+) file ([\'].+[^\'])?as \{(\w+)\}$/',
	function ( $world, $filepath, $output_filter, $key ) {
		$full_file = file_get_contents( $world->replace_variables( $filepath ) );

		if ( $output_filter ) {
			$output_filter = '/' . trim( str_replace( '%s', '(.+[^\b])', $output_filter ), "' " ) . '/';
			if ( false !== preg_match( $output_filter, $full_file, $matches ) )
				$output = array_pop( $matches );
			else
				$output = '';
		} else {
			$output = $full_file;
		}
		$world->variables[ $key ] = trim( $output, "\n" );
	}
);

$steps->Given('/^a misconfigured WP_CONTENT_DIR constant directory$/',
	function($world) {
		$wp_config_path = $world->variables['RUN_DIR'] . "/wp-config.php";

		$wp_config_code = file_get_contents( $wp_config_path );

		$world->add_line_to_wp_config( $wp_config_code,
			"define( 'WP_CONTENT_DIR', '' );" );

		file_put_contents( $wp_config_path, $wp_config_code );
	}
);