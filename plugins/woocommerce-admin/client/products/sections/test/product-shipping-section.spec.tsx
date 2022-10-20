/**
 * External dependencies
 */
import { act, render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { useSelect, useDispatch } from '@wordpress/data';
import { Form } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import { ProductShippingSection } from '../product-shipping-section';
import { validate } from '../../product-validation';
import { ADD_NEW_SHIPPING_CLASS_OPTION_VALUE } from '~/products/constants';

jest.mock( '@woocommerce/tracks', () => ( { recordEvent: jest.fn() } ) );
jest.mock( '@wordpress/data', () => ( {
	...jest.requireActual( '@wordpress/data' ),
	useSelect: jest.fn(),
	useDispatch: jest.fn(),
} ) );

describe( 'ProductShippingSection', () => {
	const useSelectMock = useSelect as jest.Mock;
	const useDispatchMock = useDispatch as jest.Mock;

	beforeEach( () => {
		jest.clearAllMocks();
	} );

	describe( 'when creating a product', () => {
		describe( 'when creating a shipping class', () => {
			const newShippingClass = {
				name: 'New shipping class',
				slug: 'new-shipping-class',
			};
			const createProductShippingClass = jest
				.fn()
				.mockReturnValue( Promise.resolve( newShippingClass ) );
			const invalidateResolution = jest.fn();

			beforeEach( () => {
				useSelectMock.mockReturnValue( {
					shippingClasses: [ newShippingClass ],
					hasResolvedShippingClasses: true,
				} );

				useDispatchMock.mockReturnValue( {
					createProductShippingClass,
					invalidateResolution,
				} );

				render(
					<Form initialValues={ {} } validate={ validate }>
						<ProductShippingSection />
					</Form>
				);
			} );

			it( 'should be selected as the current option', async () => {
				const select = screen.getByLabelText( 'Shipping class' );
				act( () =>
					userEvent.selectOptions(
						select,
						ADD_NEW_SHIPPING_CLASS_OPTION_VALUE
					)
				);

				const dialog = screen.getByRole( 'dialog' );
				const addButton = within( dialog ).getByText( 'Add' );
				await act( async () => userEvent.click( addButton ) );

				expect( select ).toHaveDisplayValue( [
					newShippingClass.name,
				] );
			} );
		} );
	} );
} );
