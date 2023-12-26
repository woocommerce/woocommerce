/**
 * External dependencies
 */
import { insertBlock } from '@wordpress/e2e-test-utils';

export const insertShortcodeBlock = async ( shortcode: string ) => {
	await insertBlock( 'Shortcode' );
	await page.keyboard.type( shortcode );
};
