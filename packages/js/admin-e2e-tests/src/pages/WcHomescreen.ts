/**
 * External dependencies
 */
import { ElementHandle } from 'puppeteer';

/**
 * Internal dependencies
 */
import {
	waitForElementByText,
	getElementByAttributeAndValue,
	waitForElementByTextWithoutThrow,
	getElementByText,
	waitForTimeout,
} from '../utils/actions';
import { BasePage } from './BasePage';

export class WcHomescreen extends BasePage {
	url = 'wp-admin/admin.php?page=wc-admin';

	async isDisplayed(): Promise< void > {
		// Wait for Benefits section to appear
		await waitForElementByText( 'h1', 'Home' );
	}

	async possiblyDismissWelcomeModal(): Promise< void > {
		const modal = await this.isWelcomeModalVisible();

		if ( modal ) {
			await this.clickButtonWithText( 'Next' );
			await waitForTimeout( 1000 );
			await this.clickButtonWithText( 'Next' );
			await waitForTimeout( 1000 );
			await this.click( '.components-guide__finish-button' );
			await waitForTimeout( 500 );
		}
	}

	async isWelcomeModalVisible(): Promise< boolean > {
		const modalText = 'Welcome to your WooCommerce store’s online HQ!';
		const modal = await waitForElementByTextWithoutThrow(
			'h2',
			modalText,
			10
		);
		return modal;
	}

	async getTaskList(): Promise< Array< string | null > > {
		await page.waitForSelector(
			'.woocommerce-task-card .woocommerce-task-list__item-title'
		);
		await waitForElementByText( '*', 'Get ready to start selling' );
		const list = await this.page.$$eval(
			'.woocommerce-task-card .woocommerce-task-list__item-title',
			( items ) => items.map( ( item ) => item.textContent )
		);
		return list.map( ( item: string | null ) => {
			const match = item?.match( /(.+)[0-9] minute/ );
			if ( match && match.length > 1 ) {
				return match[ 1 ];
			}
			return item;
		} );
	}

	async isTaskListDisplayed(): Promise< boolean > {
		return !! ( await waitForElementByTextWithoutThrow(
			'*',
			'Get ready to start selling'
		) );
	}

	async clickOnTaskList( taskTitle: string ): Promise< void > {
		const item = await waitForElementByText( '*', taskTitle );

		if ( ! item ) {
			throw new Error(
				`Could not find task list item with title: ${ taskTitle }`
			);
		} else {
			await item.click();
			await waitForElementByText( 'h1', taskTitle );
		}
	}

	async hideTaskList(): Promise< void > {
		const taskListOptions = await getElementByAttributeAndValue(
			'button',
			'title',
			'Task List Options'
		);
		await taskListOptions?.click();
		await waitForElementByText( 'button', 'Hide this' );
		await waitForTimeout( 200 ); // Transition of popup.
		const hideThisButton = await getElementByText( 'button', 'Hide this' );
		await hideThisButton?.click();
		await waitForTimeout( 500 );
	}

	async waitForNotesRequestToBeLoaded(): Promise< void > {
		await this.page.waitForResponse( ( response ) => {
			const url = encodeURIComponent( response.url() );
			return url.includes( '/wc-analytics/admin/notes' ) && response.ok();
		} );
	}

	async isActivityPanelShown(): Promise< boolean > {
		return !! ( await this.page.$( '.woocommerce-activity-panel' ) );
	}

	async getActivityPanels(): Promise<
		Array< { title: string; count?: number; element?: ElementHandle } >
	> {
		const panelContainer = await page.waitForSelector(
			'.woocommerce-activity-panel'
		);
		const list = await panelContainer.$$( 'h2' );
		return Promise.all(
			list.map( async ( item: ElementHandle ) => {
				const textContent = await page.evaluate(
					( el ) => el.textContent,
					item
				);
				const match = textContent?.match( /([a-zA-Z]+)([0-9]+)/ );
				if ( match && match.length > 2 ) {
					return {
						title: match[ 1 ],
						count: parseInt( match[ 2 ], 10 ),
						element: item,
					};
				}
				return { title: textContent };
			} )
		);
	}

	async expandActivityPanel( title: string ): Promise< void > {
		const activityPanels = await this.getActivityPanels();
		const panel = activityPanels.find( ( p ) => p.title === title );
		if ( panel ) {
			await panel.element?.click();
		}
	}
}
