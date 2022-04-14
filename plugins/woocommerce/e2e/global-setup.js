const { chromium } = require('@playwright/test');
const fs = require("fs");

module.exports = async (config) => {
	// Clear out the previous save states
	const adminState = 'e2e/storage/adminState.json';
	const customerState = 'e2e/storage/customerState.json';
	fs.unlink(adminState, function (err) {
		if (err) {
			console.log('Admin auth state wasn\'t there. Will create it.');
		} else {
			console.log('Successfully deleted the admin auth state file.');
		}
	});
	fs.unlink(customerState, function (err) {
		if (err) {
			console.log('Customer auth state wasn\'t there. Will create it.');
		} else {
			console.log('Successfully deleted the customer auth state file.');
		}
	});

	const { baseURL } = config.projects[0].use;
	// Sign in as admin user and save state
	const browser = await chromium.launch();
	const adminPage = await browser.newPage();
	await adminPage.goto(`${baseURL}/wp-admin`);
	await adminPage.fill('input[name="log"]', 'admin');
	await adminPage.fill('input[name="pwd"]', 'password');
	await adminPage.click('text=Log In');
	await adminPage
		.context()
		.storageState({ path: 'e2e/storage/adminState.json' });

	// Sign in as customer user and save state
	const customerPage = await browser.newPage();
	await customerPage.goto(`${baseURL}/wp-admin`);
	await customerPage.fill('input[name="log"]', 'customer');
	await customerPage.fill('input[name="pwd"]', 'password');
	await customerPage.click('text=Log In');
	await customerPage
		.context()
		.storageState({ path: 'e2e/storage/customerState.json' });
	await browser.close();
};
