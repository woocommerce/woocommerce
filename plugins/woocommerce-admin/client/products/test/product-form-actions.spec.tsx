/**
 * External dependencies
 */
import { render, waitFor, screen, within } from '@testing-library/react';
import { Fragment } from '@wordpress/element';
import { Form, FormContext } from '@woocommerce/components';
import { Product } from '@woocommerce/data';
import { recordEvent } from '@woocommerce/tracks';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import { ProductFormActions } from '../product-form-actions';
import { validate } from '../product-validation';

const createProductWithStatus = jest.fn();
const updateProductWithStatus = jest.fn();
const copyProductWithStatus = jest.fn();
const deleteProductAndRedirect = jest.fn();
const onPublishCES = jest.fn().mockResolvedValue( {} );
const onDraftCES = jest.fn().mockResolvedValue( {} );

jest.mock( '@wordpress/plugins', () => ( { registerPlugin: jest.fn() } ) );

jest.mock( '@wordpress/data', () => ( {
	...jest.requireActual( '@wordpress/data' ),
	useDispatch: jest.fn().mockReturnValue( { updateOptions: jest.fn() } ),
	useSelect: jest.fn().mockReturnValue( { productCESAction: 'hide' } ),
} ) );
jest.mock( '@woocommerce/tracks', () => ( { recordEvent: jest.fn() } ) );
jest.mock(
	'~/customer-effort-score-tracks/use-product-mvp-ces-footer',
	() => ( {
		useProductMVPCESFooter: () => ( {
			onPublish: onPublishCES,
			onSaveDraft: onDraftCES,
		} ),
	} )
);
jest.mock( '@woocommerce/admin-layout', () => ( {
	WooHeaderItem: ( props: { children: () => React.ReactElement } ) => (
		<Fragment { ...props }>{ props.children() }</Fragment>
	),
} ) );
jest.mock( '@woocommerce/product-editor', () => {
	return {
		__experimentalUseProductHelper: () => ( {
			createProductWithStatus,
			updateProductWithStatus,
			copyProductWithStatus,
			deleteProductAndRedirect,
		} ),
	};
} );
jest.mock( '~/hooks/usePreventLeavingPage' );
jest.mock(
	'~/customer-effort-score-tracks/use-customer-effort-score-exit-page-tracker',
	() => ( {
		useCustomerEffortScoreExitPageTracker: jest.fn(),
	} )
);

