/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import { Edit } from '../edit';

jest.mock( '@wordpress/block-editor', () => ( {
	InspectorControls: jest.fn( ( { children } ) => <div>{ children }</div> ),
	RichText: jest.fn( ( { value, placeholder } ) => (
		<span>{ value || placeholder }</span>
	) ),
	useBlockProps: Object.assign(
		jest.fn( () => ( { className: '' } ) ),
		{
			save: jest.fn( () => ( {} ) ),
		}
	),
} ) );

jest.mock( '@wordpress/data', () => ( {
	...jest.requireActual( '@wordpress/data' ),
	useSelect: jest.fn( () => 2 ),
} ) );

jest.mock( '@woocommerce/editor-components/page-selector', () => () => null );

jest.mock( '@woocommerce/base-components/cart-checkout', () => ( {
	PlaceOrderButton: jest.fn( ( { label } ) => <button>{ label }</button> ),
	ReturnToCartButton: jest.fn( ( { children } ) => (
		<span>{ children }</span>
	) ),
} ) );

jest.mock( '@woocommerce/block-settings', () => ( {
	CHECKOUT_PAGE_ID: 1,
} ) );

const expectReturnToCartVisible = () => {
	expect( screen.getByText( 'Return to Cart' ) ).toBeVisible();
};

const expectReturnToCartHidden = () => {
	expect( screen.queryByText( 'Return to Cart' ) ).not.toBeInTheDocument();
};

describe( 'Checkout Actions editor', () => {
	it.each( [
		{ initialValue: false, expectedValue: true },
		{ initialValue: true, expectedValue: false },
	] )(
		'maps showReturnToCart=$initialValue to $expectedValue and previews the current state',
		async ( { initialValue, expectedValue } ) => {
			const user = userEvent.setup();
			const setAttributes = jest.fn();

			render(
				<Edit
					attributes={ {
						cartPageId: 1,
						showReturnToCart: initialValue,
						placeOrderButtonLabel: 'Place Order',
						priceSeparator: '·',
						returnToCartButtonLabel: 'Return to Cart',
					} }
					setAttributes={ setAttributes }
				/>
			);

			const returnToCartToggle = screen.getByRole( 'checkbox', {
				name: 'Show a "Return to Cart" link',
			} );
			expect( returnToCartToggle ).toHaveProperty(
				'checked',
				initialValue
			);
			if ( initialValue ) {
				expectReturnToCartVisible();
			} else {
				expectReturnToCartHidden();
			}

			await user.click( returnToCartToggle );

			expect( setAttributes ).toHaveBeenCalledTimes( 1 );
			expect( setAttributes ).toHaveBeenCalledWith( {
				showReturnToCart: expectedValue,
			} );
		}
	);
} );
