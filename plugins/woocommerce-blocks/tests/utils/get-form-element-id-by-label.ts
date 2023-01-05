/**
 * External dependencies
 */
import { canvas } from '@wordpress/e2e-test-utils';

export const getFormElementIdByLabel = async (
	text: string,
	className: string
) => {
	// Remove leading dot if className is passed with it.
	className = className.replace( /^\./, '' );

	const labelElement = await canvas().waitForXPath(
		`//label[contains(text(), "${ text }") and contains(@class, "${ className }")]`,
		{ visible: true }
	);
	return await canvas().evaluate(
		( label ) => `#${ label.getAttribute( 'for' ) }`,
		labelElement
	);
};
