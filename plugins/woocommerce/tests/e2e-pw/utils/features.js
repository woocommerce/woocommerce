function is_enabled( feature ) {
	const phase = process.env.WC_ADMIN_PHASE;
	let config = 'development.json';
	if ( ![ 'core','developer' ].includes( phase ) ) {
		config = 'core.json';
	}

	const features = require( `../../../client/admin/config/${config}` ).features;
	return features[ feature ] && features[ feature ] === true;
}

module.exports = {
	is_enabled
}