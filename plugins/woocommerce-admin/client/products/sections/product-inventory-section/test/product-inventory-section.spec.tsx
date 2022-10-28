/**
 * External dependencies
 */
import { Form } from '@woocommerce/components';
import { Product } from '@woocommerce/data';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import { getAdminSetting } from '~/utils/admin-settings';
import { ProductInventorySection } from '../';

jest.mock( '@woocommerce/tracks', () => ( { recordEvent: jest.fn() } ) );
jest.mock( '~/utils/admin-settings', () => ( {
	getAdminSetting: jest.fn(),
} ) );

describe( 'ProductInventorySection', () => {
	beforeEach( () => {
		jest.clearAllMocks();
		( getAdminSetting as jest.Mock ).mockImplementation(
			( key, value = false ) => {
				const values = {
					manageStock: 'yes',
					notifyLowStockAmount: 5,
				};
				if ( values.hasOwnProperty( key ) ) {
					return values[ key as keyof typeof values ];
				}
				return value;
			}
		);
	} );

	const product: Partial< Product > = {
		id: 1,
		name: 'Lorem',
		slug: 'lorem',
		manage_stock: false,
	};

	it( 'should render the sku field', () => {
		render(
			<Form initialValues={ product }>
				<ProductInventorySection />
			</Form>
		);

		expect(
			screen.getByLabelText( 'SKU (Stock Keeping Unit)' )
		).toBeInTheDocument();
	} );

	it( 'should disable the manage stock section if inventory management is turned off', () => {
		( getAdminSetting as jest.Mock ).mockImplementation( ( key, value ) => {
			const values = {
				manageStock: 'no',
			};
			if ( values.hasOwnProperty( key ) ) {
				return values[ key as keyof typeof values ];
			}
			return value;
		} );

		render(
			<Form initialValues={ product }>
				<ProductInventorySection />
			</Form>
		);

		expect(
			screen.getByText( 'Track quantity for this product' )
				.previousSibling
		).toHaveClass( 'is-disabled' );
	} );

	it( 'should not disable the manage stock section if inventory management is turned on', () => {
		render(
			<Form initialValues={ product }>
				<ProductInventorySection />
			</Form>
		);

		expect(
			screen.getByText( 'Track quantity for this product' )
				.previousSibling
		).not.toHaveClass( 'is-disabled' );
	} );

	it( 'should render the quantity field when product stock is being managed', () => {
		render(
			<Form initialValues={ { ...product, manage_stock: true } }>
				<ProductInventorySection />
			</Form>
		);

		expect(
			screen.getByLabelText( 'Current quantity' )
		).toBeInTheDocument();
	} );

	it( 'should not render the quantity field when product stock is not being managed', () => {
		render(
			<Form initialValues={ product }>
				<ProductInventorySection />
			</Form>
		);

		expect(
			screen.queryByLabelText( 'Current quantity' )
		).not.toBeInTheDocument();
	} );

	it( 'should render the default low stock amount in placeholder', () => {
		render(
			<Form initialValues={ { ...product, manage_stock: true } }>
				<ProductInventorySection />
			</Form>
		);

		expect(
			screen.getByPlaceholderText( '5 (store default)' )
		).toBeInTheDocument();
	} );

	it( 'should not render the advanced section until clicked', () => {
		render(
			<Form initialValues={ product }>
				<ProductInventorySection />
			</Form>
		);

		expect(
			screen.queryByLabelText( 'Limit purchases to 1 item per order' )
		).not.toBeInTheDocument();
	} );

	it( 'should render the advanced section after clicked', () => {
		render(
			<Form initialValues={ product }>
				<ProductInventorySection />
			</Form>
		);

		userEvent.click( screen.getByText( 'Advanced' ) );
		expect(
			screen.getByLabelText( 'Limit purchases to 1 item per order' )
		).toBeInTheDocument();
	} );

	it( 'should not allow backorder settings when not managing stock', () => {
		render(
			<Form initialValues={ product }>
				<ProductInventorySection />
			</Form>
		);

		userEvent.click( screen.getByText( 'Advanced' ) );
		expect(
			screen.queryByText( 'When out of stock' )
		).not.toBeInTheDocument();
	} );

	it( 'should allow backorder settings when managing stock', () => {
		render(
			<Form initialValues={ { ...product, manage_stock: true } }>
				<ProductInventorySection />
			</Form>
		);

		userEvent.click( screen.getByText( 'Advanced' ) );
		expect( screen.queryByText( 'When out of stock' ) ).toBeInTheDocument();
	} );
} );
