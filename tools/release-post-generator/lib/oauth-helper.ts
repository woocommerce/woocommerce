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
	redirectUri: string,
	scope: string
) => {
	return new Promise( async ( resolve, reject ) => {
		open(
			`https://public-api.wordpress.com/oauth2/authorize?client_id=${ clientId }&client_secret=${ clientSecret }&redirect_uri=${ redirectUri }&scope=${ scope }&blog=${ siteId }&response_type=code`
		);

		const uri = new URL( redirectUri );

		const app = express();
		const server = await app.listen( uri.port );

		app.get( uri.pathname, ( req, res ) => {
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
	redirectUri: string,
	scope: string
) => {
	const code = await getCode(
		clientId,
		clientSecret,
		siteId,
		redirectUri,
		scope
	);
	const data = new FormData();

	data.append( 'client_id', clientId );
	data.append( 'client_secret', clientSecret );
	data.append( 'code', code );
	data.append( 'grant_type', 'authorization_code' );
	data.append( 'redirect_uri', redirectUri );

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

	if ( res.status !== 200 ) {
		const text = await res.text();
		throw new Error( `Error getting auth token ${ text }` );
	}

	const { access_token } = await res.json();
	return access_token;
};
