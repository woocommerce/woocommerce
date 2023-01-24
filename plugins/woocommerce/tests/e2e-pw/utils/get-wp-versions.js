const axios = require( 'axios' ).default;

module.exports = async () => {
	const response = await axios
		.get( 'https://api.wordpress.org/core/version-check/1.7/' )
		.catch( ( error ) => {
			console.log( error.toJSON() );
			throw new Error( error.message );
		} );

	const allVersions = response.data.offers.map( ( { version } ) => version );

	// Get the three latest X.Y versions
	const latestThree_xy = [];
	for ( const version of allVersions ) {
		const this_xy = version.match( /^\d+.\d+/ )[ 0 ];

		if ( latestThree_xy.length === 3 ) {
			break;
		}

		if ( latestThree_xy.includes( this_xy ) ) {
			continue;
		}

		latestThree_xy.push( this_xy );
	}

	// Get the three latest X.Y.Z versions
	const latestThree_xyz = [];
	for ( const xy of latestThree_xy ) {
		const this_xyz = allVersions.find( ( version ) =>
			version.startsWith( xy )
		);

		latestThree_xyz.push( this_xyz );
	}

	// Get only the previous 2 versions
	const prev_two = latestThree_xyz.slice( -2 );

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
