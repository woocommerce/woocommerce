const {test, expect} = require('@playwright/test');

test.describe('WordPress', async () => {
	test.use({storageState: process.env.CUSTOMERSTATE});

	test.beforeEach(async ({page}) => {
		await page.goto('hello-world/');
		await expect(page.locator('.entry-title')).toHaveText(
			'Hello world!'
		);
	});

	test('logged-in customer can comment on a post', async ({page}) => {
		let comment = `This is a test comment ${Date.now()}`;
		await page.locator('textarea#comment').fill(comment);
		await page.locator('input#submit').click();
		await expect(
			page.locator('div.comment-content').last()
		).toContainText(comment);
		await page.pause();
	});
});


