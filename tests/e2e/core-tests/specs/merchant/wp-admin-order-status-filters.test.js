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

const statusColumnTextSelector = 'mark.order-status > span';

// Order Statuses to filter against
const config = require( 'config' );
const pendingPayment = config.get( 'orderstatuses.pendingpayment' );
const processing = config.get( 'orderstatuses.processing' );
const onHold = config.get( 'orderstatuses.onhold' );
const completed = config.get( 'orderstatuses.completed' );
const cancelled = config.get( 'orderstatuses.cancelled' );
const refunded = config.get( 'orderstatuses.refunded' );
const failed = config.get( 'orderstatuses.failed' );

const runOrderStatusFiltersTest = () => {
	describe('WooCommerce Orders > Filter Orders by Status', () => {
		beforeAll(async () => {
			// First, let's login
			await StoreOwnerFlow.login();

			// Next, let's create some orders we can filter against
			await createSimpleOrder(pendingPayment);
			await createSimpleOrder(processing);
			await createSimpleOrder(onHold);
			await createSimpleOrder(completed);
			await createSimpleOrder(cancelled);
			await createSimpleOrder(refunded);
			await createSimpleOrder(failed);
		}, 40000);

		afterAll( async () => {
			// Make sure we're on the all orders view and cleanup the orders we created
			await StoreOwnerFlow.openAllOrdersView();
			await moveAllItemsToTrash();
		});

		it('should filter by Pending payment', async () => {
			await StoreOwnerFlow.openAllOrdersView();
			await clickFilter('.wc-pending');
			await expect(page).toMatchElement(statusColumnTextSelector, { text: pendingPayment });

			// Verify other statuses don't show
			await expect(page).not.toMatchElement(statusColumnTextSelector, { text: processing });
			await expect(page).not.toMatchElement(statusColumnTextSelector, { text: completed });
			await expect(page).not.toMatchElement(statusColumnTextSelector, { text: cancelled });
			await expect(page).not.toMatchElement(statusColumnTextSelector, { text: refunded });
			await expect(page).not.toMatchElement(statusColumnTextSelector, { text: onHold });
			await expect(page).not.toMatchElement(statusColumnTextSelector, { text: failed });
		});

		it('should filter by Processing', async () => {
			await StoreOwnerFlow.openAllOrdersView();
			await clickFilter('.wc-processing');
			await expect(page).toMatchElement(statusColumnTextSelector, { text: processing });

			// Verify other statuses don't show
			await expect(page).not.toMatchElement(statusColumnTextSelector, { text: pendingPayment });
			await expect(page).not.toMatchElement(statusColumnTextSelector, { text: completed });
			await expect(page).not.toMatchElement(statusColumnTextSelector, { text: cancelled });
			await expect(page).not.toMatchElement(statusColumnTextSelector, { text: refunded });
			await expect(page).not.toMatchElement(statusColumnTextSelector, { text: onHold });
			await expect(page).not.toMatchElement(statusColumnTextSelector, { text: failed });
		});

		it('should filter by On hold', async () => {
			await StoreOwnerFlow.openAllOrdersView();
			await clickFilter('.wc-on-hold');
			await expect(page).toMatchElement(statusColumnTextSelector, { text: onHold });

			// Verify other statuses don't show
			await expect(page).not.toMatchElement(statusColumnTextSelector, { text: pendingPayment });
			await expect(page).not.toMatchElement(statusColumnTextSelector, { text: processing });
			await expect(page).not.toMatchElement(statusColumnTextSelector, { text: completed });
			await expect(page).not.toMatchElement(statusColumnTextSelector, { text: cancelled });
			await expect(page).not.toMatchElement(statusColumnTextSelector, { text: refunded });
			await expect(page).not.toMatchElement(statusColumnTextSelector, { text: failed });
		});

		it('should filter by Completed', async () => {
			await StoreOwnerFlow.openAllOrdersView();
			await clickFilter('.wc-completed');
			await expect(page).toMatchElement(statusColumnTextSelector, { text: completed });

			// Verify other statuses don't show
			await expect(page).not.toMatchElement(statusColumnTextSelector, { text: pendingPayment });
			await expect(page).not.toMatchElement(statusColumnTextSelector, { text: processing });
			await expect(page).not.toMatchElement(statusColumnTextSelector, { text: cancelled });
			await expect(page).not.toMatchElement(statusColumnTextSelector, { text: refunded });
			await expect(page).not.toMatchElement(statusColumnTextSelector, { text: onHold });
			await expect(page).not.toMatchElement(statusColumnTextSelector, { text: failed });
		});

		it('should filter by Cancelled', async () => {
			await StoreOwnerFlow.openAllOrdersView();
			await clickFilter('.wc-cancelled');
			await expect(page).toMatchElement(statusColumnTextSelector, { text: cancelled });

			// Verify other statuses don't show
			await expect(page).not.toMatchElement(statusColumnTextSelector, { text: pendingPayment });
			await expect(page).not.toMatchElement(statusColumnTextSelector, { text: processing });
			await expect(page).not.toMatchElement(statusColumnTextSelector, { text: completed });
			await expect(page).not.toMatchElement(statusColumnTextSelector, { text: refunded });
			await expect(page).not.toMatchElement(statusColumnTextSelector, { text: onHold });
			await expect(page).not.toMatchElement(statusColumnTextSelector, { text: failed });
		});

		it('should filter by Refunded', async () => {
			await StoreOwnerFlow.openAllOrdersView();
			await clickFilter('.wc-refunded');
			await expect(page).toMatchElement(statusColumnTextSelector, { text: refunded });

			// Verify other statuses don't show
			await expect(page).not.toMatchElement(statusColumnTextSelector, { text: pendingPayment });
			await expect(page).not.toMatchElement(statusColumnTextSelector, { text: processing });
			await expect(page).not.toMatchElement(statusColumnTextSelector, { text: completed });
			await expect(page).not.toMatchElement(statusColumnTextSelector, { text: cancelled });
			await expect(page).not.toMatchElement(statusColumnTextSelector, { text: onHold });
			await expect(page).not.toMatchElement(statusColumnTextSelector, { text: failed });
		});

		it('should filter by Failed', async () => {
			await StoreOwnerFlow.openAllOrdersView();
			await clickFilter('.wc-failed');
			await expect(page).toMatchElement(statusColumnTextSelector, { text: failed });

			// Verify other statuses don't show
			await expect(page).not.toMatchElement(statusColumnTextSelector, { text: pendingPayment });
			await expect(page).not.toMatchElement(statusColumnTextSelector, { text: processing });
			await expect(page).not.toMatchElement(statusColumnTextSelector, { text: completed });
			await expect(page).not.toMatchElement(statusColumnTextSelector, { text: cancelled });
			await expect(page).not.toMatchElement(statusColumnTextSelector, { text: refunded });
			await expect(page).not.toMatchElement(statusColumnTextSelector, { text: onHold });
		});

		it('should filter by All', async () => {
			await StoreOwnerFlow.openAllOrdersView();
			// Make sure all the order statuses that were created show in this list
			await clickFilter('.all');
			await expect(page).toMatchElement(statusColumnTextSelector, { text: pendingPayment });
			await expect(page).toMatchElement(statusColumnTextSelector, { text: processing });
			await expect(page).toMatchElement(statusColumnTextSelector, { text: completed });
			await expect(page).toMatchElement(statusColumnTextSelector, { text: cancelled });
			await expect(page).toMatchElement(statusColumnTextSelector, { text: refunded });
			await expect(page).toMatchElement(statusColumnTextSelector, { text: onHold });
			await expect(page).toMatchElement(statusColumnTextSelector, { text: failed });
		});

	});
};

module.exports = runOrderStatusFiltersTest;
