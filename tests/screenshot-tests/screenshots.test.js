const path    = require('path');
const config  = require('config');
const baseUrl = config.get('url');

describe('woocommerce screenshot tests', () => {

	it('test my account page', async () => {
		await page.goto(baseUrl+'/my-account/');

		const imagePath = path.resolve(__dirname, './master-screenshots', 'my-account-logged-out.png');
		const image = await page.screenshot({path: imagePath});
		expect(image).toMatchImageSnapshot();
	});
});
