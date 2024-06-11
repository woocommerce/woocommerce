/**
 * External dependencies
 */
import { render } from '@testing-library/react';
import { useEditorContext } from '@woocommerce/base-context';
import { formatShippingAddress } from '@woocommerce/base-utils';
import { useSelect } from '@wordpress/data';
import ShippingAddress from '@woocommerce/base-components/cart-checkout/totals/shipping/shipping-address';

jest.mock( '@woocommerce/base-utils', () => ( {
	...jest.requireActual( '@woocommerce/base-utils' ),
	formatShippingAddress: jest.fn(),
} ) );

jest.mock( '@woocommerce/base-context', () => ( {
	...jest.requireActual( '@woocommerce/base-context' ),
	useEditorContext: jest.fn(),
} ) );

jest.mock( '@wordpress/data', () => ( {
	...jest.requireActual( '@wordpress/data' ),
	useSelect: jest.fn(),
} ) );

describe( 'ShippingAddress', () => {
	const setShippingCalculatorLabel = jest.fn();
	const setShippingCalculatorAddress = jest.fn();
	const shippingAddress = {
		first_name: 'John',
		last_name: 'Doe',
		company: 'Company',
		address_1: '123 Main St',
		address_2: '',
		city: 'San Francisco',
		state: 'CA',
		postcode: '94107',
		country: 'US',
		email: 'john.doe@company',
		phone: '+1234567890',
	};

	beforeEach( () => {
		jest.clearAllMocks();
	} );

	it( 'should set shipping calculator label and address for a formatted address', () => {
		( useEditorContext as jest.Mock ).mockReturnValue( {
			isEditor: false,
		} );
		( useSelect as jest.Mock ).mockReturnValue( false );
		( formatShippingAddress as jest.Mock ).mockReturnValue(
			'123 Main St, San Francisco, CA 94107, US'
		);

		render(
			<ShippingAddress
				setShippingCalculatorLabel={ setShippingCalculatorLabel }
				setShippingCalculatorAddress={ setShippingCalculatorAddress }
				shippingAddress={ shippingAddress }
				hasRates={ true }
			/>
		);

		expect( setShippingCalculatorLabel ).toHaveBeenCalledWith(
			'Delivers to'
		);
		expect( setShippingCalculatorAddress ).toHaveBeenCalledWith(
			'123 Main St, San Francisco, CA 94107, US'
		);
	} );

	it( 'should not set shipping calculator label and address when in editor and no formatted address', () => {
		( useEditorContext as jest.Mock ).mockReturnValue( { isEditor: true } );
		( useSelect as jest.Mock ).mockReturnValue( false );
		( formatShippingAddress as jest.Mock ).mockReturnValue( '' );

		render(
			<ShippingAddress
				setShippingCalculatorLabel={ setShippingCalculatorLabel }
				setShippingCalculatorAddress={ setShippingCalculatorAddress }
				shippingAddress={ shippingAddress }
				hasRates={ true }
			/>
		);

		expect( setShippingCalculatorLabel ).not.toHaveBeenCalled();
		expect( setShippingCalculatorAddress ).not.toHaveBeenCalled();
	} );

	it( 'should set shipping calculator label to "No delivery options available" when there are no rates', () => {
		( useEditorContext as jest.Mock ).mockReturnValue( {
			isEditor: false,
		} );
		( useSelect as jest.Mock ).mockReturnValue( false );
		( formatShippingAddress as jest.Mock ).mockReturnValue(
			'123 Main St, San Francisco, CA 94107, US'
		);

		render(
			<ShippingAddress
				setShippingCalculatorLabel={ setShippingCalculatorLabel }
				setShippingCalculatorAddress={ setShippingCalculatorAddress }
				shippingAddress={ shippingAddress }
				hasRates={ false }
			/>
		);

		expect( setShippingCalculatorLabel ).toHaveBeenCalledWith(
			'No delivery options available for'
		);
		expect( setShippingCalculatorAddress ).toHaveBeenCalledWith(
			'123 Main St, San Francisco, CA 94107, US'
		);
	} );
} );