describe( 'ProductFormActions', () => {
	beforeEach( () => {
		jest.clearAllMocks();
	} );

	it( 'should render the form action buttons', () => {
		const { queryByText } = render(
			<Form initialValues={ {} }>
				<ProductFormActions />
			</Form>
		);
		expect( queryByText( 'Save draft' ) ).toBeInTheDocument();
		expect( queryByText( 'Preview' ) ).toBeInTheDocument();
		expect( queryByText( 'Publish' ) ).toBeInTheDocument();
	} );

	it( 'should have a publish dropdown button with two other actions', () => {
		render(
			<Form initialValues={ {} }>
				<ProductFormActions />
			</Form>
		);
		screen.getByLabelText( 'Publish options' ).click();
		expect(
			screen.queryByText( 'Publish & duplicate' )
		).toBeInTheDocument();
		expect(
			screen.queryByText( 'Copy to a new draft' )
		).toBeInTheDocument();
	} );

	describe( 'with new product', () => {
		it( 'should not have the Move to trash button present', () => {
			render(
				<Form initialValues={ {} }>
					<ProductFormActions />
				</Form>
			);

			screen.getByLabelText( 'Publish options' ).click();
			expect(
				screen.queryByText( 'Move to trash' )
			).not.toBeInTheDocument();
		} );

		it( 'should trigger createProductWithStatus and the product_edit track when Save draft is clicked', () => {
			const product = { name: 'Name' };
			const { queryByText } = render(
				<Form initialValues={ product }>
					<ProductFormActions />
				</Form>
			);
			queryByText( 'Save draft' )?.click();
			expect( createProductWithStatus ).toHaveBeenCalledWith(
				product,
				'draft'
			);
			expect( recordEvent ).toHaveBeenCalledWith( 'product_edit', {
				new_product_page: true,
				product_id: undefined,
				product_type: undefined,
				is_downloadable: undefined,
				is_virtual: undefined,
				manage_stock: undefined,
			} );
		} );

		it( 'should trigger createProductWithStatus and the product_update track when Publish is clicked', () => {
			const product = { name: 'Name' };
			const { queryByText } = render(
				<Form initialValues={ product }>
					<ProductFormActions />
				</Form>
			);
			queryByText( 'Publish' )?.click();
			expect( createProductWithStatus ).toHaveBeenCalledWith(
				product,
				'publish'
			);
			expect( recordEvent ).toHaveBeenCalledWith( 'product_update', {
				new_product_page: true,
				product_id: undefined,
				product_type: undefined,
				is_downloadable: undefined,
				is_virtual: undefined,
				manage_stock: undefined,
			} );
		} );

		it( 'should have the Preview button disabled', () => {
			const product = { name: 'Name' };
			const { queryByText } = render(
				<Form initialValues={ product }>
					<ProductFormActions />
				</Form>
			);
			const previewButton = queryByText( 'Preview' );
			expect( ( previewButton as HTMLButtonElement ).disabled ).toEqual(
				true
			);
		} );
	} );

	describe( 'with existing product', () => {
		it( 'should have the Move to trash button present', () => {
			const product: Partial< Product > = {
				id: 5,
				name: 'Name',
			};
			render(
				<Form initialValues={ product }>
					<ProductFormActions />
				</Form>
			);

			screen.getByLabelText( 'Publish options' ).click();
			expect( screen.queryByText( 'Move to trash' ) ).toBeInTheDocument();
		} );

		it( 'The publish button should be renamed to Update when product is published', () => {
			const { queryByText } = render(
				<Form< Partial< Product > >
					initialValues={ { id: 5, name: 'test', status: 'publish' } }
				>
					<ProductFormActions />
				</Form>
			);
			expect( queryByText( 'Update' ) ).toBeInTheDocument();
		} );

		it( 'should trigger updateProductWithStatus and the product_edit track when Save draft is clicked', () => {
			const product: Partial< Product > = {
				id: 5,
				name: 'Name',
				type: 'simple',
				status: 'draft',
				downloadable: false,
				virtual: false,
				manage_stock: true,
			};
			const { queryByText, getByLabelText } = render(
				<Form< Partial< Product > > initialValues={ product }>
					{ ( { getInputProps }: FormContext< Product > ) => {
						return (
							<>
								<label htmlFor="product-name">Name</label>
								<input
									id="product-name"
									name="name"
									{ ...getInputProps< string >( 'name' ) }
								/>
								<ProductFormActions />
							</>
						);
					} }
				</Form>
			);
			userEvent.type(
				getByLabelText( 'Name' ),
				'{esc}{space}Update',
				{}
			);
			queryByText( 'Save draft' )?.click();
			expect( updateProductWithStatus ).toHaveBeenCalledWith(
				product.id,
				{ ...product, name: 'Name Update' },
				'draft'
			);
			expect( recordEvent ).toHaveBeenCalledWith( 'product_edit', {
				new_product_page: true,
				product_id: 5,
				product_type: 'simple',
				is_downloadable: false,
				is_virtual: false,
				manage_stock: true,
			} );
		} );

		it( 'should trigger updateProductWithStatus and the product_update track when Publish is clicked', () => {
			const product: Partial< Product > = {
				id: 5,
				name: 'Name',
				type: 'simple',
				status: 'draft',
				downloadable: false,
				virtual: false,
				manage_stock: true,
			};
			const { queryByText } = render(
				<Form initialValues={ product }>
					<ProductFormActions />
				</Form>
			);
			const publishButton = queryByText( 'Publish' );
			expect( ( publishButton as HTMLButtonElement ).disabled ).toEqual(
				false
			);
			publishButton?.click();
			expect( recordEvent ).toHaveBeenCalledWith( 'product_update', {
				new_product_page: true,
				product_id: 5,
				product_type: 'simple',
				is_downloadable: false,
				is_virtual: false,
				manage_stock: true,
			} );
			expect( updateProductWithStatus ).toHaveBeenCalledWith(
				product.id,
				product,
				'publish'
			);
		} );

		it( 'should disable publish/update button when product is published and not dirty', () => {
			const product: Partial< Product > = {
				id: 5,
				name: 'Name',
				type: 'simple',
				status: 'publish',
				downloadable: false,
				virtual: false,
				manage_stock: true,
			};
			const { queryByText } = render(
				<Form initialValues={ product }>
					<ProductFormActions />
				</Form>
			);
			const publishButton = queryByText( 'Update' );
			expect( ( publishButton as HTMLButtonElement ).disabled ).toEqual(
				true
			);
		} );

		it( 'should have the Preview button enabled', () => {
			const product: Partial< Product > = {
				id: 5,
				name: 'Name',
				type: 'simple',
				status: 'publish',
				downloadable: false,
				virtual: false,
				manage_stock: true,
				permalink: 'some_permalink',
			};
			const { queryByText } = render(
				<Form initialValues={ product }>
					<ProductFormActions />
				</Form>
			);
			const previewButton = queryByText( 'Preview' );
			expect( ( previewButton as HTMLButtonElement ).disabled ).toEqual(
				undefined
			);
		} );

		it( 'should trigger the product_preview_changes track when Preview is clicked', () => {
			const product: Partial< Product > = {
				id: 5,
				name: 'Name',
				type: 'simple',
				status: 'publish',
				downloadable: false,
				virtual: false,
				manage_stock: true,
				permalink: 'some_permalink',
			};
			const { queryByText } = render(
				<Form initialValues={ product }>
					<ProductFormActions />
				</Form>
			);
			const previewButton = queryByText( 'Preview' );
			previewButton?.click();
			expect( recordEvent ).toHaveBeenCalledWith(
				'product_preview_changes',
				{
					new_product_page: true,
					product_id: 5,
					product_type: 'simple',
					is_downloadable: false,
					is_virtual: false,
					manage_stock: true,
				}
			);
		} );

		it( 'should have the Move to trash button enabled and trigger the product_delete track and deleteProductAndRedirect function', () => {
			const product: Partial< Product > = {
				id: 5,
				name: 'Name',
				type: 'simple',
				status: 'publish',
				downloadable: false,
				virtual: false,
				manage_stock: true,
				permalink: 'some_permalink',
			};
			const { queryByText, queryByLabelText } = render(
				<Form initialValues={ product }>
					<ProductFormActions />
				</Form>
			);
			queryByLabelText( 'Publish options' )?.click();
			const moveToTrashButton = queryByText( 'Move to trash' );
			expect(
				( moveToTrashButton?.parentElement as HTMLButtonElement )
					.disabled
			).toEqual( false );
			moveToTrashButton?.click();
			expect( recordEvent ).toHaveBeenCalledWith( 'product_delete', {
				new_product_page: true,
				product_id: 5,
				product_type: 'simple',
				is_downloadable: false,
				is_virtual: false,
				manage_stock: true,
			} );
			expect( deleteProductAndRedirect ).toHaveBeenCalledWith(
				product.id
			);
		} );

		it( 'should trigger updateProductWithStatus and copyProductWithStatus when Update & duplicate is clicked', async () => {
			const product: Partial< Product > = {
				id: 5,
				name: 'Name',
				type: 'simple',
				status: 'publish',
				downloadable: false,
				virtual: false,
				manage_stock: true,
				permalink: 'some_permalink',
			};
			const { queryByText, queryByLabelText } = render(
				<Form initialValues={ product }>
					<ProductFormActions />
				</Form>
			);
			queryByLabelText( 'Publish options' )?.click();
			const publishAndDuplicateButton =
				queryByText( 'Update & duplicate' );
			publishAndDuplicateButton?.click();
			expect( recordEvent ).toHaveBeenCalledWith(
				'product_publish_and_copy',
				{
					new_product_page: true,
					product_id: 5,
					product_type: 'simple',
					is_downloadable: false,
					is_virtual: false,
					manage_stock: true,
				}
			);
			updateProductWithStatus.mockReturnValue( Promise.resolve() );
			expect( updateProductWithStatus ).toHaveBeenCalledWith(
				product.id,
				product,
				'publish'
			);
			await waitFor( () =>
				expect( copyProductWithStatus ).toHaveBeenCalledWith( product )
			);
		} );

		it( 'should trigger updateProductWithStatus and copyProductWithStatus when Copy to a new draft is clicked', async () => {
			const product: Partial< Product > = {
				id: 5,
				name: 'Name',
				type: 'simple',
				status: 'publish',
				downloadable: false,
				virtual: false,
				manage_stock: true,
				permalink: 'some_permalink',
			};
			const { queryByText, queryByLabelText } = render(
				<Form initialValues={ product }>
					<ProductFormActions />
				</Form>
			);
			queryByLabelText( 'Publish options' )?.click();
			const copyToANewDraftButton = queryByText( 'Copy to a new draft' );
			copyToANewDraftButton?.click();
			expect( recordEvent ).toHaveBeenCalledWith( 'product_copy', {
				new_product_page: true,
				product_id: 5,
				product_type: 'simple',
				is_downloadable: false,
				is_virtual: false,
				manage_stock: true,
			} );
			updateProductWithStatus.mockReturnValue( Promise.resolve() );
			expect( updateProductWithStatus ).toHaveBeenCalledWith(
				product.id,
				product,
				'publish'
			);
			await waitFor( () =>
				expect( copyProductWithStatus ).toHaveBeenCalledWith( product )
			);
		} );
	} );

	describe( 'when the form is invalid', () => {
		[ 'Save draft', 'Preview', 'Publish' ].forEach( ( buttonText ) => {
			it( `should have the ${ buttonText } button disabled`, () => {
				render(
					<Form initialValues={ {} } validate={ validate }>
						<ProductFormActions />
					</Form>
				);
				const actionButton = screen.getByText( buttonText );
				expect( actionButton ).toBeDisabled();
			} );
		} );

		it( 'should have the Publish options menu button disabled', () => {
			render(
				<Form initialValues={ {} } validate={ validate }>
					<ProductFormActions />
				</Form>
			);
			expect( screen.getByLabelText( 'Publish options' ) ).toBeDisabled();
		} );

		it( 'should have the Publish options menu items disabled', () => {
			render(
				// This consider a product created and published
				<Form
					initialValues={ { id: 1, status: 'publish' } }
					validate={ validate }
				>
					<ProductFormActions />
				</Form>
			);

			const publishOptionsButton =
				screen.getByLabelText( 'Publish options' );

			userEvent.click( publishOptionsButton );

			const optionsMenu = screen.getByRole( 'menu' );
			[ 'Update & duplicate', 'Copy to a new draft' ].forEach(
				( itemText ) => {
					const menuItem = within( optionsMenu )
						.getByText( itemText )
						.closest( 'button' );
					expect( menuItem ).toBeDisabled();
				}
			);
		} );
	} );
} );

