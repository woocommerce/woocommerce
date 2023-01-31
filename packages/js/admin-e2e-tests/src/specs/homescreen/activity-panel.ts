/**
 * External dependencies
 */
import { afterAll, beforeAll, describe, it } from '@jest/globals';
// TODO fix the type for this module.
// eslint-disable-next-line
import { createSimpleProduct, withRestApi } from '@woocommerce/e2e-utils';

/**
 * Internal dependencies
 */
import { Login } from '../../pages/Login';
import { OnboardingWizard } from '../../pages/OnboardingWizard';
import { WcHomescreen } from '../../pages/WcHomescreen';
import {
	createOrder,
	removeAllOrders,
	unhideTaskList,
	runActionScheduler,
	updateOption,
	resetWooCommerceState,
} from '../../fixtures';
import { OrdersActivityPanel } from '../../elements/OrdersActivityPanel';
import { addReviewToProduct, waitForElementByText } from '../../utils/actions';

const simpleProductName = 'Simple order';
export const testAdminHomescreenActivityPanel = () => {
	describe( 'Homescreen activity panel', () => {
		const profileWizard = new OnboardingWizard( page );
		const homeScreen = new WcHomescreen( page );
		const ordersPanel = new OrdersActivityPanel( page );
		const login = new Login( page );

		beforeAll( async () => {
			await login.login();
			await resetWooCommerceState();
			await profileWizard.navigate();
			await profileWizard.skipStoreSetup();

			await homeScreen.isDisplayed();
			await homeScreen.possiblyDismissWelcomeModal();
		} );

		afterAll( async () => {
			await withRestApi.deleteAllProducts();
			await removeAllOrders();
			await unhideTaskList( 'setup' );
			await runActionScheduler();
			await updateOption( 'woocommerce_task_list_hidden', 'no' );
			await login.logout();
		} );

		it( 'should not show activity panel while task list is displayed', async () => {
			await expect( homeScreen.isTaskListDisplayed() ).resolves.toBe(
				true
			);
			await expect( homeScreen.isActivityPanelShown() ).resolves.toBe(
				false
			);
		} );

		it( 'should not show panels when there are no orders or products yet with task list hidden', async () => {
			await homeScreen.hideTaskList();
			await expect( homeScreen.isTaskListDisplayed() ).resolves.toBe(
				false
			);
			await expect( homeScreen.isActivityPanelShown() ).resolves.toBe(
				false
			);
		} );

		it( 'should show Reviews panel when we have at-least one product', async () => {
			const productId = await createSimpleProduct(
				simpleProductName,
				'9.99'
			);
			await addReviewToProduct( productId, simpleProductName );
			await homeScreen.navigate();
			await homeScreen.isDisplayed();
			const activityPanels = await homeScreen.getActivityPanels();
			expect( activityPanels ).toHaveLength( 1 );
			expect( activityPanels ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( { title: 'Reviews' } ),
				] )
			);
		} );

		it( 'should show Orders and Stock panels when at-least one order is added', async () => {
			await createOrder();
			await page.reload( {
				waitUntil: [ 'networkidle0', 'domcontentloaded' ],
			} );
			const activityPanels = await homeScreen.getActivityPanels();
			expect( activityPanels ).toHaveLength( 3 );
			expect( activityPanels ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( { title: 'Orders' } ),
				] )
			);
			expect( activityPanels ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( { title: 'Stock' } ),
				] )
			);
		} );

		describe( 'Orders panel', () => {
			it( 'should show: "you have fullfilled all your orders" when expanding Orders panel if no actionable orders', async () => {
				await homeScreen.expandActivityPanel( 'Orders' );
				await waitForElementByText(
					'h4',
					'You’ve fulfilled all your orders'
				);
				await expect( page ).toMatchElement( 'h4', {
					text: 'You’ve fulfilled all your orders',
				} );
			} );

			it( 'should show actionable Orders when expanding Orders panel', async () => {
				const order1 = await createOrder( 'processing' );
				const order2 = await createOrder( 'on-hold' );
				await homeScreen.navigate();
				await homeScreen.expandActivityPanel( 'Orders' );
				const orders = await ordersPanel.getDisplayedOrders();
				expect( orders ).toHaveLength( 2 );
				expect( orders ).toContain( `Order #${ order1.id }` );
				expect( orders ).toContain( `Order #${ order2.id }` );
			} );
		} );
	} );
};
