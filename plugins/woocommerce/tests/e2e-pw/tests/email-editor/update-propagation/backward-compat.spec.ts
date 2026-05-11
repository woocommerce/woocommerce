/**
 * External dependencies
 */
import { test, expect } from '@playwright/test';
import { createClient } from '@woocommerce/e2e-utils-playwright';

/**
 * Internal dependencies
 */
import { ADMIN_STATE_PATH } from '../../../playwright.config';
import { admin } from '../../../test-data/data';
import { enableEmailEditor } from '../helpers/enable-email-editor-feature';
import {
	clearTemplateHtmlOverride,
	setTemplateHtmlOverride,
} from './helpers/test-helper-plugin';
import { seedWooEmailPost, getWooEmailMeta } from './helpers/seed-woo-email';
import {
	simulateCoreBump,
	triggerBackfill,
	triggerDetectionSweep,
} from './helpers/simulate-plugin-update';
import { attachTracksSpy } from './helpers/tracks-spy';
import { assertNoLeakedFixtureState } from './helpers/leaked-state-checks';
import {
	STATUS,
	META_KEYS,
	TRACKS_EVENTS,
	TEST_HELPER_API_BASE,
} from './helpers/classifications';

const BACKFILL_COMPLETE_OPTION =
	'woocommerce_email_template_sync_backfill_complete';

const OLD_HTML = '<!-- wp:paragraph --><p>OLD</p><!-- /wp:paragraph -->';

async function resetBackfillFence( baseURL: string ): Promise< void > {
	const client = createClient( baseURL, {
		type: 'basic',
		username: admin.username,
		password: admin.password,
		defaultHeaders: { 'X-Playwright': '1' },
	} );
	await client.post( `${ TEST_HELPER_API_BASE }/delete-option`, {
		option_name: BACKFILL_COMPLETE_OPTION,
	} );
}

