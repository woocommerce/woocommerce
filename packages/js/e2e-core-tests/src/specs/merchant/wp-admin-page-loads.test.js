/**
 * Internal dependencies
 */
const { merchant } = require( '@woocommerce/e2e-utils' );
const { MENUS } = require( '../data/elements' );

/**
 * External dependencies
 */
const { it, describe, beforeAll, expect } = require( '@jest/globals' );

const runPageLoadTest = () => {
	describe.each( MENUS )(
		'%s > Opening Top Level Pages',
		( menuTitle, menuElement, subMenus ) => {
			beforeAll( async () => {
				await merchant.login();
			} );

			afterAll( async () => {
				await merchant.logout();
			} );

			it.each( subMenus )(
				'can see %s page properly',
				async ( subMenuTitle, subMenuElement, subMenuText ) => {
					// Go to Top Level Menu
					await Promise.all( [
						page.click( menuElement ),
						page.waitForNavigation( { waitUntil: 'networkidle0' } ),
						merchant.dismissOnboardingWizard(),
						merchant.collapseAdminMenu( false ),
					] );

					// Click sub-menu item and wait for the page to finish loading
					if ( subMenuElement.length ) {
						await Promise.all( [
							page.click( subMenuElement ),
							page.waitForNavigation( {
								waitUntil: 'networkidle0',
							} ),
						] );
					}

					await expect( page ).toMatchElement( 'h1', {
						text: subMenuText,
					} );
				}
			);
		}
	);

	describe( 'Load async chunks', () => {
		beforeAll( async () => {
			await merchant.login();
		} );

		afterAll( async () => {
			await merchant.logout();
		} );

		it( 'Should load the CSS/JS chunks with a version parameter on Analytics Page', async () => {
			await merchant.openAnalyticsPage();
			const version = await page.evaluate(
				() => window.wcAdminAssets.version
			);

			const hasChunks = {
				js: false,
				css: false,
			};
			page.on( 'request', async ( request ) => {
				const url = request.url();
				const resType = request.resourceType();

				if ( url.includes( 'admin/chunks' ) ) {
					await expect( url ).toMatch(
						new RegExp(
							`\\?ver=${ version.replaceAll( '.', '\\.' ) }$`
						)
					);

					if ( resType === 'script' ) hasChunks.js = true;
					if ( resType === 'stylesheet' ) hasChunks.css = true;
				}
			} );
			await Promise.all( [
				page.reload(),
				page.waitForNavigation( {
					waitUntil: 'networkidle0',
				} ),
			] );
			expect( hasChunks.js ).toBe( true );
			expect( hasChunks.css ).toBe( true );
		} );
	} );
};

// eslint-disable-next-line jest/no-export
module.exports = runPageLoadTest;
