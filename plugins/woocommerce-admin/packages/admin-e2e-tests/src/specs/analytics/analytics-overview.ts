/**
 * Internal dependencies
 */
import { AnalyticsOverview } from '../../pages/AnalyticsOverview';
import { Login } from '../../pages/Login';

/* eslint-disable @typescript-eslint/no-var-requires */
const { afterAll, beforeAll, describe, it } = require( '@jest/globals' );

const testAdminAnalyticsOverview = () => {
	describe( 'Analytics pages', () => {
		const analyticsPage = new AnalyticsOverview( page );
		const login = new Login( page );

		beforeAll( async () => {
			await login.login();
			await analyticsPage.navigate();
			await analyticsPage.isDisplayed();
		} );
		afterAll( async () => {
			await login.logout();
		} );

		it( 'a user should see 3 sections by default - Performance, Charts, and Leaderboards', async () => {
			const sections = ( await analyticsPage.getSections() ).map(
				( section ) => section.title
			);
			expect( sections ).toContain( 'Performance' );
			expect( sections ).toContain( 'Charts' );
			expect( sections ).toContain( 'Leaderboards' );
		} );

		it( 'should allow a user to remove a section', async () => {
			await analyticsPage.removeSection( 'Performance' );
			const sections = ( await analyticsPage.getSections() ).map(
				( section ) => section.title
			);
			expect( sections ).not.toContain( 'Performance' );
		} );

		it( 'should allow a user to add a section back in', async () => {
			let sections = ( await analyticsPage.getSections() ).map(
				( section ) => section.title
			);
			expect( sections ).not.toContain( 'Performance' );
			await analyticsPage.addSection( 'Performance' );

			sections = ( await analyticsPage.getSections() ).map(
				( section ) => section.title
			);
			expect( sections ).toContain( 'Performance' );
		} );

		describe( 'moving sections', () => {
			it( 'should not display move up for the top, or move down for the bottom section', async () => {
				const sections = await analyticsPage.getSections();
				for ( const section of sections ) {
					const index = sections.indexOf( section );
					const menuItems = (
						await analyticsPage.getEllipsisMenuItems(
							section.title
						)
					 ).map( ( item ) => item.title );
					if ( index === 0 ) {
						expect( menuItems ).toContain( 'Move down' );
						expect( menuItems ).not.toContain( 'Move up' );
					} else if ( index === sections.length - 1 ) {
						expect( menuItems ).not.toContain( 'Move down' );
						expect( menuItems ).toContain( 'Move up' );
					} else {
						expect( menuItems ).toContain( 'Move down' );
						expect( menuItems ).toContain( 'Move up' );
					}
					await analyticsPage.closeSectionEllipsis( section.title );
				}
			} );

			it( 'should allow a user to move a section down', async () => {
				const sections = await analyticsPage.getSections();
				await analyticsPage.moveSectionDown( sections[ 0 ].title );
				const newSections = await analyticsPage.getSections();
				expect( sections[ 0 ].title ).toEqual( newSections[ 1 ].title );
				expect( sections[ 1 ].title ).toEqual( newSections[ 0 ].title );
			} );

			it( 'should allow a user to move a section up', async () => {
				const sections = await analyticsPage.getSections();
				await analyticsPage.moveSectionUp( sections[ 1 ].title );
				const newSections = await analyticsPage.getSections();
				expect( sections[ 0 ].title ).toEqual( newSections[ 1 ].title );
				expect( sections[ 1 ].title ).toEqual( newSections[ 0 ].title );
			} );
		} );
	} );
};

module.exports = { testAdminAnalyticsOverview };
