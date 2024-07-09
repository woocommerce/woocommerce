/**
 * External dependencies
 */
import React from 'react';
import { render, fireEvent, screen } from '@testing-library/react';
import { SlotFillProvider } from '@wordpress/components';
import { createElement } from '@wordpress/element';
import { recordEvent } from '@woocommerce/tracks';
import { select } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { Tabs } from '../';
import { TabBlockEdit as Tab } from '../../../blocks/generic/tab/edit';
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

function MockTabs( {
	selected = null,
	onChange = jest.fn(),
}: {
	selected?: string | null;
	onChange?: ( tabId: string ) => void;
} ) {
	const mockContext = {
		editedProduct: null,
		postId: 1,
		postType: 'product',
		selectedTab: selected,
	};

	return (
		<SlotFillProvider>
			<Tabs selected={ selected } onChange={ onChange } />
			<Tab
				{ ...blockProps }
				attributes={ {
					id: 'test1',
					title: 'Test button 1',
					order: 1,
					isSelected: selected === 'test1',
				} }
				context={ mockContext }
				name="test1"
			/>
			<Tab
				{ ...blockProps }
				attributes={ {
					id: 'test2',
					title: 'Test button 2',
					order: 2,
					isSelected: selected === 'test2',
				} }
				context={ mockContext }
				name="test2"
			/>
			<Tab
				{ ...blockProps }
				attributes={ {
					id: 'test3',
					title: 'Test button 3',
					order: 3,
					isSelected: selected === 'test3',
				} }
				context={ mockContext }
				name="test3"
			/>
		</SlotFillProvider>
	);
}

describe( 'Tabs', () => {
	beforeEach( () => {
		jest.clearAllMocks();
	} );

	it( 'should render tab buttons added to the slot', () => {
		render( <MockTabs /> );

		expect( screen.queryByText( 'Test button 1' ) ).toBeInTheDocument();
		expect( screen.queryByText( 'Test button 2' ) ).toBeInTheDocument();
	} );

	it( 'should call onChange with the first tab if no selection set', async () => {
		const mockOnChange = jest.fn();

		render( <MockTabs onChange={ mockOnChange } /> );

		expect( mockOnChange ).toHaveBeenCalledWith( 'test1' );
	} );

	it( 'should set the selected tab active', async () => {
		const mockOnChange = jest.fn();

		render( <MockTabs selected={ 'test2' } onChange={ mockOnChange } /> );

		expect( screen.queryByText( 'Test button 1' ) ).toHaveAttribute(
			'aria-selected',
			'false'
		);

		expect( screen.queryByText( 'Test button 2' ) ).toHaveAttribute(
			'aria-selected',
			'true'
		);

		expect( mockOnChange ).not.toHaveBeenCalled();
	} );

	it( 'should call the onChange prop when changing', async () => {
		const mockOnChange = jest.fn();

		render( <MockTabs selected={ 'test2' } onChange={ mockOnChange } /> );

		const button = screen.getByText( 'Test button 1' );
		fireEvent.click( button );

		expect( mockOnChange ).toHaveBeenCalledWith( 'test1' );
	} );

	it( 'should add a class to the newly selected tab panel', async () => {
		const { rerender } = render( <MockTabs selected={ 'test2' } /> );

		const panel1 = screen.getByRole( 'tabpanel', {
			name: 'Test button 1',
		} );
		const panel2 = screen.getByRole( 'tabpanel', {
			name: 'Test button 2',
		} );

		expect( panel1.classList ).not.toContain( 'is-selected' );
		expect( panel2.classList ).toContain( 'is-selected' );

		rerender( <MockTabs selected={ 'test1' } /> );

		expect( panel1.classList ).toContain( 'is-selected' );
		expect( panel2.classList ).not.toContain( 'is-selected' );
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
