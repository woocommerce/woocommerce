export const goToShop = () => {
	page.goto( BASE_URL + '/shop', {
		waitUntil: 'networkidle0',
	} );
};
