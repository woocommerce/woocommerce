module.exports = ( { core } ) => {
	const path = require( 'path' );
	const { FEAT_SLUG, PLUGIN_SLUG, TEST_TYPE, REPORTS_HOME } = process.env;
	const relativeDir = path.normalize(
		`public/daily/${ FEAT_SLUG || PLUGIN_SLUG }/${ TEST_TYPE }`
	);

	bucketURL = `${ REPORTS_HOME }/${ relativeDir }`;

	core.setOutput( 'bucket-url', bucketURL );
};
