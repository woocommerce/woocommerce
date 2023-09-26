/**
 * External dependencies
 */
import config from 'config';
import { ElementHandle } from 'puppeteer';

/**
 * Internal dependencies
 */
import { Login } from '../pages/Login';

/**
 * Wait for UI blocking to end.
 */
const uiUnblocked = async (): Promise< void > => {
	await page.waitForFunction(
		() => ! Boolean( document.querySelector( '.blockUI' ) )
	);
};

/**
 * Suspend processing for the specified time
 *
 * @param {number} timeout in milliseconds
 */
const waitForTimeout = async ( timeout: number ): Promise< void > => {
	await new Promise( ( resolve ) => setTimeout( resolve, timeout ) );
};

/**
 * Publish, verify that item was published. Trash, verify that item was trashed.
 *
 * @param {string} button              (Publish)
 * @param {string} publishNotice
 * @param {string} publishVerification
 */
const verifyPublishAndTrash = async (
	button: string,
	publishNotice: string,
	publishVerification: string,
	trashVerification: string
): Promise< void > => {
	// Wait for auto save
	await waitForTimeout( 2000 );
	// Publish
	await page.click( button );

	// Verify
	await expect( page ).toMatchElement( publishNotice, {
		text: publishVerification,
	} );
	if ( button === '.order_actions li .save_order' ) {
		await expect( page ).toMatchElement(
			'#select2-order_status-container',
			{
				text: 'Processing',
			}
		);
		await expect( page ).toMatchElement(
			'#woocommerce-order-notes .note_content',
			{
				text: 'Order status changed from Pending payment to Processing.',
			}
		);
	}

	// Trash
	await expect( page ).toClick( 'a', { text: 'Move to Trash' } );
	await page.waitForSelector( '#message' );

	// Verify
	await expect( page ).toMatchElement( publishNotice, {
		text: trashVerification,
	} );
};

const hasClass = async (
	element: ElementHandle,
	elementClass: string
): Promise< boolean > => {
	const classNameProp = await element.getProperty( 'className' );
	const classNameValue = ( await classNameProp.jsonValue() ) as string;

	return classNameValue.includes( elementClass );
};

const getInputValue = async ( selector: string ): Promise< unknown > => {
	const field = await page.$( selector );
	if ( field ) {
		const fieldValue = await (
			await field.getProperty( 'value' )
		 ).jsonValue();

		return fieldValue;
	}
	return null;
};

const getAttribute = async (
	selector: string,
	attribute: string
): Promise< unknown > => {
	await page.focus( selector );
	const field = await page.$( selector );
	if ( field ) {
		const fieldValue = await (
			await field.getProperty( attribute )
		 ).jsonValue();

		return fieldValue;
	}
	return null;
};

const getElementByText = async (
	element: string,
	text: string,
	parentSelector?: string
): Promise< ElementHandle | null > => {
	let parent: ElementHandle | null = null;
	if ( parentSelector ) {
		parent = await page.$( parentSelector );
	}
	const els = await ( parent || page ).$x(
		`//${ element }[contains(text(), "${ text }")]`
	);
	return els[ 0 ];
};

const getElementByAttributeAndValue = async (
	element: string,
	attribute: string,
	value: string,
	parentSelector?: string
): Promise< ElementHandle | null > => {
	let parent: ElementHandle | null = null;
	if ( parentSelector ) {
		parent = await page.$( parentSelector );
	}
	const els = await ( parent || page ).$x(
		`//${ element }[@${ attribute }="${ value }"]`
	);
	return els[ 0 ];
};

const waitForElementByText = async (
	element: string,
	text: string,
	options?: { timeout?: number }
): Promise< ElementHandle | null > => {
	const els = await page.waitForXPath(
		`//${ element }[contains(text(), "${ text }")]`,
		options
	);
	return els;
};

export const waitForElementByTextWithoutThrow = async (
	element: string,
	text: string,
	timeoutInSeconds = 5
): Promise< boolean > => {
	let selected = await getElementByText( element, text );
	for ( let s = 0; s < timeoutInSeconds; s++ ) {
		if ( selected ) {
			break;
		}
		await waitForTimeout( 1000 );
		selected = await getElementByText( element, text );
	}
	return Boolean( selected );
};

const waitUntilElementStopsMoving = async ( selector: string ) => {
	return await page.waitForFunction(
		( elementSelector ) => {
			const element = document.querySelector( elementSelector );
			const elementRect = element.getBoundingClientRect();
			const jsWindow: Window &
				typeof globalThis & {
					elementX?: number;
					elementY?: number;
				} = window;

			if (
				jsWindow.elementX !== elementRect.x.toFixed( 1 ) ||
				jsWindow.elementY !== elementRect.y.toFixed( 1 )
			) {
				jsWindow.elementX = elementRect.x.toFixed( 1 );
				jsWindow.elementY = elementRect.y.toFixed( 1 );
				return false;
			}

			delete jsWindow.elementX;
			delete jsWindow.elementY;
			return true;
		},
		{},
		selector
	);
};

const deactivateAndDeleteExtension = async (
	extension: string
): Promise< void > => {
	const baseUrl = config.get( 'url' );
	const pluginsAdmin = 'wp-admin/plugins.php?plugin_status=all&paged=1&s';
	await page.goto( baseUrl + pluginsAdmin, {
		waitUntil: 'networkidle0',
		timeout: 10000,
	} );
	await waitForElementByText( 'h1', 'Plugins' );
	// deactivate extension
	const deactivateExtension = await page.$( `#deactivate-${ extension }` );
	await deactivateExtension?.click();
	await waitForElementByText( 'p', 'Plugin deactivated.' );
	// delete extension
	const deleteExtension = await page.$( `#delete-${ extension }` );
	await deleteExtension?.click();
};

const addReviewToProduct = async ( productId: number, productName: string ) => {
	// we need a guest user
	const login = new Login( page );
	await login.logout();

	const baseUrl = config.get( 'url' );
	const productUrl = `/?p=${ productId }`;
	await page.goto( baseUrl + productUrl, {
		waitUntil: 'networkidle0',
		timeout: 10000,
	} );
	await waitForElementByText( 'h1', productName );

	// Reviews tab
	const reviewTab = await page.$( '#tab-title-reviews' );
	await reviewTab?.click();
	const fiveStars = await page.$( '.star-5' );
	await fiveStars?.click();

	// write a comment
	await page.type( '#comment', 'My comment' );
	await page.type( '#author', 'John Doe' );
	await page.type( '#email', 'john.doe@john.doe' );

	const submit = await page.$( '#submit' );
	await submit?.click();
	// the comment was published
	await waitForElementByText( 'p', 'My comment' );
	await login.login();
};

export {
	uiUnblocked,
	verifyPublishAndTrash,
	getInputValue,
	getAttribute,
	getElementByText,
	getElementByAttributeAndValue,
	waitForElementByText,
	waitUntilElementStopsMoving,
	hasClass,
	waitForTimeout,
	deactivateAndDeleteExtension,
	addReviewToProduct,
};
