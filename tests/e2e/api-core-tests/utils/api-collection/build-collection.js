const fs = require('fs');
const { Collection, ItemGroup, Item } = require('postman-collection');
require('dotenv').config();
const {
	BASE_URL,
	USERNAME,
	PASSWORD,
	USE_INDEX_PERMALINKS
} = process.env;

/**
 * Build a Postman collection using the API testing objects.
 *
 * For more information on the Postman Collection SDK, see:
 * https://www.postmanlabs.com/postman-collection/index.html
 */

// Set up our empty collection
const postmanCollection = new Collection( {
	auth: {
		type: "basic",
		basic: [
			{
				key: "username",
				value: USERNAME,
				type: "string"
			},
			{
				key: "password",
				value: PASSWORD,
				type: "string"
			},
		]
	},
	info: {
	  name: 'WooCommerce API - v3'
	},
} );

// Get the API url
// Update the API path if the `USE_INDEX_PERMALINKS` flag is set
const useIndexPermalinks = ( USE_INDEX_PERMALINKS === 'true' );
let apiPath = `${BASE_URL}/wp-json/wc/v3`;
if ( useIndexPermalinks ) {
	apiPath = `${BASE_URL}/?rest_route=/wc/v3`;
}
// Set this here for use in `request.js`
global.API_PATH = `${apiPath}/`;

// Add the API path has a collection variable
postmanCollection.variables.add({
	id: 'apiBaseUrl',
	value: apiPath,
	type: 'string',
});

// Get the API request data
const resources = require('../../endpoints');
resourceKeys = Object.keys( resources );

// Add the requests to folders in the collection
for ( const key in resources ) {

	const folder = new ItemGroup( {
		name: resources[key].name,
		items: []
	} );

	for ( const endpoint in resources[key] ) {

		let api = resources[key][endpoint];

		// If there is no name defined, continue
		if ( !api.name ) {
			continue;
		}

		const request = new Item( {
			name: api.name,
			request: {
			  url: `{{apiBaseUrl}}/${api.path}`,
			  method: api.method,
			  body: {
				mode: 'raw',
				raw: JSON.stringify( api.payload ),
				options: {
					raw: {
						language: "json"
					}
				}
			  },
			},
		} );
		folder.items.add( request );
	}

	postmanCollection.items.add( folder ) ;
}

// We need to convert the collection to JSON so that it can be exported to a file
const collectionJSON = postmanCollection.toJSON();

// Create a colleciton.json file. It can be imported to postman
fs.writeFile('./collection.json', JSON.stringify( collectionJSON ), ( err ) => {
	if ( err ) {
	  console.log( err );
	}
	console.log('File saved!');
});
