/**
 * Internal dependencies
 */
const { merchant } = require( '@woocommerce/e2e-utils' );
const { MENUS } = require( '../data/elements' );

/**
 * External dependencies
 */
const { it, describe, beforeAll } = require( '@jest/globals' );

const runPageLoadTest = () => {
	describe.each( MENUS )(
		' %s > Opening Top Level Pages',
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
						await Promise.all([
							page.click( subMenuElement ),
							page.waitForNavigation( { waitUntil: 'networkidle0' } ),
						] );
					}

					await expect( page ).toMatchElement( 'h1', {
						text: subMenuText,
					} );
				}
			);
		}
	);
};

// eslint-disable-next-line jest/no-export
module.exports = runPageLoadTest;
