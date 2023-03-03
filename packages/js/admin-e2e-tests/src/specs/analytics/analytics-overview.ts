/**
 * External dependencies
 */
import { afterAll, beforeAll, describe, it } from '@jest/globals';
/**
 * Internal dependencies
 */
import { AnalyticsOverview } from '../../pages/AnalyticsOverview';
import { Login } from '../../pages/Login';

export const testAdminAnalyticsOverview = () => {
	describe( 'Analytics pages', () => {
		const analyticsPage = new AnalyticsOverview( page );
		const login = new Login( page );
		const sectionTitles = [ 'Performance', 'Charts', 'Leaderboards' ];
		const titlesString = sectionTitles.join( ', ' );

		beforeAll( async () => {
			await login.login();
			await analyticsPage.navigate();
			await analyticsPage.isDisplayed();
			// Restore original order to sections
			for ( let t = 0; t < sectionTitles.length; t++ ) {
				const visibleSections = await analyticsPage.getSectionTitles();
				if ( visibleSections.indexOf( sectionTitles[ t ] ) < 0 ) {
					await analyticsPage.addSection( sectionTitles[ t ] );
				}
			}
		} );
		afterAll( async () => {
			await login.logout();
		} );

		it( `a user should see ${ sectionTitles.length } sections by default - ${ titlesString }`, async () => {
			const sections = await analyticsPage.getSectionTitles();
			for ( let t = 0; t < sectionTitles.length; t++ ) {
				expect( sections ).toContain( sectionTitles[ t ] );
			}
		} );

		it( 'should allow a user to remove a section', async () => {
			await analyticsPage.removeSection( sectionTitles[ 0 ] );
			const sections = await analyticsPage.getSectionTitles();
			expect( sections ).not.toContain( sectionTitles[ 0 ] );
		} );

		it( 'should allow a user to add a section back in', async () => {
			let sections = await analyticsPage.getSectionTitles();
			expect( sections ).not.toContain( sectionTitles[ 0 ] );
			await analyticsPage.addSection( sectionTitles[ 0 ] );

			sections = await analyticsPage.getSectionTitles();
			expect( sections ).toContain( sectionTitles[ 0 ] );
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
				const sections = await analyticsPage.getSectionTitles();
				await analyticsPage.moveSectionDown( sections[ 0 ] );
				const newSections = await analyticsPage.getSectionTitles();
				expect( sections[ 0 ] ).toEqual( newSections[ 1 ] );
				expect( sections[ 1 ] ).toEqual( newSections[ 0 ] );
			} );

			it( 'should allow a user to move a section up', async () => {
				const sections = await analyticsPage.getSectionTitles();
				await analyticsPage.moveSectionUp( sections[ 1 ] );
				const newSections = await analyticsPage.getSectionTitles();
				expect( sections[ 0 ] ).toEqual( newSections[ 1 ] );
				expect( sections[ 1 ] ).toEqual( newSections[ 0 ] );
			} );
		} );
	} );
};
