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
	const latestMinorVersion = allVersions
		.find( ( version ) => body[ version ] === 'latest' )
		.match( /^\d+.\d+/ )[ 0 ];

	const prevTwo = [];

	for ( let thisVersion of previousStableVersions ) {
		if ( thisVersion.startsWith( latestMinorVersion ) ) {
			continue;
		}

		prevTwo.push( thisVersion );

		if ( prevTwo.length === 2 ) {
			break;
		}
	}

	const matrix = {
		version: [
			{
				number: prevTwo[ 0 ],
				description: 'WP Latest-1',
				env_description: 'wp-latest-1',
			},
			{
				number: prevTwo[ 1 ],
				description: 'WP Latest-2',
				env_description: 'wp-latest-2',
			},
		],
	};

	return matrix;
};

module.exports = { getPreviousTwoVersions };
