const axios = require( 'axios' ).default;

const getPreviousTwoVersions = async () => {
	const response = await axios
		.get( 'http://api.wordpress.org/core/stable-check/1.0/' )
		.catch( ( error ) => {
			console.log( error.toJSON() );
			throw new Error( error.message );
		} );

	const body = response.data;
	const allVersions = Object.keys( body );
	const previousStableVersions = allVersions
		.filter( ( version ) => body[ version ] === 'outdated' )
		.sort()
		.reverse();
	const latestMajorAndMinorNumbers = allVersions
		.find( ( version ) => body[ version ] === 'latest' )
		.match( /^\d+.\d+/ )[ 0 ];

	const latestMinus1 = previousStableVersions.find(
		( version ) => ! version.startsWith( latestMajorAndMinorNumbers )
	);

	const latestMinus1MajorAndMinorNumbers = latestMinus1.match(
		/^\d+.\d+/
	)[ 0 ];

	const latestMinus2 = previousStableVersions.find(
		( version ) =>
			! (
				version.startsWith( latestMajorAndMinorNumbers ) ||
				version.startsWith( latestMinus1MajorAndMinorNumbers )
			)
	);

	const matrix = {
		version: [
			{
				number: latestMinus1,
				description: 'WP Latest-1',
				env_description: 'wp-latest-1',
			},
			{
				number: latestMinus2,
				description: 'WP Latest-2',
				env_description: 'wp-latest-2',
			},
		],
	};

	return matrix;
};

module.exports = { getPreviousTwoVersions };
