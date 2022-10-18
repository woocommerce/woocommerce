/**
 * External dependencies
 */
import { render } from '@testing-library/react';
import { SlotFillProvider } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { ProductFieldLayout } from '../product-field-layout';
import { WooProductFieldItem } from '../woo-product-field-item';

describe( 'ProductFieldLayout', () => {
	beforeEach( () => {
		jest.clearAllMocks();
	} );

	it( 'should allow adding extra fields before the field using slot fill', () => {
		const { queryByText } = render(
			<SlotFillProvider>
				<ProductFieldLayout
					fieldName="Name"
					categoryName="Product Details"
				>
					<div>Name field</div>
				</ProductFieldLayout>
				<div>
					<WooProductFieldItem
						fieldName="Name"
						categoryName="Product Details"
						location="before"
					>
						<div>New field</div>
					</WooProductFieldItem>
				</div>
			</SlotFillProvider>
		);
		expect( queryByText( 'New field' ) ).toBeInTheDocument();
		expect( queryByText( 'New field' )?.nextSibling?.textContent ).toEqual(
			'Name field'
		);
	} );

	it( 'should allow adding extra fields after the field using slot fill', () => {
		const { queryByText } = render(
			<SlotFillProvider>
				<ProductFieldLayout
					fieldName="Name"
					categoryName="Product Details"
				>
					<div>Name field</div>
				</ProductFieldLayout>
				<div>
					<WooProductFieldItem
						fieldName="Name"
						categoryName="Product Details"
						location="after"
					>
						<div>New field</div>
					</WooProductFieldItem>
				</div>
			</SlotFillProvider>
		);
		expect( queryByText( 'New field' ) ).toBeInTheDocument();
		expect( queryByText( 'Name field' )?.nextSibling?.textContent ).toEqual(
			'New field'
		);
	} );

	it( 'should not render new slot fills when field name does not match', () => {
		const { queryByText } = render(
			<SlotFillProvider>
				<ProductFieldLayout
					fieldName="Name"
					categoryName="Product Details"
				>
					<div>Name field</div>
				</ProductFieldLayout>
				<div>
					<WooProductFieldItem
						fieldName="Description"
						categoryName="Product Details"
						location="after"
					>
						<div>New field</div>
					</WooProductFieldItem>
				</div>
			</SlotFillProvider>
		);
		expect( queryByText( 'New field' ) ).not.toBeInTheDocument();
	} );

	it( 'should not render new slot fills when category name does not match', () => {
		const { queryByText } = render(
			<SlotFillProvider>
				<ProductFieldLayout
					fieldName="Name"
					categoryName="Product Details"
				>
					<div>Name field</div>
				</ProductFieldLayout>
				<div>
					<WooProductFieldItem
						fieldName="Name"
						categoryName="Images"
						location="after"
					>
						<div>New field</div>
					</WooProductFieldItem>
				</div>
			</SlotFillProvider>
		);
		expect( queryByText( 'New field' ) ).not.toBeInTheDocument();
	} );
} );
