const axios = require( 'axios' ).default;

const getPreviousTwoVersions = async () => {
	const latestThreeMinorVersions = [];
	const latestThreePatchVersions = [];

	const response = await axios
		.get( 'https://api.wordpress.org/core/version-check/1.7/' )
		.catch( ( error ) => {
			console.log( error.toJSON() );
			throw new Error( error.message );
		} );

	const allVersions = response.data.offers.map( ( { version } ) => version );

	for ( const version of allVersions ) {
		const this_minorVersion = version.match( /^\d+.\d+/ )[ 0 ];

		if ( latestThreeMinorVersions.length === 3 ) {
			break;
		}

		if ( latestThreeMinorVersions.includes( this_minorVersion ) ) {
			continue;
		}

		latestThreeMinorVersions.push( this_minorVersion );
	}

	for ( const xy of latestThreeMinorVersions ) {
		const this_patchVersion = allVersions.find( ( version ) =>
			version.startsWith( xy )
		);

		latestThreePatchVersions.push( this_patchVersion );
	}

	// Get only the previous 2 versions
	const prev_two = latestThreePatchVersions.slice( -2 );

	const matrix = {
		version: [
			{
				number: prev_two[ 0 ],
				description: 'WP Latest-1',
				env_description: 'wp-latest-1',
			},
			{
				number: prev_two[ 1 ],
				description: 'WP Latest-2',
				env_description: 'wp-latest-2',
			},
		],
	};

	return matrix;
};

module.exports = { getPreviousTwoVersions };
