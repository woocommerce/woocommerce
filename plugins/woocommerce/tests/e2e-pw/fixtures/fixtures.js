/**
 * External dependencies
 */
import {
	test as baseTest,
	expect as baseExpect,
	request as baseRequest,
} from '@playwright/test';
import { createClient, WP_API_PATH } from '@woocommerce/e2e-utils-playwright';

/**
 * Internal dependencies
 */
import { random } from '../utils/helpers';
import { admin } from '../test-data/data';

export const test = baseTest.extend( {
	restApi: async ( { baseURL }, use ) => {
		await use(
			createClient( baseURL, {
				type: 'basic',
				username: admin.username,
				password: admin.password,
			} )
		);
	},

	testPageTitlePrefix: [ '', { option: true } ],

	testPage: async ( { restApi, testPageTitlePrefix }, use ) => {
		const pageTitle = `${ testPageTitlePrefix } Page ${ random() }`.trim();
		const pageSlug = pageTitle.replace( / /gi, '-' ).toLowerCase();

		await use( { title: pageTitle, slug: pageSlug } );

		// Cleanup
		const pages = await restApi.get(
			`${ WP_API_PATH }/pages?slug=${ pageSlug }`,
			{
				data: {
					_fields: [ 'id' ],
				},
				failOnStatusCode: false,
			}
		);

		for ( const page of await pages.data ) {
			await restApi.delete( `${ WP_API_PATH }/pages/${ page.id }`, {
				data: {
					force: true,
				},
			} );
		}
	},

	testPostTitlePrefix: [ '', { option: true } ],

	testPost: async ( { restApi, testPostTitlePrefix }, use ) => {
		const postTitle = `${ testPostTitlePrefix } Post ${ random() }`.trim();
		const postSlug = postTitle.replace( / /gi, '-' ).toLowerCase();

		await use( { title: postTitle, slug: postSlug } );

		// Cleanup
		const posts = await restApi.get(
			`${ WP_API_PATH }/posts?slug=${ postSlug }`,
			{
				data: {
					_fields: [ 'id' ],
				},
				failOnStatusCode: false,
			}
		);

		for ( const post of await posts.data ) {
			await restApi.delete( `${ WP_API_PATH }/posts/${ post.id }`, {
				data: {
					force: true,
				},
			} );
		}
	},
} );

export const expect = baseExpect;
export const request = baseRequest;
export const tags = {
	GUTENBERG: '@gutenberg',
	SERVICES: '@services',
	PAYMENTS: '@payments',
	HPOS: '@hpos',
	SKIP_ON_EXTERNAL_ENV: '@skip-on-external-env',
	SKIP_ON_WPCOM: '@skip-on-wpcom',
	SKIP_ON_PRESSABLE: '@skip-on-pressable',
	COULD_BE_LOWER_LEVEL_TEST: '@could-be-lower-level-test',
	NON_CRITICAL: '@non-critical',
	TO_BE_REMOVED: '@to-be-removed',
	NOT_E2E: '@not-e2e',
	WP_CORE: '@wp-core',
};
