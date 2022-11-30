const { api } = require( './utils' );

/**
 * Clear the test environment from all unwanted data.
 */
const reset = async () => {
	console.log( 'Deleting all existing products...' );
	const products = await api.get.products( { per_page: 100 } );
	console.log(
		`TEST: products: ${ JSON.stringify(
			products.map( ( { id } ) => id ),
			null,
			2
		) }`
	);
};

module.exports = {
	reset,
};
