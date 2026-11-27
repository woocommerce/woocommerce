/**
 * External dependencies
 */
import type React from '@wordpress/element';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import StockFilterEdit from '../edit';

jest.mock( '@woocommerce/block-settings', () => ( {
	...jest.requireActual( '@woocommerce/block-settings' ),
	blocksConfig: { productCount: 1 },
} ) );

jest.mock( '@wordpress/block-editor', () => ( {
	...jest.requireActual( '@wordpress/block-editor' ),
	useBlockProps: jest.fn( ( props = {} ) => props ),
	InspectorControls: jest.fn( ( { children } ) => <div>{ children }</div> ),
} ) );

jest.mock( '@wordpress/components', () => ( {
	...jest.requireActual( '@wordpress/components' ),
	ToggleControl: jest.fn( ( { label, checked, onChange } ) => (
		<label htmlFor={ String( label ) }>
			<input
				id={ String( label ) }
				type="checkbox"
				checked={ checked }
				onChange={ ( event ) => onChange( event.target.checked ) }
			/>
			{ label }
		</label>
	) ),
	__experimentalToggleGroupControl: jest.fn( () => null ),
	__experimentalToolsPanel: jest.fn( ( { children } ) => (
		<div>{ children }</div>
	) ),
	__experimentalToolsPanelItem: jest.fn( ( { children } ) => (
		<div>{ children }</div>
	) ),
} ) );

jest.mock( '../block', () => jest.fn( () => null ) );
jest.mock( '../../filter-wrapper/upgrade', () => ( {
	UpgradeNotice: jest.fn( () => null ),
} ) );

describe( 'Stock Filter Edit', () => {
	it( "sets showFilterButton from the Show 'Apply filters' button toggle", async () => {
		const setAttributes = jest.fn();
		const Edit = StockFilterEdit as unknown as React.ComponentType<
			Record< string, unknown >
		>;

		render(
			<Edit
				attributes={ {
					displayStyle: 'list',
					heading: '',
					headingLevel: 3,
					selectType: 'multiple',
					showCounts: false,
					showFilterButton: false,
				} }
				clientId="stock-filter-client-id"
				setAttributes={ setAttributes }
			/>
		);

		await userEvent.click(
			screen.getByRole( 'checkbox', {
				name: "Show 'Apply filters' button",
			} )
		);

		expect( setAttributes ).toHaveBeenCalledWith( {
			showFilterButton: true,
		} );
	} );
} );
