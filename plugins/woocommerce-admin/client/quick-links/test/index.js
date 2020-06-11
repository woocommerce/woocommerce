/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { toHaveAttribute } from '@testing-library/jest-dom/matchers';

/**
 * Internal dependencies
 */
import QuickLinks from '../index';

expect.extend( { toHaveAttribute } );

describe( 'QuickLinks', () => {
	it( 'should build href correctly for a `wc-admin` item', () => {
		render( <QuickLinks getSetting={ () => {} } /> );

		const marketingItem = screen.getByRole( 'menuitem', {
			name: 'Market my store',
		} );

		expect( marketingItem ).toHaveAttribute(
			'href',
			'admin.php?page=wc-admin&path=%2Fmarketing'
		);
	} );

	it( 'should build href correctly for a `wp-admin` item', () => {
		render( <QuickLinks getSetting={ () => {} } /> );

		const addProductsItem = screen.getByRole( 'menuitem', {
			name: 'Add products',
		} );

		expect( addProductsItem ).toHaveAttribute(
			'href',
			'post-new.php?post_type=product'
		);
	} );

	it( 'should build href correctly for a `wc-settings` item', () => {
		render( <QuickLinks getSetting={ () => {} } /> );

		const shippingSettingsItem = screen.getByRole( 'menuitem', {
			name: 'Shipping settings',
		} );

		expect( shippingSettingsItem ).toHaveAttribute(
			'href',
			'admin.php?page=wc-settings&tab=shipping'
		);
	} );

	it( 'should call `recordEvent` when a `wc-admin` item is clicked', () => {
		const recordEvent = jest.fn();

		render(
			<QuickLinks
				getSetting={ () => {} }
				recordEvent={ recordEvent }
				// Prevent jsdom "Error: Not implemented: navigation" in test output
				onItemClick={ () => false }
			/>
		);

		userEvent.click(
			screen.getByRole( 'menuitem', { name: 'Market my store' } )
		);

		const homeQuickLinksClickEventName = 'home_quick_links_click';
		const propsWithMarketingTaskName = { task_name: 'marketing' };

		expect( recordEvent ).toHaveBeenCalledWith(
			homeQuickLinksClickEventName,
			propsWithMarketingTaskName
		);
	} );

	it( 'should call `recordEvent` when a `wp-admin` item is clicked', () => {
		const recordEvent = jest.fn();

		render(
			<QuickLinks
				getSetting={ () => {} }
				recordEvent={ recordEvent }
				// Prevent jsdom "Error: Not implemented: navigation" in test output
				onItemClick={ () => false }
			/>
		);

		userEvent.click(
			screen.getByRole( 'menuitem', { name: 'Add products' } )
		);

		const homeQuickLinksClickEventName = 'home_quick_links_click';
		const propsWithAddProductsTaskName = { task_name: 'add-products' };

		expect( recordEvent ).toHaveBeenCalledWith(
			homeQuickLinksClickEventName,
			propsWithAddProductsTaskName
		);
	} );

	it( 'should call `recordEvent` when a `wc-settings` item is clicked', () => {
		const recordEvent = jest.fn();

		render(
			<QuickLinks
				getSetting={ () => {} }
				recordEvent={ recordEvent }
				// Prevent jsdom "Error: Not implemented: navigation" in test output
				onItemClick={ () => false }
			/>
		);

		userEvent.click(
			screen.getByRole( 'menuitem', { name: 'Shipping settings' } )
		);

		const homeQuickLinksClickEventName = 'home_quick_links_click';
		const propsWithShippingSettingsTaskName = {
			task_name: 'shipping-settings',
		};

		expect( recordEvent ).toHaveBeenCalledWith(
			homeQuickLinksClickEventName,
			propsWithShippingSettingsTaskName
		);
	} );

	it( 'should call `recordEvent` when an `external` item is clicked', () => {
		const recordEvent = jest.fn();

		render(
			<QuickLinks
				getSetting={ () => {} }
				recordEvent={ recordEvent }
				// Prevent jsdom "Error: Not implemented: navigation" in test output
				onItemClick={ () => false }
			/>
		);

		userEvent.click(
			screen.getByRole( 'menuitem', { name: 'Get support' } )
		);

		const homeQuickLinksClickEventName = 'home_quick_links_click';
		const propsWithSupportTaskName = { task_name: 'support' };

		expect( recordEvent ).toHaveBeenCalledWith(
			homeQuickLinksClickEventName,
			propsWithSupportTaskName
		);
	} );

	it( 'should call `getSetting` to determine the frontend url', () => {
		const getSetting = jest.fn( () => 'https://example.com' );

		render(
			<QuickLinks getSetting={ getSetting } recordEvent={ () => {} } />
		);

		expect( getSetting ).toHaveBeenCalledWith( 'siteUrl' );
	} );
} );
