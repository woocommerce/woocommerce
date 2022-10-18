/**
 * External dependencies
 */
import { act, render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { useSelect, useDispatch } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
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
			const createProductShippingClass = jest.fn();
			const invalidateResolution = jest.fn();
			const createErrorNotice = jest.fn();

			function getShippingClassSelect() {
				return screen.getByLabelText(
					__( 'Shipping class', 'woocommerce' )
				);
			}

			async function addNewShippingClass(
				name: string = 'New shipping class'
			) {
				const select = getShippingClassSelect();

				act( () =>
					userEvent.selectOptions(
						select,
						ADD_NEW_SHIPPING_CLASS_OPTION_VALUE
					)
				);

				const dialog = screen.getByRole( 'dialog' );
				const inputName = within( dialog ).getByLabelText(
					__( 'Name', 'woocommerce' )
				);
				const buttonAdd = within( dialog ).getByText(
					__( 'Add', 'woocommerce' )
				);

				await act( async () => userEvent.type( inputName, name ) );
				await act( async () => userEvent.click( buttonAdd ) );
			}

			beforeEach( () => {
				useSelectMock.mockReturnValue( {
					shippingClasses: [ newShippingClass ],
					hasResolvedShippingClasses: true,
				} );

				useDispatchMock.mockReturnValue( {
					createProductShippingClass,
					invalidateResolution,
					createErrorNotice,
				} );

				render(
					<Form initialValues={ {} } validate={ validate }>
						<ProductShippingSection />
					</Form>
				);
			} );

			it( 'should be selected as the current option', async () => {
				createProductShippingClass.mockResolvedValue(
					newShippingClass
				);

				await addNewShippingClass();

				const select = getShippingClassSelect();

				expect( select ).toHaveDisplayValue( [
					newShippingClass.name,
				] );
			} );

			it( 'should show a snackbar message when server responds an error', async () => {
				createProductShippingClass.mockRejectedValue(
					new Error( 'Server Error' )
				);

				await addNewShippingClass();

				expect( createErrorNotice ).toHaveBeenNthCalledWith(
					1,
					__(
						'We couldn’t add this shipping class. Try again in a few seconds.',
						'woocommerce'
					),
					expect.objectContaining( { explicitDismiss: true } )
				);
			} );
		} );
	} );
} );
