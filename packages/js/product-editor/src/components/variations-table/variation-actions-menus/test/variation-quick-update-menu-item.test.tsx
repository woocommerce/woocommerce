/**
 * External dependencies
 */
import { render, fireEvent } from '@testing-library/react';
import { ProductVariation } from '@woocommerce/data';
import { recordEvent } from '@woocommerce/tracks';
import React, { createElement } from 'react';
import { SlotFillProvider } from '@wordpress/components';

/**
 * Internal dependencies
 */
import {
	SingleUpdateMenu,
	MultipleUpdateMenu,
	VariationQuickUpdateMenuItem,
} from '..';

jest.mock( '@woocommerce/tracks', () => ( {
	recordEvent: jest.fn(),
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

describe( 'SingleUpdateMenu', () => {
	let onClickMock: jest.Mock,
		onChangeMock: jest.Mock,
		onDeleteMock: jest.Mock;
	beforeEach( () => {
		onClickMock = jest.fn();
		onChangeMock = jest.fn();
		onDeleteMock = jest.fn();
		( recordEvent as jest.Mock ).mockClear();
	} );

	it( 'should render a top level fill in the single variation actions', () => {
		const { getByRole, getByText } = render(
			<SlotFillProvider>
				<VariationQuickUpdateMenuItem
					group={ 'top-level' }
					order={ 20 }
					supportsMultipleSelection={ true }
					onClick={ onClickMock }
				>
					My top level item
				</VariationQuickUpdateMenuItem>
				<SingleUpdateMenu
					selection={ [ mockVariation ] }
					onChange={ onChangeMock }
					onDelete={ onDeleteMock }
				/>
			</SlotFillProvider>
		);
		fireEvent.click( getByRole( 'button', { name: 'Actions' } ) );
		fireEvent.click( getByText( 'My top level item' ) );
		expect( onClickMock ).toHaveBeenCalled();
		expect( onClickMock ).toHaveBeenCalledWith(
			expect.objectContaining( {
				onChange: onChangeMock,
				onClose: expect.any( Function ),
				selection: [ mockVariation ],
			} )
		);
	} );

	it( 'should render a fill in the secondary area in the single variation actions', () => {
		const { getByRole, getByText } = render(
			<SlotFillProvider>
				<VariationQuickUpdateMenuItem
					group={ 'secondary' }
					order={ 20 }
					supportsMultipleSelection={ true }
					onClick={ onClickMock }
				>
					My secondary item
				</VariationQuickUpdateMenuItem>
				<SingleUpdateMenu
					selection={ [ mockVariation ] }
					onChange={ onChangeMock }
					onDelete={ onDeleteMock }
				/>
			</SlotFillProvider>
		);
		fireEvent.click( getByRole( 'button', { name: 'Actions' } ) );
		fireEvent.click( getByText( 'My secondary item' ) );
		expect( onClickMock ).toHaveBeenCalled();
		expect( onClickMock ).toHaveBeenCalledWith(
			expect.objectContaining( {
				onChange: onChangeMock,
				onClose: expect.any( Function ),
				selection: [ mockVariation ],
			} )
		);
	} );

	it( 'should render a fill in the tertiary area in the single variation actions', () => {
		const { getByRole, getByText } = render(
			<SlotFillProvider>
				<VariationQuickUpdateMenuItem
					group={ 'tertiary' }
					order={ 20 }
					supportsMultipleSelection={ true }
					onClick={ onClickMock }
				>
					My tertiary item
				</VariationQuickUpdateMenuItem>
				<SingleUpdateMenu
					selection={ [ mockVariation ] }
					onChange={ onChangeMock }
					onDelete={ onDeleteMock }
				/>
			</SlotFillProvider>
		);
		fireEvent.click( getByRole( 'button', { name: 'Actions' } ) );
		fireEvent.click( getByText( 'My tertiary item' ) );
		expect( onClickMock ).toHaveBeenCalled();
		expect( onClickMock ).toHaveBeenCalledWith(
			expect.objectContaining( {
				onChange: onChangeMock,
				onClose: expect.any( Function ),
				selection: [ mockVariation ],
			} )
		);
	} );

	it( 'should render a fill in the pricing group in the single variation actions', () => {
		const { getByRole, getByText } = render(
			<SlotFillProvider>
				<VariationQuickUpdateMenuItem
					group={ 'shipping' }
					order={ 20 }
					supportsMultipleSelection={ true }
					onClick={ onClickMock }
				>
					My shipping item
				</VariationQuickUpdateMenuItem>
				<SingleUpdateMenu
					selection={ [ mockVariation ] }
					onChange={ onChangeMock }
					onDelete={ onDeleteMock }
				/>
			</SlotFillProvider>
		);
		fireEvent.click( getByRole( 'button', { name: 'Actions' } ) );
		fireEvent.click( getByText( 'Shipping' ) );
		fireEvent.click( getByText( 'My shipping item' ) );
		expect( onClickMock ).toHaveBeenCalled();
		expect( onClickMock ).toHaveBeenCalledWith(
			expect.objectContaining( {
				onChange: onChangeMock,
				onClose: expect.any( Function ),
				selection: [ mockVariation ],
			} )
		);
	} );
} );

describe( 'MultipleUpdateMenu', () => {
	let onClickMock: jest.Mock,
		onChangeMock: jest.Mock,
		onDeleteMock: jest.Mock;
	beforeEach( () => {
		onClickMock = jest.fn();
		onChangeMock = jest.fn();
		onDeleteMock = jest.fn();
		( recordEvent as jest.Mock ).mockClear();
	} );

	it( 'should render a top level fill in the multiple variation actions', () => {
		const { queryByText, getByRole } = render(
			<SlotFillProvider>
				<VariationQuickUpdateMenuItem
					group={ 'top-level' }
					order={ 20 }
					supportsMultipleSelection={ true }
					onClick={ onClickMock }
				>
					My top level item
				</VariationQuickUpdateMenuItem>
				<MultipleUpdateMenu
					selection={ [ mockVariation, anotherMockVariation ] }
					onChange={ onChangeMock }
					onDelete={ onDeleteMock }
				/>
			</SlotFillProvider>
		);
		fireEvent.click( getByRole( 'button', { name: 'Quick update' } ) );
		expect( queryByText( 'My top level item' ) ).toBeInTheDocument();
	} );

	it( 'should render a fill in the secondary area in the multiple variation actions', () => {
		const { queryByText, getByRole } = render(
			<SlotFillProvider>
				<VariationQuickUpdateMenuItem
					group={ 'secondary' }
					order={ 20 }
					supportsMultipleSelection={ true }
					onClick={ onClickMock }
				>
					My secondary item
				</VariationQuickUpdateMenuItem>
				<MultipleUpdateMenu
					selection={ [ mockVariation, anotherMockVariation ] }
					onChange={ onChangeMock }
					onDelete={ onDeleteMock }
				/>
			</SlotFillProvider>
		);
		fireEvent.click( getByRole( 'button', { name: 'Quick update' } ) );
		expect( queryByText( 'My secondary item' ) ).toBeInTheDocument();
	} );

	it( 'should render a fill in the tertiary area in the multiple variation actions', () => {
		const { queryByText, getByRole } = render(
			<SlotFillProvider>
				<VariationQuickUpdateMenuItem
					group={ 'tertiary' }
					order={ 20 }
					supportsMultipleSelection={ true }
					onClick={ onClickMock }
				>
					My tertiary item
				</VariationQuickUpdateMenuItem>
				<MultipleUpdateMenu
					selection={ [ mockVariation, anotherMockVariation ] }
					onChange={ onChangeMock }
					onDelete={ onDeleteMock }
				/>
			</SlotFillProvider>
		);
		fireEvent.click( getByRole( 'button', { name: 'Quick update' } ) );
		expect( queryByText( 'My tertiary item' ) ).toBeInTheDocument();
	} );

	it( 'should render a fill in the pricing group in the multiple variation actions', async () => {
		const { queryByText, getByRole, getByText } = render(
			<SlotFillProvider>
				<VariationQuickUpdateMenuItem
					group={ 'pricing' }
					order={ 20 }
					supportsMultipleSelection={ true }
					onClick={ onClickMock }
				>
					My pricing item
				</VariationQuickUpdateMenuItem>
				<MultipleUpdateMenu
					selection={ [ mockVariation, anotherMockVariation ] }
					onChange={ onChangeMock }
					onDelete={ onDeleteMock }
				/>
			</SlotFillProvider>
		);
		await fireEvent.click(
			getByRole( 'button', { name: 'Quick update' } )
		);
		await fireEvent.click( getByText( 'Pricing' ) );
		expect( queryByText( 'My pricing item' ) ).toBeInTheDocument();
	} );
} );
