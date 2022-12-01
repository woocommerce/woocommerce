const api = require( './api' );

const deleteAllProducts = async () => {
	console.log( 'Deleting all products...' );

	let products,
		page = 1;

	while (
		( products = await api.get.products( { per_page: 100, page: page++ } ) )
			.length > 0
	) {
		const ids = products.map( ( { id } ) => id );
		await api.deletePost.product( ids );
	}

	console.log( 'Done.' );
};

const deleteAllProductCategories = async () => {
	console.log( 'Deleting all product categories...' );

	let categories,
		page = 1;

	// Exclude "Uncategorized" as it cannot be deleted
	while (
		( categories = (
			await api.get.productCategories( { per_page: 100, page: page++ } )
		 ).filter( ( { slug } ) => slug !== 'uncategorized' ) ).length > 0
	) {
		const ids = categories.map( ( { id } ) => id );
		await api.deletePost.productCategories( ids );
	}

	console.log( 'Done.' );
};

const deleteAllProductTags = async () => {
	console.log( 'Deleting all product tags...' );

	let tags,
		page = 1;

	while (
		( tags = await api.get.productTags( {
			per_page: 100,
			page: page++,
		} ) ).length > 0
	) {
		const ids = tags.map( ( { id } ) => id );
		await api.deletePost.productTags( ids );
	}

	console.log( 'Done.' );
};

const deleteAllOrders = async () => {
	console.log( 'Deleting all orders...' );

	let orders,
		page = 1;

	while (
		( orders = await api.get.orders( { per_page: 100, page: page++ } ) )
			.length > 0
	) {
		const ids = orders.map( ( { id } ) => id );
		await api.deletePost.order( ids );
	}

	console.log( 'Done.' );
};

/**
 * Reset the test site. Useful when running E2E tests on a hosted test site to reset it to a somewhat pristine state prior to running tests.
 *
 * @param {string} cKey Consumer key
 * @param {string} cSecret Consumer secret
 */
const reset = async ( cKey, cSecret ) => {
	console.log( '--------------------------' );
	console.log( 'Resetting test site...' );
	console.log( '--------------------------' );

	api.constructWith( cKey, cSecret );

	await deleteAllProducts();
	await deleteAllProductCategories();
	await deleteAllProductTags();
	await deleteAllOrders();
};

module.exports = {
	reset,
};
