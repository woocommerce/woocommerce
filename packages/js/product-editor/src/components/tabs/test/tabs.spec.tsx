/**
 * External dependencies
 */
import { render, fireEvent } from '@testing-library/react';
import { getQuery, navigateTo } from '@woocommerce/navigation';
import React, { createElement } from 'react';
import { SlotFillProvider } from '@wordpress/components';
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { Tabs } from '../';
import { Edit as Tab } from '../../../blocks/tab/edit';

jest.mock( '@wordpress/block-editor', () => ( {
	...jest.requireActual( '@wordpress/block-editor' ),
	useBlockProps: jest.fn(),
} ) );

jest.mock( '@woocommerce/navigation', () => ( {
	...jest.requireActual( '@woocommerce/navigation' ),
	navigateTo: jest.fn(),
	getQuery: jest.fn().mockReturnValue( {} ),
} ) );

function MockTabs( { onChange = jest.fn() } ) {
	const [ selected, setSelected ] = useState< string | null >( null );
	const mockContext = {
		selectedTab: selected,
	};

	return (
		<SlotFillProvider>
			<Tabs
				onChange={ ( tabId ) => {
					setSelected( tabId );
					onChange( tabId );
				} }
			/>
			<Tab
				attributes={ { id: 'test1', title: 'Test button 1' } }
				context={ mockContext }
			/>
			<Tab
				attributes={ { id: 'test2', title: 'Test button 2' } }
				context={ mockContext }
			/>
			<Tab
				attributes={ { id: 'test3', title: 'Test button 3' } }
				context={ mockContext }
			/>
		</SlotFillProvider>
	);
}

describe( 'Tabs', () => {
	beforeEach( () => {
		( getQuery as jest.Mock ).mockReturnValue( {
			tab: null,
		} );
	} );

	it( 'should render tab buttons added to the slot', () => {
		const { queryByText } = render( <MockTabs /> );
		expect( queryByText( 'Test button 1' ) ).toBeInTheDocument();
		expect( queryByText( 'Test button 2' ) ).toBeInTheDocument();
	} );

	it( 'should set the first tab as active initially', () => {
		const { queryByText } = render( <MockTabs /> );
		expect( queryByText( 'Test button 1' ) ).toHaveAttribute(
			'aria-selected',
			'true'
		);
		expect( queryByText( 'Test button 2' ) ).toHaveAttribute(
			'aria-selected',
			'false'
		);
	} );

	it( 'should navigate to a new URL when a tab is clicked', () => {
		const { getByText } = render( <MockTabs /> );
		const button = getByText( 'Test button 2' );
		fireEvent.click( button );

		expect( navigateTo ).toHaveBeenLastCalledWith( {
			url: 'admin.php?page=wc-admin&tab=test2',
		} );
	} );

	it( 'should select the tab provided in the URL initially', () => {
		( getQuery as jest.Mock ).mockReturnValue( {
			tab: 'test2',
		} );

		const { getByText } = render( <MockTabs /> );

		expect( getByText( 'Test button 2' ) ).toHaveAttribute(
			'aria-selected',
			'true'
		);
	} );

	it( 'should select the tab provided on URL change', () => {
		const { getByText, rerender } = render( <MockTabs /> );

		( getQuery as jest.Mock ).mockReturnValue( {
			tab: 'test3',
		} );

		rerender( <MockTabs /> );

		expect( getByText( 'Test button 3' ) ).toHaveAttribute(
			'aria-selected',
			'true'
		);
	} );

	it( 'should call the onChange props when changing', async () => {
		const mockOnChange = jest.fn();
		const { rerender } = render( <MockTabs onChange={ mockOnChange } /> );

		expect( mockOnChange ).toHaveBeenCalledWith( 'test1' );

		( getQuery as jest.Mock ).mockReturnValue( {
			tab: 'test2',
		} );

		rerender( <MockTabs onChange={ mockOnChange } /> );

		expect( mockOnChange ).toHaveBeenCalledWith( 'test2' );
	} );

	it( 'should add a class to the initially selected tab panel', async () => {
		const { getByRole } = render( <MockTabs /> );
		const panel1 = getByRole( 'tabpanel', { name: 'Test button 1' } );
		const panel2 = getByRole( 'tabpanel', { name: 'Test button 2' } );

		expect( panel1.classList ).toContain( 'is-selected' );
		expect( panel2.classList ).not.toContain( 'is-selected' );
	} );

	it( 'should add a class to the newly selected tab panel', async () => {
		const { getByText, getByRole, rerender } = render( <MockTabs /> );
		const button = getByText( 'Test button 2' );
		fireEvent.click( button );
		const panel1 = getByRole( 'tabpanel', { name: 'Test button 1' } );
		const panel2 = getByRole( 'tabpanel', { name: 'Test button 2' } );

		( getQuery as jest.Mock ).mockReturnValue( {
			tab: 'test2',
		} );

		rerender( <MockTabs /> );

		expect( panel1.classList ).not.toContain( 'is-selected' );
		expect( panel2.classList ).toContain( 'is-selected' );
	} );
} );
