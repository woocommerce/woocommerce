/**
 * Internal dependencies
 */
const {
	createSimpleProduct,
	createCoupon,
	uiUnblocked,
	evalAndClick,
	merchant,
	createOrder,
	utils,
} = require( '@woocommerce/e2e-utils' );

const config = require( 'config' );
const simpleProductPrice = config.has('products.simple.price') ? config.get('products.simple.price') : '9.99';
const discountedPrice = simpleProductPrice - 5.00;

const couponDialogMessage = 'Enter a coupon code to apply. Discounts are applied to line totals, before taxes.';

let couponCode;
let orderId;
let productId;

const runOrderApplyCouponTest = () => {
	describe('WooCommerce Orders > Apply coupon', () => {
		beforeAll(async () => {
			productId = await createSimpleProduct();
			couponCode = await createCoupon();
			orderId = await createOrder( { productId, status: 'pending' } );

			await merchant.login();
			await merchant.goToOrder( orderId );
			await page.removeAllListeners('dialog');

			// Make sure the simple product price is greater than the coupon amount
			await expect(Number(simpleProductPrice)).toBeGreaterThan(5.00);
		} );

		it('can apply a coupon', async () => {
			await page.waitForSelector('button.add-coupon');
			const couponDialog = await expect(page).toDisplayDialog(async () => {
				await evalAndClick('button.add-coupon');
			});
			expect(couponDialog.message()).toMatch(couponDialogMessage);

			// Accept the dialog with the coupon code
			await couponDialog.accept(couponCode);
			await uiUnblocked();

			// Verify the coupon list is showing
			await page.waitForSelector('.wc-used-coupons');
			await expect(page).toMatchElement('.wc_coupon_list', { text: 'Coupon(s)' });
			await expect(page).toMatchElement('.wc_coupon_list li.code.editable', { text: couponCode.toLowerCase() });

			// Check that the coupon has been applied
			await expect(page).toMatchElement('.wc-order-item-discount', { text: '5.00' });
			await expect(page).toMatchElement('.line_cost > .view > .woocommerce-Price-amount', { text: discountedPrice });
		});

		it('can remove a coupon', async () => {
			// Make sure we have a coupon on the page to use
			await page.waitForSelector('.wc-used-coupons');
			await expect(page).toMatchElement('.wc_coupon_list li.code.editable', { text: couponCode.toLowerCase() });
			await evalAndClick( 'a.remove-coupon' );

			await uiUnblocked();

			await utils.waitForTimeout( 2000 ); // to avoid flakyness

			// Verify the coupon pricing has been removed
			await expect(page).not.toMatchElement('.wc_coupon_list li.code.editable', { text: couponCode.toLowerCase() });
			await expect(page).not.toMatchElement('.wc-order-item-discount', { text: '5.00' });
			await expect(page).not.toMatchElement('.line-cost .view .woocommerce-Price-amount', { text: discountedPrice });

			// Verify the original price is the order total
			await expect(page).toMatchElement('.line_cost > .view > .woocommerce-Price-amount', { text: simpleProductPrice });
		});

	});

};

module.exports = runOrderApplyCouponTest;
