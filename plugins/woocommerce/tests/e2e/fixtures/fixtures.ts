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
		if ( ! baseURL ) {
			throw new Error(
				'Playwright baseURL is required to use the restApi fixture.'
			);
		}

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
	PAYPAL: '@paypal',
} as const;

/**
 * Playwright test locks for specs that write the same global options.
 * Specs that share a lock never run at the same time, in any worker or project.
 */
export const locks = {
	// `woocommerce_analytics_scheduled_import`.
	ANALYTICS_IMPORT_MODE: 'analytics-import-mode',
	// `woocommerce_feature_block_email_editor_enabled` and
	// `woocommerce_feature_email_improvements_enabled`. One file's afterAll
	// turns a flag off while another file still needs it. Specs that assert
	// sent emails or the Email settings page also take it, since the flags
	// change both.
	EMAIL_FEATURE_FLAGS: 'email-feature-flags',
	// `woocommerce_pickup_location_settings` and `pickup_location_pickup_locations`.
	LOCAL_PICKUP: 'local-pickup',
	// The Back in Stock feature flag and `woocommerce_customer_stock_notifications_*`.
	// Concurrent writes of the same value make `e2e-options/update` return 400.
	STOCK_NOTIFICATIONS: 'stock-notifications',
	// The `taxonomy-product_cat`, `taxonomy-product_tag` and
	// `taxonomy-product_brand` templates. The Site Editor offers to add one only
	// while it does not exist.
	TAXONOMY_TEMPLATES: 'taxonomy-templates',
} as const;
