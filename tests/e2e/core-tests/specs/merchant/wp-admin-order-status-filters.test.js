/* eslint-disable jest/no-export, jest/no-disabled-tests */
/**
 * Internal dependencies
 */
const {
	merchant,
	createSimpleOrder,
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

const runOrderStatusFiltersTest = () => {
	describe('WooCommerce Orders > Filter Orders by Status', () => {
		beforeAll(async () => {
			// First, let's login
			await merchant.login();

			// Next, let's create some orders we can filter against
			await createSimpleOrder(orderStatus.pending.description.text);
			await createSimpleOrder(orderStatus.processing.description.text);
			await createSimpleOrder(orderStatus.onHold.description.text);
			await createSimpleOrder(orderStatus.completed.description.text);
			await createSimpleOrder(orderStatus.cancelled.description.text);
			await createSimpleOrder(orderStatus.refunded.description.text);
			await createSimpleOrder(orderStatus.failed.description.text);
		}, 40000);

		afterAll( async () => {
			// Make sure we're on the all orders view and cleanup the orders we created
			await merchant.openAllOrdersView();
			await moveAllItemsToTrash();
		});

		it('should filter by Pending payment', async () => {
			await merchant.openAllOrdersView();
			await clickFilter('.' + orderStatus.pending.name);
			await expect(page).toMatchElement(statusColumnTextSelector, orderStatus.pending.description);

			// Verify other statuses don't show
			await expect(page).not.toMatchElement(statusColumnTextSelector, orderStatus.processing.description);
			await expect(page).not.toMatchElement(statusColumnTextSelector, orderStatus.completed.description);
			await expect(page).not.toMatchElement(statusColumnTextSelector, orderStatus.cancelled.description);
			await expect(page).not.toMatchElement(statusColumnTextSelector, orderStatus.refunded.description);
			await expect(page).not.toMatchElement(statusColumnTextSelector, orderStatus.onHold.description);
			await expect(page).not.toMatchElement(statusColumnTextSelector, orderStatus.failed.description);
		});

		it('should filter by Processing', async () => {
			await merchant.openAllOrdersView();
			await clickFilter('.' + orderStatus.processing.name);
			await expect(page).toMatchElement(statusColumnTextSelector, orderStatus.processing.description);

			// Verify other statuses don't show
			await expect(page).not.toMatchElement(statusColumnTextSelector, orderStatus.pending.description);
			await expect(page).not.toMatchElement(statusColumnTextSelector, orderStatus.completed.description);
			await expect(page).not.toMatchElement(statusColumnTextSelector, orderStatus.cancelled.description);
			await expect(page).not.toMatchElement(statusColumnTextSelector, orderStatus.refunded.description);
			await expect(page).not.toMatchElement(statusColumnTextSelector, orderStatus.onHold.description);
			await expect(page).not.toMatchElement(statusColumnTextSelector, orderStatus.failed.description);
		});

		it('should filter by On hold', async () => {
			await merchant.openAllOrdersView();
			await clickFilter('.' + orderStatus.onHold.name);
			await expect(page).toMatchElement(statusColumnTextSelector, orderStatus.onHold.description);

			// Verify other statuses don't show
			await expect(page).not.toMatchElement(statusColumnTextSelector, orderStatus.pending.description);
			await expect(page).not.toMatchElement(statusColumnTextSelector, orderStatus.processing.description);
			await expect(page).not.toMatchElement(statusColumnTextSelector, orderStatus.completed.description);
			await expect(page).not.toMatchElement(statusColumnTextSelector, orderStatus.cancelled.description);
			await expect(page).not.toMatchElement(statusColumnTextSelector, orderStatus.refunded.description);
			await expect(page).not.toMatchElement(statusColumnTextSelector, orderStatus.failed.description);
		});

		it('should filter by Completed', async () => {
			await merchant.openAllOrdersView();
			await clickFilter('.' + orderStatus.completed.name);
			await expect(page).toMatchElement(statusColumnTextSelector, orderStatus.completed.description);

			// Verify other statuses don't show
			await expect(page).not.toMatchElement(statusColumnTextSelector, orderStatus.pending.description);
			await expect(page).not.toMatchElement(statusColumnTextSelector, orderStatus.processing.description);
			await expect(page).not.toMatchElement(statusColumnTextSelector, orderStatus.cancelled.description);
			await expect(page).not.toMatchElement(statusColumnTextSelector, orderStatus.refunded.description);
			await expect(page).not.toMatchElement(statusColumnTextSelector, orderStatus.onHold.description);
			await expect(page).not.toMatchElement(statusColumnTextSelector, orderStatus.failed.description);
		});

		it('should filter by Cancelled', async () => {
			await merchant.openAllOrdersView();
			await clickFilter('.' + orderStatus.cancelled.name);
			await expect(page).toMatchElement(statusColumnTextSelector, orderStatus.cancelled.description);

			// Verify other statuses don't show
			await expect(page).not.toMatchElement(statusColumnTextSelector, orderStatus.pending.description);
			await expect(page).not.toMatchElement(statusColumnTextSelector, orderStatus.processing.description);
			await expect(page).not.toMatchElement(statusColumnTextSelector, orderStatus.completed.description);
			await expect(page).not.toMatchElement(statusColumnTextSelector, orderStatus.refunded.description);
			await expect(page).not.toMatchElement(statusColumnTextSelector, orderStatus.onHold.description);
			await expect(page).not.toMatchElement(statusColumnTextSelector, orderStatus.failed.description);
		});

		it('should filter by Refunded', async () => {
			await merchant.openAllOrdersView();
			await clickFilter('.' + orderStatus.refunded.name);
			await expect(page).toMatchElement(statusColumnTextSelector, orderStatus.refunded.description);

			// Verify other statuses don't show
			await expect(page).not.toMatchElement(statusColumnTextSelector, orderStatus.pending.description);
			await expect(page).not.toMatchElement(statusColumnTextSelector, orderStatus.processing.description);
			await expect(page).not.toMatchElement(statusColumnTextSelector, orderStatus.completed.description);
			await expect(page).not.toMatchElement(statusColumnTextSelector, orderStatus.cancelled.description);
			await expect(page).not.toMatchElement(statusColumnTextSelector, orderStatus.onHold.description);
			await expect(page).not.toMatchElement(statusColumnTextSelector, orderStatus.failed.description);
		});

		it('should filter by Failed', async () => {
			await merchant.openAllOrdersView();
			await clickFilter('.' + orderStatus.failed.name);
			await expect(page).toMatchElement(statusColumnTextSelector, orderStatus.failed.description);

			// Verify other statuses don't show
			await expect(page).not.toMatchElement(statusColumnTextSelector, orderStatus.pending.description);
			await expect(page).not.toMatchElement(statusColumnTextSelector, orderStatus.processing.description);
			await expect(page).not.toMatchElement(statusColumnTextSelector, orderStatus.completed.description);
			await expect(page).not.toMatchElement(statusColumnTextSelector, orderStatus.cancelled.description);
			await expect(page).not.toMatchElement(statusColumnTextSelector, orderStatus.refunded.description);
			await expect(page).not.toMatchElement(statusColumnTextSelector, orderStatus.onHold.description);
		});

		it('should filter by All', async () => {
			await merchant.openAllOrdersView();
			// Make sure all the order statuses that were created show in this list
			await clickFilter('.all');
			await expect(page).toMatchElement(statusColumnTextSelector, orderStatus.pending.description);
			await expect(page).toMatchElement(statusColumnTextSelector, orderStatus.processing.description);
			await expect(page).toMatchElement(statusColumnTextSelector, orderStatus.completed.description);
			await expect(page).toMatchElement(statusColumnTextSelector, orderStatus.cancelled.description);
			await expect(page).toMatchElement(statusColumnTextSelector, orderStatus.refunded.description);
			await expect(page).toMatchElement(statusColumnTextSelector, orderStatus.onHold.description);
			await expect(page).toMatchElement(statusColumnTextSelector, orderStatus.failed.description);
		});

	});
};

module.exports = runOrderStatusFiltersTest;
