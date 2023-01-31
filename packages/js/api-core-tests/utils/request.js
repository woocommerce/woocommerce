require( 'dotenv' ).config();
const { USER_KEY, USER_SECRET } = process.env;
// eslint-disable-next-line
const request = require( 'supertest' )( API_PATH );

/**
 * Make a GET request.
 *
 * @param {string} requestPath The path of the request.
 * @param {Object} queryString Optional. An object of one or more `key: value` query string parameters.
 * @return {Response} The response object.
 */
const getRequest = async ( requestPath, queryString = {} ) => {
	const response = await request
		.get( requestPath )
		.set( 'Accept', 'application/json' )
		.query( queryString )
		.auth( USER_KEY, USER_SECRET );
	return response;
};

/**
 * Make a POST request.
 *
 * @param {string} requestPath The path of the request.
 * @param {Object} requestBody The body of the request to submit.
 * @return {Response} The response object.
 */
const postRequest = async ( requestPath, requestBody ) => {
	const response = await request
		.post( requestPath )
		.send( requestBody )
		.set( 'Accept', 'application/json' )
		.auth( USER_KEY, USER_SECRET );
	return response;
};

/**
 * Make a PUT request.
 *
 * @param {string} requestPath The path of the request.
 * @param {Object} requestBody The body of the request to submit.
 * @return {Request} The request object.
 */
const putRequest = async ( requestPath, requestBody ) => {
	const response = await request
		.put( requestPath )
		.send( requestBody )
		.set( 'Accept', 'application/json' )
		.auth( USER_KEY, USER_SECRET );
	return response;
};

/**
 * Make a DELETE request, optionally deleting the resource permanently.
 *
 * @param {string}  requestPath       The path of the request.
 * @param {boolean} deletePermanently Flag to permanently delete the resource.
 * @return {Response} The response object.
 */
const deleteRequest = async ( requestPath, deletePermanently = false ) => {
	const requestBody = deletePermanently ? { force: true } : {};
	const response = await request
		.delete( requestPath )
		.set( 'Accept', 'application/json' )
		.send( requestBody )
		.auth( USER_KEY, USER_SECRET );
	return response;
};

module.exports = { getRequest, postRequest, putRequest, deleteRequest };
