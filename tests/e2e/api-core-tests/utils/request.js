require('dotenv').config();
const { USERNAME, PASSWORD } = process.env;
const request = require('supertest')( API_PATH );

/**
 * Make a GET request.
 *
 * @param {string} requestPath The path of the request.
 * @param {object} queryString Optional. An object of one or more `key: value` query string parameters.
 * @returns {Response}
 */
const getRequest = async ( requestPath, queryString = {} ) => {
	const response = await request
		.get( requestPath )
		.set( 'Accept', 'application/json' )
		.query( queryString )
		.auth( USERNAME, PASSWORD );
	return response;
}

/**
 * Make a POST request.
 *
 * @param {string} requestPath The path of the request.
 * @param {object} requestBody The body of the request to submit.
 * @returns {Response}
 */
const postRequest = async ( requestPath, requestBody ) => {
	const response = await request
		.post( requestPath )
		.send( requestBody )
		.set( 'Accept', 'application/json' )
		.auth( USERNAME, PASSWORD );
	return response;
}

/**
 * Make a PUT request.
 *
 * @param {string} requestPath The path of the request.
 * @param {object} requestBody The body of the request to submit.
 * @returns {Request}
 */
const putRequest = async ( requestPath, requestBody ) => {
	const response = await request
		.put( requestPath )
		.send( requestBody )
		.set( 'Accept', 'application/json' )
		.auth( USERNAME, PASSWORD );
	return response;
}

/**
 * Make a DELETE request, optionally deleting the resource permanently.
 *
 * @param {string} requestPath The path of the request.
 * @param {boolean} deletePermanently Flag to permanently delete the resource.
 * @returns {Response}
 */
const deleteRequest = async ( requestPath, deletePermanently = false ) => {
	const requestBody = deletePermanently ? { force: true } : {}
	const response = await request
		.delete( requestPath )
		.set( 'Accept', 'application/json' )
		.send( requestBody )
		.auth( USERNAME, PASSWORD );
	return response;
}

module.exports = { getRequest, postRequest, putRequest, deleteRequest }
