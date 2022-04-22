const { getTestConfig } = require( './test-config' );
const { HTTPClientFactory } = require('@woocommerce/api');

/**
 * Uses the WordPress API to update the Ready page's status.
 *
 * @param {string} status | Status to update the page to. One of: publish, future, draft, pending, private
 */
const updateReadyPageStatus = async ( status ) => {
	const testConfig = getTestConfig();

	const apiUrl = testConfig.url;
	const wpPagesEndpoint = '/wp/v2/pages';
	const adminUsername = testConfig.users.admin.username ? testConfig.users.admin.username : 'admin';
	const adminPassword = testConfig.users.admin.password ? testConfig.users.admin.password : 'password';
	const client = HTTPClientFactory.build( apiUrl )
		.withBasicAuth( adminUsername, adminPassword )
		.create();

	// As the default status filter in the API is `publish`, we need to
	// filter based on the supplied status otherwise no results are returned.
	let statusFilter = 'publish';
	if ( 'publish' === status ) {
		// The page will be in a draft state, so we need to filter on that status
		statusFilter = 'draft';
	}

	const getPostsResponse = await client.get( `${wpPagesEndpoint}?search=ready&status=${statusFilter}` );
	if ( getPostsResponse.data && getPostsResponse.data.length > 0 ) {
		const pageId = getPostsResponse.data[0].id;
		// Update the page to the new status
		await client.post( `${wpPagesEndpoint}/${pageId}`, { 'status': status } );
	}
}

module.exports = updateReadyPageStatus;