describe( 'Validations', () => {
	it( 'should not allow an empty product name', () => {
		const nameErrorMessage = 'This field is required.';
		const priceErrorMessage =
			'Please enter a price with one monetary decimal point without thousand separators and currency symbols.';
		const salePriceErrorMessage =
			'Please enter a price with one monetary decimal point without thousand separators and currency symbols.';
		const highSalePriceErrorMessage =
			'Sale price cannot be equal to or higher than list price.';
		const productWithoutName: Partial< Product > = {
			name: '',
		};
		const productPriceWithText: Partial< Product > = {
			name: 'My Product',
			regular_price: 'text',
		};
		const productPriceWithNotAllowedCharacters: Partial< Product > = {
			name: 'My Product',
			regular_price: '%&@#¢∞¬÷200',
		};
		const productPriceWithSpaces: Partial< Product > = {
			name: 'My Product',
			regular_price: '2 0 0',
		};
		const productSalePriceWithText: Partial< Product > = {
			name: 'My Product',
			regular_price: '201',
			sale_price: 'text',
		};
		const productSalePriceWithNotAllowedCharacters: Partial< Product > = {
			name: 'My Product',
			regular_price: '201',
			sale_price: '%&@#¢∞¬÷200',
		};
		const productSalePriceWithSpaces: Partial< Product > = {
			name: 'My Product',
			regular_price: '201',
			sale_price: '2 0 0',
		};
		const productSalePriceHigherThanRegular: Partial< Product > = {
			name: 'My Product',
			regular_price: '201',
			sale_price: '202',
		};
		const validProduct: Partial< Product > = {
			name: 'My Product',
			regular_price: '200',
			sale_price: '199',
		};
		expect( validate( productWithoutName ) ).toEqual( {
			name: nameErrorMessage,
		} );
		expect( validate( productPriceWithText ) ).toEqual( {
			regular_price: priceErrorMessage,
		} );
		expect( validate( productPriceWithNotAllowedCharacters ) ).toEqual( {
			regular_price: priceErrorMessage,
		} );
		expect( validate( productPriceWithSpaces ) ).toEqual( {
			regular_price: priceErrorMessage,
		} );

		expect( validate( productSalePriceWithText ) ).toEqual( {
			sale_price: salePriceErrorMessage,
		} );
		expect( validate( productSalePriceWithNotAllowedCharacters ) ).toEqual(
			{
				sale_price: salePriceErrorMessage,
			}
		);
		expect( validate( productSalePriceWithSpaces ) ).toEqual( {
			sale_price: salePriceErrorMessage,
		} );
		expect( validate( productSalePriceHigherThanRegular ) ).toEqual( {
			sale_price: highSalePriceErrorMessage,
		} );
		expect( validate( validProduct ) ).toEqual( {} );
	} );
} );
