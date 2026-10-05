import { beforeEach, expect, it, vi, type Mock } from 'vitest';

/**
 * External dependencies
 */
import { render, screen, fireEvent } from '@testing-library/react';
import { TaskType } from '@woocommerce/data';
import { useSelect } from '@wordpress/data';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import { Tax } from '..';
vi.mock( '@wordpress/data', async () => {
	const mock = {
		...( await vi.importActual( '@wordpress/data' ) ),
		useSelect: vi.fn(),
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
vi.mock( '@woocommerce/tracks', () => {
	const mock = {
		recordEvent: vi.fn(),
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
vi.mock( '~/utils/features', () => {
	const mock = {
		isFeatureEnabled: vi.fn(),
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
const fakeTask: {
	additionalData: {
		[ key: string ]: boolean | string | string[];
	};
} = {
	additionalData: {},
};
beforeEach( () => {
	fakeTask.additionalData = {
		woocommerceTaxCountries: [ 'US' ],
	};
	( useSelect as Mock ).mockImplementation( () => ( {
		generalSettings: {
			woocommerce_default_country: 'US',
		},
	} ) );
} );
const assertWooCommerceTaxIsNotRecommended = () => {
	expect(
		screen.queryByText( 'Choose a tax partner' )
	).not.toBeInTheDocument();
	expect(
		screen.getByText(
			'Head over to the tax rate settings screen to configure your tax rates'
		)
	).toBeInTheDocument();
};
it( 'renders WooCommerce Tax (powered by WCS&T)', () => {
	render(
		<Tax
			onComplete={ () => {} }
			query={ {} }
			task={ fakeTask as TaskType }
		/>
	);
	expect( screen.getByText( 'Choose a tax partner' ) ).toBeInTheDocument();
} );
it( `does not render WooCommerce Tax (powered by WCS&T) if the WooCommerce Tax plugin is active`, () => {
	fakeTask.additionalData.woocommerceTaxActivated = true;
	render(
		<Tax
			onComplete={ () => {} }
			query={ {} }
			task={ fakeTask as TaskType }
		/>
	);
	assertWooCommerceTaxIsNotRecommended();
} );
it( `does not render WooCommerce Tax (powered by WCS&T) if the WooCommerce Shipping plugin is active`, () => {
	fakeTask.additionalData.woocommerceShippingActivated = true;
	render(
		<Tax
			onComplete={ () => {} }
			query={ {} }
			task={ fakeTask as TaskType }
		/>
	);
	assertWooCommerceTaxIsNotRecommended();
} );
it( `does not render WooCommerce Tax (powered by WCS&T) if the TaxJar plugin is active`, () => {
	fakeTask.additionalData.taxJarActivated = true;
	render(
		<Tax
			onComplete={ () => {} }
			query={ {} }
			task={ fakeTask as TaskType }
		/>
	);
	assertWooCommerceTaxIsNotRecommended();
} );
it( 'does not render WooCommerce Tax (powered by WCS&T) if not in a supported country', () => {
	( useSelect as Mock ).mockReturnValue( {
		generalSettings: {
			woocommerce_default_country: 'FOO',
		},
	} );
	render(
		<Tax
			onComplete={ () => {} }
			query={ {} }
			task={ fakeTask as TaskType }
		/>
	);
	assertWooCommerceTaxIsNotRecommended();
} );
it( 'should trigger event tasklist_tax_visit_marketplace_click when clicking the WooCommerce Marketplace link', () => {
	render(
		<Tax
			onComplete={ () => {} }
			query={ {} }
			task={ fakeTask as TaskType }
		/>
	);
	fireEvent.click( screen.getByText( 'the WooCommerce Marketplace' ) );
	expect( recordEvent ).toHaveBeenCalledWith(
		'tasklist_tax_visit_marketplace_click',
		{}
	);
} );
it( 'should navigate to the marketplace when clicking the WooCommerce Marketplace link', async () => {
	const { isFeatureEnabled } = await import( '~/utils/features' );
	( isFeatureEnabled as Mock ).mockReturnValue( true );
	const mockLocation = {
		href: 'test',
	} as Location;
	mockLocation.href = 'test';
	Object.defineProperty( global.window, 'location', {
		value: mockLocation,
	} );
	render(
		<Tax
			onComplete={ () => {} }
			query={ {} }
			task={ fakeTask as TaskType }
		/>
	);
	fireEvent.click( screen.getByText( 'the WooCommerce Marketplace' ) );
	expect( mockLocation.href ).toContain(
		'admin.php?page=wc-admin&tab=extensions&path=/extensions&category=operations'
	);
} );
