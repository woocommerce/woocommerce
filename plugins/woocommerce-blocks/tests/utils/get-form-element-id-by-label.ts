/**
 * External dependencies
 */
import { canvas } from '@wordpress/e2e-test-utils';

export const getFormElementIdByLabel = async (
	text: string,
	className: string
) => {
	const labelElement = await canvas().waitForXPath(
		`//label[contains(text(), "${ text }") and contains(@class, "${ className }")]`,
		{ visible: true }
	);
	return await canvas().evaluate(
		( label ) => `#${ label.getAttribute( 'for' ) }`,
		labelElement
	);
};