test.describe( 'Update propagation — backward compatibility', () => {
	test.use( { storageState: ADMIN_STATE_PATH } );

	test.beforeAll( async ( { baseURL } ) => {
		await enableEmailEditor( baseURL! );
	} );

	test.beforeEach( async ( { baseURL } ) => {
		// RSM-145 stamps this option on fresh installs via woocommerce_newly_installed
		// to suppress backfill on greenfield environments. BC scenarios need a clean
		// "pre-RSM-137" environment, so clear the option-fence before each test.
		await resetBackfillFence( baseURL! );
	} );

	test.afterEach( async () => {
		await assertNoLeakedFixtureState();
	} );

	test( 'BC Case A — content matches current core, no stamp meta', async () => {
		const postId = await seedWooEmailPost( {
			emailId: 'new_order',
			stripStampMeta: true,
		} );

		const backfill = await triggerBackfill();
		expect( backfill.stamped ).toBeGreaterThanOrEqual( 1 );

		const meta = await getWooEmailMeta( postId );
		expect( meta[ META_KEYS.STATUS ]?.[ 0 ] ).toBe( STATUS.IN_SYNC );
		expect( meta[ META_KEYS.SOURCE_HASH ]?.[ 0 ] ).toBeTruthy();

		// Simulate a core bump by setting the override to a different canonical.
		await setTemplateHtmlOverride( 'new_order', OLD_HTML );
		await triggerDetectionSweep();
		await clearTemplateHtmlOverride();

		const metaAfter = await getWooEmailMeta( postId );
		expect( metaAfter[ META_KEYS.STATUS ]?.[ 0 ] ).toBe(
			STATUS.CORE_UPDATED_UNCUSTOMIZED
		);
	} );

	test( 'BC Case B — timestamps equal and content behind core', async ( {
		request,
	} ) => {
		const ts = '2024-01-01 12:00:00';

		const postId = await seedWooEmailPost( {
			emailId: 'new_order',
			postContent: OLD_HTML,
			postDateGmt: ts,
			postModifiedGmt: ts,
			stripStampMeta: true,
		} );

		const backfill = await triggerBackfill();
		expect( backfill.stamped ).toBeGreaterThanOrEqual( 1 );

		const meta = await getWooEmailMeta( postId );
		expect( meta[ META_KEYS.STATUS ]?.[ 0 ] ).toBe( STATUS.IN_SYNC );

		// Critical: the backfill rewrote post_content from OLD_HTML to current canonical.
		const postRes = await request.get(
			`/wp-json/wp/v2/woo_email/${ postId }?context=edit`
		);
		const post = await postRes.json();
		const content = post?.content?.raw ?? '';
		expect( content ).not.toContain( 'OLD' );
	} );

	test( '@pr BC Case C — customized post content preserved (critical safety)', async ( {
		request,
	} ) => {
		const customized =
			'<!-- wp:paragraph --><p>MERCHANT CUSTOM 1234</p><!-- /wp:paragraph -->';

		const postId = await seedWooEmailPost( {
			emailId: 'new_order',
			postContent: customized,
			postDateGmt: '2024-01-01 12:00:00',
			postModifiedGmt: '2024-06-15 09:00:00',
			stripStampMeta: true,
		} );

		const backfill = await triggerBackfill();
		expect( backfill.stamped ).toBeGreaterThanOrEqual( 1 );

		let meta = await getWooEmailMeta( postId );
		expect( meta[ META_KEYS.STATUS ]?.[ 0 ] ).toBe( STATUS.IN_SYNC );

		// CRITICAL: post content must be UNTOUCHED by backfill.
		const postRes = await request.get(
			`/wp-json/wp/v2/woo_email/${ postId }?context=edit`
		);
		const post = await postRes.json();
		expect( post?.content?.raw ?? '' ).toContain( 'MERCHANT CUSTOM 1234' );

		await setTemplateHtmlOverride( 'new_order', OLD_HTML );
		await triggerDetectionSweep();
		await clearTemplateHtmlOverride();

		meta = await getWooEmailMeta( postId );
		// Safety claim: classification is CUSTOMIZED, not UNCUSTOMIZED.
		expect( meta[ META_KEYS.STATUS ]?.[ 0 ] ).toBe(
			STATUS.CORE_UPDATED_CUSTOMIZED
		);

		const postRes2 = await request.get(
			`/wp-json/wp/v2/woo_email/${ postId }?context=edit`
		);
		const post2 = await postRes2.json();
		expect( post2?.content?.raw ?? '' ).toContain( 'MERCHANT CUSTOM 1234' );
	} );

	test( 'BC no mass-fire on first upgrade: zero _available, one _backfill_completed', async ( {
		page,
	} ) => {
		const spy = await attachTracksSpy( page );

		const emailIds = [
			'new_order',
			'cancelled_order',
			'failed_order',
			'customer_on_hold_order',
			'customer_processing_order',
			'customer_completed_order',
			'customer_refunded_order',
			'customer_invoice',
			'customer_note',
			'customer_reset_password',
			'customer_new_account',
		];
		for ( const id of emailIds ) {
			await seedWooEmailPost( {
				emailId: id,
				stripStampMeta: true,
			} );
		}

		const backfill = await triggerBackfill();
		expect( backfill.stamped ).toBeGreaterThanOrEqual( emailIds.length );

		await triggerDetectionSweep();

		await spy.expectNotFired( TRACKS_EVENTS.AVAILABLE );
		await spy.expectFired( TRACKS_EVENTS.BACKFILL_COMPLETED, 1 );
	} );

	test( 'BC migration is idempotent: second backfill is a no-op', async () => {
		const postId = await seedWooEmailPost( {
			emailId: 'new_order',
			stripStampMeta: true,
		} );

		const first = await triggerBackfill();
		expect( first.stamped ).toBeGreaterThanOrEqual( 1 );
		const metaAfterFirst = await getWooEmailMeta( postId );

		await triggerBackfill();
		const metaAfterSecond = await getWooEmailMeta( postId );

		expect( metaAfterSecond ).toEqual( metaAfterFirst );
	} );
} );
