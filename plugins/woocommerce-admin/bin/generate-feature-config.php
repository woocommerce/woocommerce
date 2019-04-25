<?php
/**
 * Generates an array of feature flags, based on the config used by the client application.
 *
 * @package WooCommerce Admin
 */

$phase = isset( $_SERVER['WC_ADMIN_PHASE'] ) ? $_SERVER['WC_ADMIN_PHASE'] : ''; // WPCS: sanitization ok.
if ( ! in_array( $phase, array( 'development', 'plugin', 'core' ), true ) ) {
	$phase = 'development';
}
$config_json = file_get_contents( 'config/' . $phase . '.json' );
$config      = json_decode( $config_json );


if ( 'core' !== $phase ) {
	$write  = "<?php\n";
	$write .= "// WARNING: Do not directly edit this file.\n";
	$write .= "// This file is auto-generated as part of the build process and things may break.\n";
	$write .= "function wc_admin_get_feature_config() {\n";
	$write .= "\treturn array(\n";
	foreach ( $config->features as $feature => $bool ) {
		$write .= "\t\t'{$feature}' => " . ( $bool ? 'true' : 'false' ) . ",\n";
	}
	$write .= "\t);\n";
	$write .= "}\n";

	$config_file = fopen( 'includes/feature-config.php', 'w' );
} else {
	$write = "\t\t\tarray(\n";
	foreach ( $config->features as $feature => $bool ) {
		if ( true === $bool ) {
			$write .= "\t\t\t\t'{$feature}',\n";
		}
	}
	$write .= "\t\t\t)";

	if ( ! is_dir( './dist' ) && ! @mkdir( './dist' ) ) {
		echo "Run `npm run clean` to wipe artifacts.\n\n";
		exit( 1 );
	}

	$config_file = fopen( 'dist/feature-config-core.php', 'w' );
}

fwrite( $config_file, $write );
fclose( $config_file );
