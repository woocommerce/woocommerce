/**
 * External dependencies
 */
import React from 'react';
import { render, fireEvent, screen } from '@testing-library/react';
import { getQuery, navigateTo } from '@woocommerce/navigation';
import { SlotFillProvider } from '@wordpress/components';
import { useState, createElement } from '@wordpress/element';
import { recordEvent } from '@woocommerce/tracks';
import { select } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { Tabs } from '../';
import {
	TabBlockEdit as Tab,
	TabBlockAttributes,
} from '../../../blocks/generic/tab/edit';
import { TRACKS_SOURCE } from '../../../constants';

jest.mock( '@woocommerce/block-templates', () => ( {
	...jest.requireActual( '@woocommerce/block-templates' ),
	useWooBlockProps: jest.fn(),
} ) );

jest.mock( '@woocommerce/navigation', () => ( {
	...jest.requireActual( '@woocommerce/navigation' ),
	navigateTo: jest.fn(),
	getQuery: jest.fn().mockReturnValue( {} ),
} ) );

jest.mock( '@woocommerce/tracks', () => ( {
	...jest.requireActual( '@woocommerce/tracks' ),
	recordEvent: jest.fn(),
} ) );

jest.mock( '@wordpress/data', () => {
	const originalModule = jest.requireActual( '@wordpress/data' );
	return {
		...originalModule,
		select: jest.fn( ( ...args ) => originalModule.select( ...args ) ),
	};
} );

const blockProps = {
	setAttributes: () => {},
	className: '',
	clientId: '',
	isSelected: false,
};

function MockTabs( { onChange = jest.fn() } ) {
	const [ selected, setSelected ] = useState< string | null >( null );
	const mockContext = {
		editedProduct: null,
		postId: 1,
		postType: 'product',
		selectedTab: selected,
	};

	function getAttributes( id: string ) {
		return function setAttributes( {
			isSelected,
		}: Partial< TabBlockAttributes > ) {
			if ( isSelected ) {
				setSelected( id );
			}
		};
	}

	return (
		<SlotFillProvider>
			<Tabs
				onChange={ ( tabId ) => {
					setSelected( tabId );
					onChange( tabId );
				} }
			/>
			<Tab
				{ ...blockProps }
				attributes={ {
					id: 'test1',
					title: 'Test button 1',
					order: 1,
					isSelected: selected === 'test1',
				} }
				// eslint-disable-next-line @typescript-eslint/ban-ts-comment
				// @ts-ignore editedProduct is not used, so we can just ignore the fact that our context doesn't have it
				context={ mockContext }
				name="test1"
				setAttributes={ getAttributes( 'test1' ) }
			/>
			<Tab
				{ ...blockProps }
				attributes={ {
					id: 'test2',
					title: 'Test button 2',
					order: 2,
					isSelected: selected === 'test2',
				} }
				// eslint-disable-next-line @typescript-eslint/ban-ts-comment
				// @ts-ignore editedProduct is not used, so we can just ignore the fact that our context doesn't have it
				context={ mockContext }
				name="test2"
				setAttributes={ getAttributes( 'test2' ) }
			/>
			<Tab
				{ ...blockProps }
				attributes={ {
					id: 'test3',
					title: 'Test button 3',
					order: 3,
					isSelected: selected === 'test3',
				} }
				// eslint-disable-next-line @typescript-eslint/ban-ts-comment
				// @ts-ignore editedProduct is not used, so we can just ignore the fact that our context doesn't have it
				context={ mockContext }
				name="test3"
				setAttributes={ getAttributes( 'test3' ) }
			/>
		</SlotFillProvider>
	);
}

describe( 'Tabs', () => {
	beforeEach( () => {
		jest.clearAllMocks();
		( getQuery as jest.Mock ).mockReturnValue( {
			tab: null,
		} );
	} );

	it( 'should render tab buttons added to the slot', () => {
		render( <MockTabs /> );

		expect( screen.queryByText( 'Test button 1' ) ).toBeInTheDocument();
		expect( screen.queryByText( 'Test button 2' ) ).toBeInTheDocument();
	} );

	it( 'should set the first tab as active initially', async () => {
		render( <MockTabs /> );

		expect( screen.queryByText( 'Test button 1' ) ).toHaveAttribute(
			'aria-selected',
			'true'
		);

		expect( screen.queryByText( 'Test button 2' ) ).toHaveAttribute(
			'aria-selected',
			'false'
		);
	} );

	it( 'should navigate to a new URL when a tab is clicked', () => {
		render( <MockTabs /> );

		const button = screen.getByText( 'Test button 2' );
		fireEvent.click( button );

		expect( navigateTo ).toHaveBeenLastCalledWith( {
			url: 'admin.php?page=wc-admin&tab=test2',
		} );
	} );

	it( 'should select the tab provided in the URL initially', () => {
		( getQuery as jest.Mock ).mockReturnValue( {
			tab: 'test2',
		} );

		render( <MockTabs /> );

		expect( screen.getByText( 'Test button 2' ) ).toHaveAttribute(
			'aria-selected',
			'true'
		);
	} );

	it( 'should select the tab provided on URL change', () => {
		const { rerender } = render( <MockTabs /> );

		( getQuery as jest.Mock ).mockReturnValue( {
			tab: 'test3',
		} );

		rerender( <MockTabs /> );

		expect( screen.getByText( 'Test button 3' ) ).toHaveAttribute(
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
		render( <MockTabs /> );

		const panel1 = screen.getByRole( 'tabpanel', {
			name: 'Test button 1',
		} );
		const panel2 = screen.getByRole( 'tabpanel', {
			name: 'Test button 2',
		} );

		expect( panel1.classList ).toContain( 'is-selected' );
		expect( panel2.classList ).not.toContain( 'is-selected' );
	} );

	it( 'should add a class to the newly selected tab panel', async () => {
		const { rerender } = render( <MockTabs /> );

		const button = screen.getByText( 'Test button 2' );
		fireEvent.click( button );
		const panel1 = screen.getByRole( 'tabpanel', {
			name: 'Test button 1',
		} );
		const panel2 = screen.getByRole( 'tabpanel', {
			name: 'Test button 2',
		} );

		( getQuery as jest.Mock ).mockReturnValue( {
			tab: 'test2',
		} );

		rerender( <MockTabs /> );

		expect( panel1.classList ).not.toContain( 'is-selected' );
		expect( panel2.classList ).toContain( 'is-selected' );
	} );

	it( 'should trigger wcadmin_product_tab_click track event when tab is clicked', async () => {
		( select as jest.Mock ).mockImplementation( () => ( {
			getEditedEntityRecord: () => ( {
				type: 'simple',
			} ),
		} ) );
		render( <MockTabs /> );

		const button = screen.getByText( 'Test button 2' );
		fireEvent.click( button );
		expect( recordEvent ).toBeCalledWith( 'product_tab_click', {
			product_tab: 'test2',
			product_type: 'simple',
			source: TRACKS_SOURCE,
		} );
	} );
} );
