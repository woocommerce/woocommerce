const base = require( '@playwright/test' );
const wcApi = require( '@woocommerce/woocommerce-rest-api' ).default;
const { admin } = require( '../test-data/data' );
const { random } = require( '../utils/helpers' );

exports.test = base.test.extend( {
	api: async ( { baseURL }, use ) => {
		const api = new wcApi( {
			url: baseURL,
			consumerKey: process.env.CONSUMER_KEY,
			consumerSecret: process.env.CONSUMER_SECRET,
			version: 'wc/v3',
			axiosConfig: {
				// allow 404s, so we can check if a resource was deleted without try/catch
				validateStatus( status ) {
					return ( status >= 200 && status < 300 ) || status === 404;
				},
			},
		} );

		await use( api );
	},
	wcAdminApi: async ( { baseURL }, use ) => {
		const wcAdminApi = new wcApi( {
			url: baseURL,
			consumerKey: process.env.CONSUMER_KEY,
			consumerSecret: process.env.CONSUMER_SECRET,
			version: 'wc-admin', // Use wc-admin namespace
		} );

		await use( wcAdminApi );
	},

	wpApi: async ( { baseURL }, use ) => {
		const wpApi = await base.request.newContext( {
			baseURL,
			extraHTTPHeaders: {
				Authorization: `Basic ${ Buffer.from(
					`${ admin.username }:${ admin.password }`
				).toString( 'base64' ) }`,
				cookie: '',
			},
		} );

		await use( wpApi );
	},

	testPageTitlePrefix: [ '', { option: true } ],

	testPage: async ( { wpApi, testPageTitlePrefix }, use ) => {
		const pageTitle = `${ testPageTitlePrefix } Page ${ random() }`;
		const pageSlug = pageTitle.replace( / /gi, '-' ).toLowerCase();

		await use( { title: pageTitle, slug: pageSlug } );

		// Cleanup
		const pages = await wpApi.get(
			`/wp-json/wp/v2/pages?slug=${ pageSlug }`,
			{
				data: {
					_fields: [ 'id' ],
				},
				failOnStatusCode: false,
			}
		);

		for ( const page of await pages.json() ) {
			console.log( `Deleting page ${ page.id }` );
			await wpApi.delete( `/wp-json/wp/v2/pages/${ page.id }`, {
				data: {
					force: true,
				},
			} );
		}
	},
} );

exports.expect = base.expect;
