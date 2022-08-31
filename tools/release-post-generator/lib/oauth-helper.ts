/**
 * External dependencies
 */
import express from 'express';
import fetch from 'node-fetch';
import open from 'open';
import FormData from 'form-data';

const getCode = (
	clientId: string,
	clientSecret: string,
	siteId: string,
	scope: string
) => {
	return new Promise( async ( resolve ) => {
		open(
			`https://public-api.wordpress.com/oauth2/authorize?client_id=${ clientId }&client_secret=${ clientSecret }&redirect_uri=http://localhost:3000/oauth&scope=${ scope }&blog=${ siteId }&response_type=code`
		);

		const app = express();
		const server = await app.listen( 3000 );

		app.get( '/oauth', ( req, res ) => {
			resolve( req.query.code );
			res.end( '' );
			server.close();
		} );
	} );
};

export const getWordpressComAuthToken = async (
	clientId: string,
	clientSecret: string,
	siteId: string,
	scope: string
) => {
	const code = await getCode( clientId, clientSecret, siteId, scope );
	const data = new FormData();

	data.append( 'client_id', clientId );
	data.append( 'client_secret', clientSecret );
	data.append( 'code', code );
	data.append( 'grant_type', 'authorization_code' );
	data.append( 'redirect_uri', 'http://localhost:3000/oauth' );

	const res = await fetch( 'https://public-api.wordpress.com/oauth2/token', {
		method: 'POST',
		headers: {
			Authorization:
				'Basic ' +
				Buffer.from( clientId + ':' + clientSecret ).toString(
					'base64'
				),
		},
		body: data,
	} );

	const { access_token } = await res.json();
	return access_token;
};
