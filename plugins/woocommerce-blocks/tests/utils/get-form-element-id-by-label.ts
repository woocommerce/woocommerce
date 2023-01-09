export const getFormElementIdByLabel = async (
	text: string,
	className: string
) => {
	// Remove leading dot if className is passed with it.
	className = className.replace( /^\./, '' );

	const labelElement = await page.waitForXPath(
		`//label[contains(text(), "${ text }") and contains(@class, "${ className }")]`,
		{ visible: true }
	);
	return await page.evaluate(
		( label ) => `#${ label.getAttribute( 'for' ) }`,
		labelElement
	);
};
