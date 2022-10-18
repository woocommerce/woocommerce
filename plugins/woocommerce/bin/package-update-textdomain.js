const wpTextdomain = require( 'wp-textdomain' );

wpTextdomain( 'packages/**/*.php', {
	domain: 'woocommerce',
	fix: true,
	missingDomain: true,
	variableDomain: true,
	keywords: [
		'__:1,2d',
		'_e:1,2d',
		'_x:1,2c,3d',
		'esc_html__:1,2d',
		'esc_html_e:1,2d',
		'esc_html_x:1,2c,3d',
		'esc_attr__:1,2d',
		'esc_attr_e:1,2d',
		'esc_attr_x:1,2c,3d',
		'_ex:1,2c,3d',
		'_n:1,2,4d',
		'_nx:1,2,4c,5d',
		'_n_noop:1,2,3d',
		'_nx_noop:1,2,3c,4d',
		'wp_set_script_translations:1,2d,3'
	],
} );
