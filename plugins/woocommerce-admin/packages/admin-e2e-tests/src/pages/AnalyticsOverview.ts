/**
 * External dependencies
 */
import { ElementHandle } from 'puppeteer';

/**
 * Internal dependencies
 */
import { waitForElementByText } from '../utils/actions';
import { Analytics } from './Analytics';

type Section = {
	title: string;
	element: ElementHandle< Element >;
};
const isSection = ( item: Section | undefined ): item is Section => {
	return !! item;
};

export class AnalyticsOverview extends Analytics {
	async navigate() {
		await this.navigateToSection( 'overview' );
	}

	async getSections() {
		const list = await this.page.$$(
			'.woocommerce-dashboard-section .woocommerce-section-header'
		);
		const sections = await Promise.all(
			list.map( async ( item ) => {
				const title = await item.evaluate( ( element ) => {
					const header = element.querySelector( 'h2' );
					return header?.textContent;
				} );
				if ( title ) {
					return {
						title,
						element: item,
					};
				}
				return undefined;
			} )
		);
		return sections.filter( isSection );
	}

	async openSectionEllipsis( sectionTitle: string ) {
		const section = ( await this.getSections() ).find(
			( thisSection ) => thisSection.title === sectionTitle
		);
		if ( section ) {
			const ellipsisMenu = await section.element.$(
				'.woocommerce-ellipsis-menu .woocommerce-ellipsis-menu__toggle'
			);
			await ellipsisMenu?.click();
			await this.page.waitForSelector(
				'.woocommerce-ellipsis-menu div[role=menu]'
			);
		}
	}

	async closeSectionEllipsis( sectionTitle: string ) {
		const section = ( await this.getSections() ).find(
			( thisSection ) => thisSection.title === sectionTitle
		);
		if ( section ) {
			const ellipsisMenu = await section.element.$(
				'.woocommerce-ellipsis-menu .woocommerce-ellipsis-menu__toggle'
			);
			await ellipsisMenu?.click();
			await page.waitFor(
				() =>
					! document.querySelector(
						'.woocommerce-ellipsis-menu div[role=menu]'
					)
			);
		}
	}

	async removeSection( sectionTitle: string ) {
		await this.openSectionEllipsis( sectionTitle );
		const item = await waitForElementByText( 'div', 'Remove section' );
		await item?.click();
	}

	async addSection( sectionTitle: string ) {
		await this.page.waitForSelector( "button[title='Add more sections']" );
		await this.page.click( "button[title='Add more sections']" );
		await this.page.waitForSelector(
			`button[title='Add ${ sectionTitle } section']`
		);
		await this.page.click(
			`button[title='Add ${ sectionTitle } section']`
		);
	}

	async moveSectionDown( sectionTitle: string ) {
		await this.openSectionEllipsis( sectionTitle );
		const item = await waitForElementByText( 'div', 'Move down' );
		await item?.click();
	}

	async moveSectionUp( sectionTitle: string ) {
		await this.openSectionEllipsis( sectionTitle );
		const item = await waitForElementByText( 'div', 'Move up' );
		await item?.click();
	}

	async getEllipsisMenuItems( sectionTitle: string ) {
		await this.openSectionEllipsis( sectionTitle );
		const list = await this.page.$$(
			'.woocommerce-ellipsis-menu div[role=menuitem]'
		);
		return Promise.all(
			list.map( async ( item ) => ( {
				title: await item.evaluate(
					( element ) => element?.textContent
				),
				element: item,
			} ) )
		);
	}

	async getEllipsisMenuCheckboxItems( sectionTitle: string ) {
		await this.openSectionEllipsis( sectionTitle );
		const list = await this.page.$$(
			'.woocommerce-ellipsis-menu div[role=menuitemcheckbox]'
		);
		return Promise.all(
			list.map( async ( item ) => ( {
				title: await item.evaluate(
					( element ) => element?.textContent
				),
				element: item,
			} ) )
		);
	}
}
