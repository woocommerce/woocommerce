const wpTextdomain = require( 'wp-textdomain' );

wpTextdomain( 'packages/**/*.php', {
	domain: 'woocommerce',
	fix: true,
	missingDomain: true,
	variableDomain: true,
} );
