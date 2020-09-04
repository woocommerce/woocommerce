export const waitForSelector = async function ( page, selector ) {
	return await page.evaluate(
		( selector ) => Boolean( document.querySelector( selector ) ),
		selector
	);
};

export const waitForElementCount = async function ( page, domSelector, count ) {
	return await page.waitForFunction(
		( domSelector, count ) => {
			return document.querySelectorAll( domSelector ).length === count;
		},
		{},
		domSelector,
		count
	);
};
