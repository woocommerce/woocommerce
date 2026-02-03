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

/**
 * Interface for test page fixture data.
 */
interface TestPage {
	title: string;
	slug: string;
}

/**
 * Interface for test post fixture data.
 */
interface TestPost {
	title: string;
	slug: string;
}

/**
 * REST API response interface.
 */
export interface RestApiResponse {
	data: unknown;
	statusCode: number;
}

/**
 * Type for the REST API client.
 */
export interface RestApiClient {
	get: (
		path: string,
		params?: Record< string, unknown >
	) => Promise< RestApiResponse >;
	post: (
		path: string,
		data?: Record< string, unknown >
	) => Promise< RestApiResponse >;
	put: (
		path: string,
		data?: Record< string, unknown >
	) => Promise< RestApiResponse >;
	delete: (
		path: string,
		params?: Record< string, unknown >
	) => Promise< RestApiResponse >;
}

/**
 * Custom fixture types for extended test.
 */
interface CustomFixtures {
	restApi: RestApiClient;
	testPageTitlePrefix: string;
	testPage: TestPage;
	testPostTitlePrefix: string;
	testPost: TestPost;
}

/**
 * WordPress page response from REST API.
 */
interface WpPageResponse {
	id: number;
}

/**
 * WordPress post response from REST API.
 */
interface WpPostResponse {
	id: number;
}

export const test = baseTest.extend< CustomFixtures >( {
	restApi: async (
		{ baseURL }: { baseURL: string | undefined },
		use
	): Promise< void > => {
		await use(
			createClient( baseURL as string, {
				type: 'basic',
				username: admin.username,
				password: admin.password,
			} ) as RestApiClient
		);
	},

	testPageTitlePrefix: [ '', { option: true } ],

	testPage: async (
		{ restApi, testPageTitlePrefix },
		use
	): Promise< void > => {
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

		for ( const page of ( await pages.data ) as WpPageResponse[] ) {
			await restApi.delete( `${ WP_API_PATH }/pages/${ page.id }`, {
				data: {
					force: true,
				},
			} );
		}
	},

	testPostTitlePrefix: [ '', { option: true } ],

	testPost: async (
		{ restApi, testPostTitlePrefix },
		use
	): Promise< void > => {
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

		for ( const post of ( await posts.data ) as WpPostResponse[] ) {
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

/**
 * Empty storage state for guest (unauthenticated) users.
 */
export const guestFile = { cookies: [], origins: [] };

/**
 * Test tags for categorizing and filtering tests.
 */
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
} as const;

/**
 * Type for test tags.
 */
export type TestTags = typeof tags;
