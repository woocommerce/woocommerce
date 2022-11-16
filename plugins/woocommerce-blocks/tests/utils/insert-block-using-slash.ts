/**
 * External dependencies
 */
import { canvas, insertBlock } from '@wordpress/e2e-test-utils';

/**
 * Internal dependencies
 */
import SELECTORS from './selectors';

export const insertBlockUsingSlash = async ( blockTitle: string ) => {
	await insertBlock( 'Paragraph' );
	await canvas().keyboard.type( `/${ blockTitle }` );
	await canvas().waitForSelector( SELECTORS.popover );
	await canvas().keyboard.press( 'Enter' );
};
