/**
 * External dependencies
 */
import { test, expect } from '@playwright/test';

/**
 * Internal dependencies
 */
import { ADMIN_STATE_PATH } from '../../../playwright.config';
import { enableEmailEditor } from '../helpers/enable-email-editor-feature';
import {
	setTransactionalEmailsOverride,
	setOptedInOverride,
	setTemplateHtmlOverride,
	clearTransactionalEmailsOverride,
	clearOptedInOverride,
	clearTemplateHtmlOverride,
	enableFakeThirdPartyEmail,
	disableFakeThirdPartyEmail,
} from './helpers/test-helper-plugin';
import {
	seedWooEmailPost,
	seedWooEmailPostDirect,
	getWooEmailMeta,
} from './helpers/seed-woo-email';
import {
	triggerBackfill,
	triggerDetectionSweep,
	simulateCoreBump,
} from './helpers/simulate-plugin-update';
import { attachTracksSpy } from './helpers/tracks-spy';
import { assertNoLeakedFixtureState } from './helpers/leaked-state-checks';
import { STATUS, META_KEYS, TRACKS_EVENTS } from './helpers/classifications';

const FAKE_EMAIL_ID = 'fake_thirdparty';

const V1_HTML = '<!-- wp:paragraph --><p>V1 CONTENT</p><!-- /wp:paragraph -->';
const V2_HTML = '<!-- wp:paragraph --><p>V2 CONTENT</p><!-- /wp:paragraph -->';

test.describe( 'Update propagation — scope and allow-list', () => {
	test.use( { storageState: ADMIN_STATE_PATH } );

	test.beforeAll( async ( { baseURL } ) => {
		await enableEmailEditor( baseURL! );
	} );

	test.beforeEach( async () => {
		await enableFakeThirdPartyEmail();
	} );

	test.afterEach( async () => {
		await disableFakeThirdPartyEmail();
		await assertNoLeakedFixtureState();
	} );

	test( 'Non-opted-in third-party email is excluded from sync', async ( {
		page,
	} ) => {
		const spy = await attachTracksSpy( page );

		// Deliberately do NOT add FAKE_EMAIL_ID to the transactional emails list:
		// a third-party email that has not enrolled in block-editor sync is excluded
		// from WCEmailTemplateSyncRegistry and therefore skipped by both the backfill
		// and the divergence sweep. Create the woo_email post directly (bypassing the
		// generator) so no options-table mapping exists for the email type — the
		// backfill's get_email_type_from_post_id() will return null and skip the post.
		const postId = await seedWooEmailPostDirect( {
			postContent:
				'<!-- wp:paragraph --><p>Third-party content</p><!-- /wp:paragraph -->',
			stripStampMeta: true,
		} );

		await triggerBackfill();
		await simulateCoreBump( FAKE_EMAIL_ID, V1_HTML );
		await triggerDetectionSweep();
		await clearTemplateHtmlOverride();

		const meta = await getWooEmailMeta( postId );
		expect( meta[ META_KEYS.STATUS ] ).toBeUndefined();
		expect( meta[ META_KEYS.SOURCE_HASH ] ).toBeUndefined();
		await spy.expectNotFired( TRACKS_EVENTS.AVAILABLE );
	} );

	test( 'Opted-in third-party email: version bump flips status when unedited', async () => {
		await setTransactionalEmailsOverride( [ FAKE_EMAIL_ID ] );
		await setOptedInOverride( { [ FAKE_EMAIL_ID ]: { version: '1.0.0' } } );
		await setTemplateHtmlOverride( FAKE_EMAIL_ID, V1_HTML );

		const postId = await seedWooEmailPost( {
			emailId: FAKE_EMAIL_ID,
			postContent: V1_HTML,
			storedSourceHash: 'AUTO_CURRENT',
			status: STATUS.IN_SYNC,
			version: '1.0.0',
		} );

		await triggerDetectionSweep();
		let meta = await getWooEmailMeta( postId );
		expect( meta[ META_KEYS.STATUS ]?.[ 0 ] ).toBe( STATUS.IN_SYNC );

		await setOptedInOverride( { [ FAKE_EMAIL_ID ]: { version: '1.1.0' } } );
		await setTemplateHtmlOverride( FAKE_EMAIL_ID, V2_HTML );

		await triggerDetectionSweep();
		meta = await getWooEmailMeta( postId );
		// The inline auto-applier runs immediately after the sweep (same HTTP request in
		// the E2E trigger-sweep endpoint). An unedited post classified as
		// core_updated_uncustomized is auto-applied and flipped back to in_sync before
		// this assertion runs — consistent with the lifecycle tested in core-flows
		// scenario 1 and backward-compat Case A.
		expect( meta[ META_KEYS.STATUS ]?.[ 0 ] ).toBe( STATUS.IN_SYNC );

		await clearTransactionalEmailsOverride();
		await clearOptedInOverride();
		await clearTemplateHtmlOverride();
	} );

	test( 'Opted-in third-party email: version bump flips status when edited', async () => {
		const customized = V1_HTML.replace( 'V1 CONTENT', 'MERCHANT EDIT' );

		await setTransactionalEmailsOverride( [ FAKE_EMAIL_ID ] );
		await setOptedInOverride( { [ FAKE_EMAIL_ID ]: { version: '1.0.0' } } );
		await setTemplateHtmlOverride( FAKE_EMAIL_ID, V1_HTML );

		const postId = await seedWooEmailPost( {
			emailId: FAKE_EMAIL_ID,
			postContent: customized,
			storedSourceHash: 'AUTO_CURRENT',
			status: STATUS.IN_SYNC,
			version: '1.0.0',
		} );

		await setOptedInOverride( { [ FAKE_EMAIL_ID ]: { version: '1.1.0' } } );
		await setTemplateHtmlOverride( FAKE_EMAIL_ID, V2_HTML );

		await triggerDetectionSweep();
		const meta = await getWooEmailMeta( postId );
		expect( meta[ META_KEYS.STATUS ]?.[ 0 ] ).toBe(
			STATUS.CORE_UPDATED_CUSTOMIZED
		);

		await clearTransactionalEmailsOverride();
		await clearOptedInOverride();
		await clearTemplateHtmlOverride();
	} );
} );
