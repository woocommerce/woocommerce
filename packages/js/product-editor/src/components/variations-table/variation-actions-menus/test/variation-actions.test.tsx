/**
 * External dependencies
 */
import { render, fireEvent } from '@testing-library/react';
import { ProductVariation } from '@woocommerce/data';
import { recordEvent } from '@woocommerce/tracks';
import React, { createElement } from 'react';

/**
 * Internal dependencies
 */
import { SingleUpdateMenu, MultipleUpdateMenu } from '..';
import { TRACKS_SOURCE } from '../../../../constants';
import { PRODUCT_STOCK_STATUS_KEYS } from '../../../../utils/get-product-stock-status';

jest.mock( '@woocommerce/tracks', () => ( {
	recordEvent: jest.fn(),
} ) );

jest.mock( '@wordpress/media-utils', () => ( {
	MediaUpload: ( {
		onSelect,
		render: mockRender,
	}: {
		onSelect: ( { id, url }: { id: number; url: string } ) => void;
		render: ( { open }: { open: () => void } ) => JSX.Element | null;
	} ) => {
		const mockOpenMediaUploadModal = () => {
			const uploadedImageMock = {
				id: 1,
				url: 'https://example.com/image.jpg',
			};
			onSelect( uploadedImageMock );
		};
		return mockRender( { open: mockOpenMediaUploadModal } );
	},
} ) );

const mockVariation = {
	id: 10,
	manage_stock: false,
	attributes: [],
	downloads: [],
	name: '',
	parent_id: 1,
} as unknown as ProductVariation;

const anotherMockVariation = {
	id: 11,
	manage_stock: false,
	attributes: [],
	downloads: [],
	name: '',
	parent_id: 1,
} as unknown as ProductVariation;

describe( 'MultipleUpdateMenu', () => {
	let onChangeMock: jest.Mock, onDeleteMock: jest.Mock;
	beforeEach( () => {
		onChangeMock = jest.fn();
		onDeleteMock = jest.fn();
		( recordEvent as jest.Mock ).mockClear();
	} );

	it( 'should render dropdown with pricing, inventory, and delete options when opened', () => {
		const { queryByText, getByRole } = render(
			<MultipleUpdateMenu
				selection={ [ mockVariation, anotherMockVariation ] }
				onChange={ onChangeMock }
				onDelete={ onDeleteMock }
			/>
		);
		fireEvent.click( getByRole( 'button', { name: 'Quick update' } ) );
		expect( queryByText( 'Update stock' ) ).toBeInTheDocument();
		expect( queryByText( 'Set list price' ) ).toBeInTheDocument();
		expect( queryByText( 'Toggle visibility' ) ).toBeInTheDocument();
	} );

	it( 'should call onDelete when Delete menuItem is clicked', async () => {
		const { getByRole, getByText } = render(
			<MultipleUpdateMenu
				selection={ [ mockVariation, anotherMockVariation ] }
				onChange={ onChangeMock }
				onDelete={ onDeleteMock }
			/>
		);
		await fireEvent.click(
			getByRole( 'button', { name: 'Quick update' } )
		);
		await fireEvent.click( getByText( 'Delete' ) );
		expect( onDeleteMock ).toHaveBeenCalled();
	} );
} );

