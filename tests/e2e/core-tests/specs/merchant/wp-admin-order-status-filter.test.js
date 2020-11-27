/* eslint-disable jest/no-export, jest/no-disabled-tests */
/**
 * Internal dependencies
 */
const {
	StoreOwnerFlow,
	createSimpleOrder,
	clickFilter,
	moveAllItemsToTrash,
} = require( '@woocommerce/e2e-utils' );

let statusColumnTextSelector = 'mark.order-status > span';

const runOrderFiltersTest = () => {
	describe('WooCommerce Orders > Filter Orders by Status', () => {
		beforeAll(async () => {
			// First, let's login
			await StoreOwnerFlow.login();

			// Next, let's create some orders we can filter against
			await createSimpleOrder('Pending payment');
			await createSimpleOrder('Processing');
			await createSimpleOrder('Completed');
			await createSimpleOrder('Cancelled');
			await createSimpleOrder('Refunded');
		});

		afterAll( async () => {
			// Make sure we're on the all orders view and cleanup the orders we created
			await StoreOwnerFlow.openAllOrdersView();
			await moveAllItemsToTrash();
		});

		it('should filter by Pending payment', async () => {
			await StoreOwnerFlow.openAllOrdersView();
			await clickFilter('.wc-pending');
			await expect(page).toMatchElement(statusColumnTextSelector, {text: 'Pending payment'});

			// Verify other statuses don't show
			await expect(page).not.toMatchElement(statusColumnTextSelector, {text: 'Processing'});
			await expect(page).not.toMatchElement(statusColumnTextSelector, {text: 'Completed'});
			await expect(page).not.toMatchElement(statusColumnTextSelector, {text: 'Cancelled'});
			await expect(page).not.toMatchElement(statusColumnTextSelector, {text: 'Refunded'});
		});

		it('should filter by Processing', async () => {
			await StoreOwnerFlow.openAllOrdersView();
			await clickFilter('.wc-processing');
			await expect(page).toMatchElement(statusColumnTextSelector, {text: 'Processing'});

			// Verify other statuses don't show
			await expect(page).not.toMatchElement(statusColumnTextSelector, {text: 'Pending payment'});
			await expect(page).not.toMatchElement(statusColumnTextSelector, {text: 'Completed'});
			await expect(page).not.toMatchElement(statusColumnTextSelector, {text: 'Cancelled'});
			await expect(page).not.toMatchElement(statusColumnTextSelector, {text: 'Refunded'});
		});

		it('should filter by Completed', async () => {
			await StoreOwnerFlow.openAllOrdersView();
			await clickFilter('.wc-completed');
			await expect(page).toMatchElement(statusColumnTextSelector, {text: 'Completed'});

			// Verify other statuses don't show
			await expect(page).not.toMatchElement(statusColumnTextSelector, {text: 'Pending payment'});
			await expect(page).not.toMatchElement(statusColumnTextSelector, {text: 'Processing'});
			await expect(page).not.toMatchElement(statusColumnTextSelector, {text: 'Cancelled'});
			await expect(page).not.toMatchElement(statusColumnTextSelector, {text: 'Refunded'});
		});

		it('should filter by Cancelled', async () => {
			await StoreOwnerFlow.openAllOrdersView();
			await clickFilter('.wc-cancelled');
			await expect(page).toMatchElement(statusColumnTextSelector, {text: 'Cancelled'});

			// Verify other statuses don't show
			await expect(page).not.toMatchElement(statusColumnTextSelector, {text: 'Pending payment'});
			await expect(page).not.toMatchElement(statusColumnTextSelector, {text: 'Processing'});
			await expect(page).not.toMatchElement(statusColumnTextSelector, {text: 'Completed'});
			await expect(page).not.toMatchElement(statusColumnTextSelector, {text: 'Refunded'});
		});

		it('should filter by Refunded', async () => {
			await StoreOwnerFlow.openAllOrdersView();
			await clickFilter('.wc-refunded');
			await expect(page).toMatchElement(statusColumnTextSelector, {text: 'Refunded'});

			// Verify other statuses don't show
			await expect(page).not.toMatchElement(statusColumnTextSelector, {text: 'Pending payment'});
			await expect(page).not.toMatchElement(statusColumnTextSelector, {text: 'Processing'});
			await expect(page).not.toMatchElement(statusColumnTextSelector, {text: 'Completed'});
			await expect(page).not.toMatchElement(statusColumnTextSelector, {text: 'Cancelled'});
		});

		it('should filter by All', async () => {
			await StoreOwnerFlow.openAllOrdersView();
			// Make sure all the order statuses that were created show in this list
			await clickFilter('.all');
			await expect(page).toMatchElement(statusColumnTextSelector, {text: 'Pending payment'});
			await expect(page).toMatchElement(statusColumnTextSelector, {text: 'Processing'});
			await expect(page).toMatchElement(statusColumnTextSelector, {text: 'Completed'});
			await expect(page).toMatchElement(statusColumnTextSelector, {text: 'Cancelled'});
			await expect(page).toMatchElement(statusColumnTextSelector, {text: 'Refunded'});
		});

	});
};

module.exports = runOrderFiltersTest;
