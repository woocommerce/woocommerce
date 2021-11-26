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

			it.each( subMenus )(
				'can see %s page properly',
				async ( subMenuTitle, subMenuElement, subMenuText ) => {
					// Go to Top Level Menu
					await Promise.all( [
						page.click( menuElement ),
						page.waitForNavigation( { waitUntil: 'networkidle0' } ),
						page.setViewport( {
							width: 1280,
							height: 800,
						} ),
					] );

					// Click sub-menu item and wait for the page to finish loading
					await Promise.all( [
						page.click( subMenuElement ),
						page.waitForNavigation( { waitUntil: 'networkidle0' } ),
					] );

					await expect( page ).toMatchElement( 'h1', {
						text: subMenuText,
					} );
				}
			);

			afterAll( async () => {
				await merchant.logout();
			} );
		}
	);
};

// eslint-disable-next-line jest/no-export
module.exports = runPageLoadTest;
