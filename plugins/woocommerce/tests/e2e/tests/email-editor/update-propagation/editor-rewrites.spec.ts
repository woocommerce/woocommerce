/**
 * External dependencies
 */
import { test, expect } from '@playwright/test';

/**
 * Internal dependencies
 */
import { ADMIN_STATE_PATH } from '../../../playwright.config';
import {
	deleteEmailPost,
	disableEmailEditor,
	enableEmailEditor,
} from '../helpers/enable-email-editor-feature';
import { resetWooEmailPost } from './helpers/seed-woo-email';
import {
	getMerchantEditedBlocks,
	listSyncEnabledEmails,
} from './helpers/editor-rewrites';
import { locks } from '../../../fixtures/fixtures';

/**
 * The "did the merchant edit this block?" check compares the PHP render of a
 * template with what the block editor saved. The editor rewrites blocks nobody
 * edited when it saves — it migrates attributes, decodes entities and trims
 * padding — and the check folds in every rewrite it knows about. A WordPress
 * release can add a new one. When that happens, untouched blocks read as
 * edited, every core update to them turns into a conflict, and this is the
 * test that says so.
 *
 * Not tagged `@pr`: it covers every shipped template and matters most on the
 * WordPress pre-release job.
 */
test.describe(
	'Update propagation — editor rewrites',
	{ lock: locks.EMAIL_FEATURE_FLAGS },
	() => {
		test.use( { storageState: ADMIN_STATE_PATH } );
		const seededPostIds: number[] = [];

		test.beforeAll( async ( { baseURL } ) => {
			await enableEmailEditor( baseURL! );
		} );

		test.afterEach( async ( { baseURL } ) => {
			const cleanupErrors: unknown[] = [];

			for ( const postId of seededPostIds.splice( 0 ) ) {
				try {
					await deleteEmailPost( baseURL!, String( postId ) );
				} catch ( error ) {
					cleanupErrors.push( error );
				}
			}

			if ( cleanupErrors.length > 0 ) {
				throw new AggregateError(
					cleanupErrors,
					'Editor rewrites cleanup failed.'
				);
			}
		} );

		test.afterAll( async ( { baseURL } ) => {
			await disableEmailEditor( baseURL! );
		} );

		test( 'Saving a shipped template in the editor without editing it leaves every block untouched', async ( {
			page,
		} ) => {
			const emailIds = await listSyncEnabledEmails();
			expect( emailIds.length ).toBeGreaterThan( 0 );

			// One post per template, holding the template's own render as both
			// content and stored base.
			const postIds: Record< string, number > = {};
			for ( const emailId of emailIds ) {
				postIds[ emailId ] = await resetWooEmailPost( emailId );
				seededPostIds.push( postIds[ emailId ] );
			}

			// Any email editor page carries the editor's serializer with every
			// email block registered, so one page can re-save all the posts.
			await page.goto(
				`/wp-admin/post.php?post=${
					postIds[ emailIds[ 0 ] ]
				}&action=edit`
			);
			await expect(
				page.locator( '#woocommerce-email-editor' )
			).toBeVisible( { timeout: 20000 } );
			await page.waitForFunction( () =>
				Boolean(
					window.wp?.blocks?.getBlockType?.(
						'woocommerce/email-content'
					)
				)
			);

			const saved = await page.evaluate( async ( ids ) => {
				const result = { count: 0, rewritten: 0 };
				for ( const id of Object.values( ids ) ) {
					const post = await window.wp.apiFetch( {
						path: `/wp/v2/woo_email/${ id }?context=edit`,
					} );
					const raw: string = post.content.raw;
					const serialized: string = window.wp.blocks.serialize(
						window.wp.blocks.parse( raw )
					);
					if ( serialized !== raw ) {
						result.rewritten++;
					}
					await window.wp.apiFetch( {
						path: `/wp/v2/woo_email/${ id }`,
						method: 'POST',
						data: { content: serialized },
					} );
					result.count++;
				}
				return result;
			}, postIds );
			expect( saved.count ).toBe( emailIds.length );
			test.info().annotations.push( {
				type: 'editor rewrites',
				description: `${ saved.rewritten } of ${ saved.count } templates changed on save`,
			} );
			// A pass means little when the editor rewrote nothing.
			expect
				.soft(
					saved.rewritten,
					'The editor saved every template byte-for-byte, so this guard checked nothing.'
				)
				.toBeGreaterThan( 0 );

			const flagged: string[] = [];
			for ( const [ emailId, postId ] of Object.entries( postIds ) ) {
				const check = await getMerchantEditedBlocks( postId );
				expect(
					check.base_blocks,
					`${ emailId } has no blocks to compare`
				).toBeGreaterThan( 0 );
				if ( check.post_blocks !== check.base_blocks ) {
					flagged.push(
						`${ emailId }: ${ check.base_blocks } blocks before save, ${ check.post_blocks } after`
					);
				}
				for ( const entry of check.edited ) {
					const what = [
						entry.attrs_changed ? 'attrs' : '',
						entry.markup_changed ? 'markup' : '',
					]
						.filter( Boolean )
						.join( '+' );
					flagged.push(
						`${ emailId } #${ entry.index } ${ entry.block } (${ what }): ${ entry.text }`
					);
				}
			}

			expect(
				flagged,
				'Blocks nobody edited read as merchant-edited after an editor save. The editor has a new rewrite; fold it into WCEmailTemplateChangeSummary.'
			).toEqual( [] );
		} );
	}
);
