/**
 * External dependencies
 */
import { test, expect } from '@playwright/test';

/**
 * Internal dependencies
 */
import { ADMIN_STATE_PATH } from '../../../playwright.config';
import { enableEmailEditor } from '../helpers/enable-email-editor-feature';
import { clearTemplateHtmlOverride } from './helpers/test-helper-plugin';
import { seedWooEmailPost, getWooEmailMeta } from './helpers/seed-woo-email';
import {
	simulateCoreBump,
	triggerDetectionSweep,
} from './helpers/simulate-plugin-update';
import { assertNoLeakedFixtureState } from './helpers/leaked-state-checks';
import { STATUS, META_KEYS } from './helpers/classifications';

const OLD_HTML =
	'<!-- wp:paragraph --><p>OLD CANONICAL</p><!-- /wp:paragraph -->';

test.describe( 'Update propagation — round-trip and idempotency', () => {
	test.use( { storageState: ADMIN_STATE_PATH } );

	test.beforeAll( async ( { baseURL } ) => {
		await enableEmailEditor( baseURL! );
	} );

	test.afterEach( async () => {
		await assertNoLeakedFixtureState();
	} );

	test( 'Auto-apply round-trip: uncustomized post returns to in_sync', async () => {
		await simulateCoreBump( 'new_order', OLD_HTML );
		const postId = await seedWooEmailPost( {
			emailId: 'new_order',
			postContent: OLD_HTML,
			storedSourceHash: 'AUTO_CURRENT',
			status: STATUS.IN_SYNC,
		} );
		await clearTemplateHtmlOverride();

		await triggerDetectionSweep();

		const meta = await getWooEmailMeta( postId );
		expect( meta[ META_KEYS.STATUS ]?.[ 0 ] ).toBe( STATUS.IN_SYNC );
	} );

	test( 'Selective apply round-trip: edit, bump, apply → in_sync', async ( {
		request,
	} ) => {
		const oldHtml =
			'<!-- wp:paragraph --><p>OLD A</p><!-- /wp:paragraph --><!-- wp:paragraph --><p>OLD B</p><!-- /wp:paragraph -->';

		await simulateCoreBump( 'new_order', oldHtml );
		const postId = await seedWooEmailPost( {
			emailId: 'new_order',
			postContent: oldHtml.replace( 'OLD B', 'MERCHANT B' ),
			storedSourceHash: 'AUTO_CURRENT',
			status: STATUS.IN_SYNC,
		} );
		await clearTemplateHtmlOverride();
		await triggerDetectionSweep();

		let meta = await getWooEmailMeta( postId );
		expect( meta[ META_KEYS.STATUS ]?.[ 0 ] ).toBe(
			STATUS.CORE_UPDATED_CUSTOMIZED
		);

		const apply = await request.post(
			`/wp-json/woocommerce-email-editor/v1/emails/${ postId }/apply`,
			{ data: { choices: [] } }
		);
		expect( apply.ok() ).toBeTruthy();

		meta = await getWooEmailMeta( postId );
		expect( meta[ META_KEYS.STATUS ]?.[ 0 ] ).toBe( STATUS.IN_SYNC );
	} );

	test( 'Reset round-trip: customized → reset → in_sync', async ( {
		request,
	} ) => {
		const customized =
			'<!-- wp:paragraph --><p>MERCHANT CUSTOM</p><!-- /wp:paragraph -->';
		const postId = await seedWooEmailPost( {
			emailId: 'new_order',
			postContent: customized,
			storedSourceHash: 'AUTO_CURRENT',
			status: STATUS.IN_SYNC,
		} );

		const reset = await request.post(
			`/wp-json/woocommerce-email-editor/v1/emails/${ postId }/reset`
		);
		expect( reset.ok() ).toBeTruthy();

		const meta = await getWooEmailMeta( postId );
		expect( meta[ META_KEYS.STATUS ]?.[ 0 ] ).toBe( STATUS.IN_SYNC );
	} );

	test( 'Detection sweep is idempotent: second run touches zero posts', async () => {
		await simulateCoreBump( 'new_order', OLD_HTML );
		const postId = await seedWooEmailPost( {
			emailId: 'new_order',
			postContent: OLD_HTML.replace( 'OLD CANONICAL', 'MERCHANT EDIT' ),
			storedSourceHash: 'AUTO_CURRENT',
			status: STATUS.IN_SYNC,
		} );
		await clearTemplateHtmlOverride();

		await triggerDetectionSweep();
		const metaBefore = await getWooEmailMeta( postId );

		const sweep2 = await triggerDetectionSweep();
		const metaAfter = await getWooEmailMeta( postId );

		expect( sweep2.classifications[ postId ] ).toBe(
			metaBefore[ META_KEYS.STATUS ]?.[ 0 ]
		);
		expect( metaAfter ).toEqual( metaBefore );
	} );
} );
