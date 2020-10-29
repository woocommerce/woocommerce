export const waitForElementCount = function ( page, domSelector, count ) {
	return page.waitForFunction(
		( domSelector, count ) => {
			return document.querySelectorAll( domSelector ).length === count;
		},
		{},
		domSelector,
		count
	);
};
