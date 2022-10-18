<?php
/**
 * Generates an array of feature flags, based on the config used by the client application.
 *
 * @package WooCommerce\Admin
 */

/**
 * Get phase for feature flags
 * - development: All features should be enabled in development.
 * - core: Stable features for WooCommerce core merge.
 */

$phase = getenv( 'WC_ADMIN_PHASE' );

if ( ! in_array( $phase, array( 'development', 'core' ), true ) ) {
	$phase = 'core'; // Default to core when running `pnpm run build`.
}
$config_json = file_get_contents( __DIR__ . '/../client/admin/config/' . $phase . '.json' );
$config      = json_decode( $config_json );

$write  = "<?php\n";
$write .= "// WARNING: Do not directly edit this file.\n";
$write .= "// This file is auto-generated as part of the build process and things may break.\n";
$write .= "if ( ! function_exists( 'wc_admin_get_feature_config' ) ) {\n";
$write .= "\tfunction wc_admin_get_feature_config() {\n";
$write .= "\t\treturn array(\n";
foreach ( $config->features as $feature => $bool ) {
	$write .= "\t\t\t'{$feature}' => " . ( $bool ? 'true' : 'false' ) . ",\n";
}
$write .= "\t\t);\n";
$write .= "\t}\n";
$write .= "}\n";

$config_file = fopen( __DIR__ . '/../includes/react-admin/feature-config.php', 'w' );

fwrite( $config_file, $write );
fclose( $config_file );
