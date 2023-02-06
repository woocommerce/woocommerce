/**
 * External dependencies
 */
import { act, render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { useSelect, useDispatch } from '@wordpress/data';
import { PartialProduct, ProductShippingClass } from '@woocommerce/data';
import { __ } from '@wordpress/i18n';
import { Form } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import { validate } from '../../product-validation';
import { ADD_NEW_SHIPPING_CLASS_OPTION_VALUE } from '~/products/constants';
//import { ProductShippingSection } from '../product-shipping-section';

// This mock is only used while these tests are skipped, doesn't work
const ProductShippingSection = ( {}: { product?: PartialProduct } ) => (
	<div>Temporary Mock</div>
);

jest.mock( '@woocommerce/tracks', () => ( { recordEvent: jest.fn() } ) );
jest.mock( '@wordpress/data', () => ( {
	...jest.requireActual( '@wordpress/data' ),
	useSelect: jest.fn(),
	useDispatch: jest.fn(),
} ) );

function getShippingClassDialog() {
	return screen.getByRole( 'dialog' );
}

function getShippingClassSelect() {
	return screen.getByLabelText( __( 'Shipping class', 'woocommerce' ) );
}

async function getShippingClassNameInput() {
	const dialog = getShippingClassDialog();
	return within( dialog ).getByLabelText( 'Name', { exact: false } );
}

async function getShippingClassSlugInput() {
	const dialog = getShippingClassDialog();
	return within( dialog ).getByLabelText( __( 'Slug', 'woocommerce' ) );
}

async function openShippingClassDialog() {
	const select = getShippingClassSelect();

	await act( async () =>
		userEvent.selectOptions( select, ADD_NEW_SHIPPING_CLASS_OPTION_VALUE )
	);

	return getShippingClassDialog();
}

async function submitShippingClassDialog() {
	const dialog = getShippingClassDialog();
	const buttonAdd = within( dialog ).getByText( __( 'Add', 'woocommerce' ) );
	await act( async () => userEvent.click( buttonAdd ) );
}

async function addNewShippingClass( name?: string, slug?: string ) {
	await openShippingClassDialog();

	if ( name ) {
		const inputName = await getShippingClassNameInput();
		userEvent.type( inputName, name );
	}

	if ( slug ) {
		const inputSlug = await getShippingClassSlugInput();
		userEvent.type( inputSlug, slug );
	}

	await submitShippingClassDialog();
}

describe.skip( 'ProductShippingSection', () => {
	const useSelectMock = useSelect as jest.Mock;
	const useDispatchMock = useDispatch as jest.Mock;
	const createProductShippingClass = jest.fn();
	const invalidateResolution = jest.fn();
	const createErrorNotice = jest.fn();
	let shippingClasses: Partial< ProductShippingClass >[];

	beforeEach( () => {
		shippingClasses = [];

		useSelectMock.mockReturnValue( {
			shippingClasses,
			hasResolvedShippingClasses: true,
		} );

		useDispatchMock.mockReturnValue( {
			createProductShippingClass,
			invalidateResolution,
			createErrorNotice,
		} );
	} );

	afterEach( () => {
		jest.clearAllMocks();
	} );

	describe( 'when creating a product', () => {
		beforeEach( () => {
			render(
				<Form initialValues={ {} } validate={ validate }>
					<ProductShippingSection />
				</Form>
			);
		} );

		describe( 'when creating a shipping class', () => {
			const newShippingClass = {
				name: 'New shipping class',
				slug: 'new-shipping-class',
			};

			it( 'should be selected as the current option', async () => {
				createProductShippingClass.mockImplementation( ( value ) => {
					shippingClasses.push( value );
					return Promise.resolve( value );
				} );

				await addNewShippingClass(
					newShippingClass.name,
					newShippingClass.slug
				);

				const select = getShippingClassSelect();

				expect( select ).toHaveDisplayValue( [
					newShippingClass.name,
				] );
			} );

			it( 'should show a snackbar message when server responds an error', async () => {
				createProductShippingClass.mockRejectedValue(
					new Error( 'Server Error' )
				);

				await addNewShippingClass( newShippingClass.name );

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

	describe( 'when editing a product', () => {
		const product: PartialProduct = {
			id: 1,
			categories: [
				{
					id: 1,
					name: 'Category 1',
					slug: 'category-1',
				},
			],
		};

		beforeEach( () => {
			render(
				<Form initialValues={ product } validate={ validate }>
					<ProductShippingSection product={ product } />
				</Form>
			);
		} );

		describe( 'when creating a shipping class', () => {
			it( 'should add the first cat as a shipping class only once', async () => {
				const category = product?.categories?.at( 0 );
				const newShippingClass: Partial< ProductShippingClass > = {
					name: category?.name,
					slug: category?.slug,
				};
				createProductShippingClass.mockImplementation( ( value ) => {
					shippingClasses.push( value );
					return Promise.resolve( value );
				} );

				await openShippingClassDialog();

				let inputName = await getShippingClassNameInput();
				expect( inputName ).toHaveValue( newShippingClass.name );

				await submitShippingClassDialog();
				expect( createProductShippingClass ).toHaveBeenNthCalledWith(
					1,
					expect.objectContaining( newShippingClass )
				);

				await openShippingClassDialog();

				inputName = await getShippingClassNameInput();
				expect( inputName ).not.toHaveValue( newShippingClass.name );
			} );
		} );
	} );
} );
