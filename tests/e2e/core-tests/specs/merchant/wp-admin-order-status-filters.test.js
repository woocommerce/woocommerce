/**
 * Internal dependencies
 */
const config = require( 'config' );

const {
	merchant,
	withRestApi,
	clickFilter,
	moveAllItemsToTrash,
} = require( '@woocommerce/e2e-utils' );

const statusColumnTextSelector = 'mark.order-status > span';

// Define order statuses to filter against
const orderStatus = [
	['Pending payment', 'wc-pending'],
	['Processing', 'wc-processing'],
	['On hold', 'wc-on-hold'],
	['Completed', 'wc-completed'],
	['Cancelled', 'wc-cancelled'],
	['Refunded', 'wc-refunded'],
	['Failed', 'wc-failed'],
];
const defaultOrder = config.get('orders.basicPaidOrder');

const runOrderStatusFiltersTest = () => {
	describe('WooCommerce Orders > Filter Orders by Status', () => {
		beforeAll(async () => {
			// First, let's create some orders we can filter against
			const orders = orderStatus.map((entryPair) => {
				const statusName = entryPair[1].replace('wc-', '');

				return {
					...defaultOrder,
					status: statusName,
				};
			});

			// Create the orders using the API
			await withRestApi.batchCreateOrders(orders);

			// Next, let's login and navigate to the Orders page
			await merchant.login();
			await merchant.openAllOrdersView();
		});

		afterAll( async () => {
			// Make sure we're on the all orders view and cleanup the orders we created
			await merchant.openAllOrdersView();
			await moveAllItemsToTrash();
		});

		it.each(orderStatus)('should filter by %s', async (statusText, statusClassName) => {
			// Identify which statuses should be shown or hidden
			const shownStatus = { text: statusText };
			const hiddenStatuses = orderStatus
				.filter((pair) => !pair.includes(statusText))
				.map(([statusText]) => {
					return { text: statusText };
				});

			// Click the status filter and verify that only the matching order is shown
			await clickFilter('.' + statusClassName);
			await expect(page).toMatchElement(statusColumnTextSelector, shownStatus);

			// Verify other statuses don't show
			for (const hiddenStatus of hiddenStatuses) {
				await expect(page).not.toMatchElement(statusColumnTextSelector, hiddenStatus);
			}
		});

		it('should filter by All', async () => {
			// Make sure all the order statuses that were created show in this list
			await clickFilter('.all');

			for (const [statusText] of orderStatus) {
				await expect(page).toMatchElement(statusColumnTextSelector, { text: statusText });
			}
		});
	});
};

module.exports = runOrderStatusFiltersTest;