describe( 'SingleUpdateMenu', () => {
	let onChangeMock: jest.Mock, onDeleteMock: jest.Mock;
	beforeEach( () => {
		onChangeMock = jest.fn();
		onDeleteMock = jest.fn();
		( recordEvent as jest.Mock ).mockClear();
	} );

	it( 'should trigger product_variations_menu_view track when dropdown toggled', () => {
		const { getByRole } = render(
			<SingleUpdateMenu
				selection={ [ mockVariation ] }
				onChange={ onChangeMock }
				onDelete={ onDeleteMock }
			/>
		);
		fireEvent.click( getByRole( 'button', { name: 'Actions' } ) );
		expect( recordEvent ).toHaveBeenCalledWith(
			'product_variations_menu_view',
			{
				source: TRACKS_SOURCE,
				variation_id: 10,
			}
		);
	} );

	it( 'should render dropdown with pricing, inventory, and delete options when opened', () => {
		const { queryByText, getByRole } = render(
			<SingleUpdateMenu
				selection={ [ mockVariation ] }
				onChange={ onChangeMock }
				onDelete={ onDeleteMock }
			/>
		);
		fireEvent.click( getByRole( 'button', { name: 'Actions' } ) );
		expect( queryByText( 'Pricing' ) ).toBeInTheDocument();
		expect( queryByText( 'Inventory' ) ).toBeInTheDocument();
		expect( queryByText( 'Delete' ) ).toBeInTheDocument();
	} );

	it( 'should call onDelete when Delete menuItem is clicked', async () => {
		const { getByRole, getByText } = render(
			<SingleUpdateMenu
				selection={ [ mockVariation ] }
				onChange={ onChangeMock }
				onDelete={ onDeleteMock }
			/>
		);
		await fireEvent.click( getByRole( 'button', { name: 'Actions' } ) );
		await fireEvent.click( getByText( 'Delete' ) );
		expect( onDeleteMock ).toHaveBeenCalled();
	} );

	describe( 'Inventory sub-menu', () => {
		it( 'should open Inventory sub-menu if Inventory is clicked with click track', async () => {
			const { queryByText, getByRole, getByText } = render(
				<SingleUpdateMenu
					selection={ [ mockVariation ] }
					onChange={ onChangeMock }
					onDelete={ onDeleteMock }
				/>
			);
			await fireEvent.click( getByRole( 'button', { name: 'Actions' } ) );
			await fireEvent.click( getByText( 'Inventory' ) );
			expect( recordEvent ).toHaveBeenCalledWith(
				'product_variations_menu_inventory_click',
				{
					source: TRACKS_SOURCE,
					variation_id: [ 10 ],
				}
			);
			expect( queryByText( 'Update stock' ) ).toBeInTheDocument();
			expect(
				queryByText( 'Toggle "track quantity"' )
			).toBeInTheDocument();
			expect(
				queryByText( 'Set status to In stock' )
			).toBeInTheDocument();
		} );

		it( 'should onChange with stock_quantity when Update stock is clicked', async () => {
			window.prompt = jest.fn().mockReturnValue( '10' );
			const { getByRole, getByText } = render(
				<SingleUpdateMenu
					selection={ [ mockVariation ] }
					onChange={ onChangeMock }
					onDelete={ onDeleteMock }
				/>
			);
			await fireEvent.click( getByRole( 'button', { name: 'Actions' } ) );
			await fireEvent.click( getByText( 'Inventory' ) );
			await fireEvent.click( getByText( 'Update stock' ) );

			expect( recordEvent ).toHaveBeenCalledWith(
				'product_variations_menu_inventory_select',
				{
					source: TRACKS_SOURCE,
					action: 'stock_quantity_set',
					variation_id: [ 10 ],
				}
			);
			expect( onChangeMock ).toHaveBeenCalledWith( [
				{
					id: 10,
					stock_quantity: 10,
					manage_stock: true,
				},
			] );
			expect( recordEvent ).toHaveBeenCalledWith(
				'product_variations_menu_inventory_update',
				{
					source: TRACKS_SOURCE,
					action: 'stock_quantity_set',
					variation_id: [ 10 ],
				}
			);
		} );

		it( 'should not call onChange when prompt is cancelled', async () => {
			window.prompt = jest.fn().mockReturnValue( null );
			const { getByRole, getByText } = render(
				<SingleUpdateMenu
					selection={ [ mockVariation ] }
					onChange={ onChangeMock }
					onDelete={ onDeleteMock }
				/>
			);
			await fireEvent.click( getByRole( 'button', { name: 'Actions' } ) );
			await fireEvent.click( getByText( 'Inventory' ) );
			await fireEvent.click( getByText( 'Update stock' ) );

			expect( recordEvent ).toHaveBeenCalledWith(
				'product_variations_menu_inventory_select',
				{
					source: TRACKS_SOURCE,
					action: 'stock_quantity_set',
					variation_id: [ 10 ],
				}
			);
			expect( onChangeMock ).not.toHaveBeenCalledWith( {
				stock_quantity: 10,
				manage_stock: true,
			} );
			expect( recordEvent ).not.toHaveBeenCalledWith(
				'product_variations_menu_inventory_update',
				{
					source: TRACKS_SOURCE,
					action: 'stock_quantity_set',
					variation_id: [ 10 ],
				}
			);
		} );

		it( 'should call onChange with toggled manage_stock when toggle "track quantity" is clicked', async () => {
			const { getByRole, getByText, rerender } = render(
				<SingleUpdateMenu
					selection={ [ mockVariation ] }
					onChange={ onChangeMock }
					onDelete={ onDeleteMock }
				/>
			);
			await fireEvent.click( getByRole( 'button', { name: 'Actions' } ) );
			await fireEvent.click( getByText( 'Inventory' ) );
			await fireEvent.click( getByText( 'Toggle "track quantity"' ) );

			expect( recordEvent ).toHaveBeenCalledWith(
				'product_variations_menu_inventory_select',
				{
					source: TRACKS_SOURCE,
					action: 'manage_stock_toggle',
					variation_id: [ 10 ],
				}
			);
			expect( onChangeMock ).toHaveBeenCalledWith( [
				{
					id: 10,
					manage_stock: true,
				},
			] );
			onChangeMock.mockClear();
			rerender(
				<SingleUpdateMenu
					selection={ [ { ...mockVariation, manage_stock: true } ] }
					onChange={ onChangeMock }
					onDelete={ onDeleteMock }
				/>
			);
			await fireEvent.click( getByRole( 'button', { name: 'Actions' } ) );
			await fireEvent.click( getByText( 'Inventory' ) );
			await fireEvent.click( getByText( 'Toggle "track quantity"' ) );
			expect( onChangeMock ).toHaveBeenCalledWith( [
				{
					id: 10,
					manage_stock: false,
				},
			] );
		} );

		it( 'should call onChange with toggled stock_status when toggle "Set status to In stock" is clicked', async () => {
			const { getByRole, getByText } = render(
				<SingleUpdateMenu
					selection={ [ mockVariation ] }
					onChange={ onChangeMock }
					onDelete={ onDeleteMock }
				/>
			);
			await fireEvent.click( getByRole( 'button', { name: 'Actions' } ) );
			await fireEvent.click( getByText( 'Inventory' ) );
			await fireEvent.click( getByText( 'Set status to In stock' ) );

			expect( recordEvent ).toHaveBeenCalledWith(
				'product_variations_menu_inventory_select',
				{
					source: TRACKS_SOURCE,
					action: 'set_status_in_stock',
					variation_id: [ 10 ],
				}
			);
			expect( onChangeMock ).toHaveBeenCalledWith( [
				{
					id: 10,
					stock_status: PRODUCT_STOCK_STATUS_KEYS.instock,
					manage_stock: false,
				},
			] );
		} );

		it( 'should call onChange with toggled stock_status when toggle "Set status to Out of stock" is clicked', async () => {
			const { getByRole, getByText } = render(
				<SingleUpdateMenu
					selection={ [ mockVariation ] }
					onChange={ onChangeMock }
					onDelete={ onDeleteMock }
				/>
			);
			await fireEvent.click( getByRole( 'button', { name: 'Actions' } ) );
			await fireEvent.click( getByText( 'Inventory' ) );
			await fireEvent.click( getByText( 'Set status to Out of stock' ) );

			expect( recordEvent ).toHaveBeenCalledWith(
				'product_variations_menu_inventory_select',
				{
					source: TRACKS_SOURCE,
					action: 'set_status_out_of_stock',
					variation_id: [ 10 ],
				}
			);
			expect( onChangeMock ).toHaveBeenCalledWith( [
				{
					id: 10,
					stock_status: PRODUCT_STOCK_STATUS_KEYS.outofstock,
					manage_stock: false,
				},
			] );
		} );

		it( 'should call onChange with toggled stock_status when toggle "Set status to On back order" is clicked', async () => {
			const { getByRole, getByText } = render(
				<SingleUpdateMenu
					selection={ [ mockVariation ] }
					onChange={ onChangeMock }
					onDelete={ onDeleteMock }
				/>
			);
			await fireEvent.click( getByRole( 'button', { name: 'Actions' } ) );
			await fireEvent.click( getByText( 'Inventory' ) );
			await fireEvent.click( getByText( 'Set status to On back order' ) );

			expect( recordEvent ).toHaveBeenCalledWith(
				'product_variations_menu_inventory_select',
				{
					source: TRACKS_SOURCE,
					action: 'set_status_on_back_order',
					variation_id: [ 10 ],
				}
			);
			expect( onChangeMock ).toHaveBeenCalledWith( [
				{
					id: 10,
					stock_status: PRODUCT_STOCK_STATUS_KEYS.onbackorder,
					manage_stock: false,
				},
			] );
		} );

		it( 'should call onChange with low_stock_amount when Edit low stock threshold is clicked', async () => {
			window.prompt = jest.fn().mockReturnValue( '7' );
			const { getByRole, getByText } = render(
				<SingleUpdateMenu
					selection={ [ mockVariation ] }
					onChange={ onChangeMock }
					onDelete={ onDeleteMock }
				/>
			);
			await fireEvent.click( getByRole( 'button', { name: 'Actions' } ) );
			await fireEvent.click( getByText( 'Inventory' ) );
			await fireEvent.click( getByText( 'Edit low stock threshold' ) );

			expect( recordEvent ).toHaveBeenCalledWith(
				'product_variations_menu_inventory_select',
				{
					source: TRACKS_SOURCE,
					action: 'low_stock_amount_set',
					variation_id: [ 10 ],
				}
			);
			expect( onChangeMock ).toHaveBeenCalledWith( [
				{
					id: 10,
					low_stock_amount: 7,
					manage_stock: true,
				},
			] );
		} );
	} );
} );
