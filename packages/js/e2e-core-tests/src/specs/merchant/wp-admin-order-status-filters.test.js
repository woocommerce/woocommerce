/**
 * Internal dependencies
 */
const {
	merchant,
	withRestApi,
	clickFilter,
	moveAllItemsToTrash,
} = require( '@woocommerce/e2e-utils' );

const statusColumnTextSelector = 'mark.order-status > span';

// Define order statuses to filter against
const orderStatus = {
	pending: {
		name: 'wc-pending',
		description: { text: 'Pending payment' },
	},
	processing: {
		name: 'wc-processing',
		description: { text: 'Processing' },
	},
	onHold: {
		name: 'wc-on-hold',
		description: { text: 'On hold' },
	},
	completed: {
		name: 'wc-completed',
		description: { text: 'Completed' },
	},
	cancelled: {
		name: 'wc-cancelled',
		description: { text: 'Cancelled' },
	},
	refunded: {
		name: 'wc-refunded',
		description: { text: 'Refunded' },
	},
	failed: {
		name: 'wc-failed',
		description: { text: 'Failed' },
	}
};
const defaultOrder = config.get('orders.basicPaidOrder');


const runOrderStatusFiltersTest = () => {
	describe('WooCommerce Orders > Filter Orders by Status', () => {
		beforeAll( async () => {
			// First, let's create some orders we can filter against
			const orders = Object.entries(orderStatus).map((entryPair) => {
				const statusName = entryPair[1].name.replace('wc-', '');

				return {
					...defaultOrder,
					status: statusName,
				};
			});

			// Create the orders using the API
			await withRestApi.batchCreateOrders( orders, false );

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
