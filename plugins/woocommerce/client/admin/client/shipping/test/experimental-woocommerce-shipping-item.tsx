/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';
import { useDispatch } from '@wordpress/data';

/**
 * Internal dependencies
 */
import WooCommerceShippingItem from '../experimental-woocommerce-shipping-item';
jest.mock( '@wordpress/data', () => ( {
	...jest.requireActual( '@wordpress/data' ),
	useDispatch: jest.fn(),
} ) );

jest.mock( '@woocommerce/admin-layout', () => {
	const mockContext = {
		layoutPath: [ 'root' ],
		layoutString: 'root',
		extendLayout: () => {},
		isDescendantOf: () => false,
	};
	return {
		...jest.requireActual( '@woocommerce/admin-layout' ),
		useLayoutContext: jest.fn().mockReturnValue( mockContext ),
		useExtendLayout: jest.fn().mockReturnValue( mockContext ),
	};
} );

describe( 'WooCommerceShippingItem', () => {
	const defaultProps = {
		pluginsBeingSetup: [] as string[],
		onSetupClick: jest.fn( () => Promise.resolve() ),
	};

	beforeEach( () => {
		( useDispatch as jest.Mock ).mockReturnValue( {
			createSuccessNotice: jest.fn(),
		} );
	} );

	it( 'should render WC Shipping item with CTA = "Get started" when WC Shipping is not installed', () => {
		render(
			<WooCommerceShippingItem
				isPluginInstalled={ false }
				{ ...defaultProps }
			/>
		);

		expect(
			screen.queryByText( 'WooCommerce Shipping' )
		).toBeInTheDocument();

		expect(
			screen.queryByRole( 'button', { name: 'Get started' } )
		).toBeInTheDocument();
	} );

	it( 'should render WC Shipping item with CTA = "Activate" when WC Shipping is installed', () => {
		render(
			<WooCommerceShippingItem
				isPluginInstalled={ true }
				{ ...defaultProps }
			/>
		);

		expect(
			screen.queryByText( 'WooCommerce Shipping' )
		).toBeInTheDocument();

		expect(
			screen.queryByRole( 'button', { name: 'Activate' } )
		).toBeInTheDocument();
	} );

	it( 'should call onSetupClick when clicking setup button', () => {
		const onSetupClick = jest.fn( () => Promise.resolve() );
		render(
			<WooCommerceShippingItem
				isPluginInstalled={ false }
				pluginsBeingSetup={ [] }
				onSetupClick={ onSetupClick }
			/>
		);

		screen.queryByRole( 'button', { name: 'Get started' } )?.click();
		expect( onSetupClick ).toHaveBeenCalledWith( [
			'woocommerce-shipping',
		] );
	} );
} );
