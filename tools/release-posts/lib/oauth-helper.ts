/**
 * External dependencies
 */
import express from 'express';
import fetch from 'node-fetch';
import open from 'open';
import FormData from 'form-data';

/**
 * Internal dependencies
 */
import { renderTemplate } from './render-template';

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
		let responded = false;

		const handle = setTimeout( () => {
			if ( ! responded ) {
				reject(
					'Could not perform oauth redirect, please check your env settings.'
				);
			}
		}, 30000 );

		app.get( uri.pathname, async ( req, res ) => {
			responded = true;
			resolve( req.query.code );
			const response = await renderTemplate( 'oauth.ejs', {} );
			res.end( response );
			clearTimeout( handle );
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
	try {
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

		const res = await fetch(
			'https://public-api.wordpress.com/oauth2/token',
			{
				method: 'POST',
				headers: {
					Authorization:
						'Basic ' +
						Buffer.from( clientId + ':' + clientSecret ).toString(
							'base64'
						),
				},
				body: data,
			}
		);

		if ( res.status !== 200 ) {
			const text = await res.text();
			throw new Error( `Error getting auth token ${ text }` );
		}

		const { access_token } = await res.json();
		return access_token;
	} catch ( e ) {
		if ( e instanceof Error ) {
			throw new Error( e.message );
		}
	}
};
