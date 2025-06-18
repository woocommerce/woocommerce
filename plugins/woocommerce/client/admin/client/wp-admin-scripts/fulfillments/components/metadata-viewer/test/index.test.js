/**
 * External dependencies
 */
import React from 'react';
import { render, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import MetadataViewer from '../index';

// Mock FulfillmentCard wrapper
jest.mock( '../../user-interface/fulfillments-card/card', () => ( {
	__esModule: true,
	default: ( { header, children } ) => (
		<div data-testid="fulfillment-card">
			<div data-testid="card-header">{ header }</div>
			<div data-testid="card-body">{ children }</div>
		</div>
	),
} ) );

// Mock icon component
jest.mock( '../../../utils/icons', () => ( {
	__esModule: true,
	PostListIcon: () => <span data-testid="post-list-icon" />,
} ) );

// Mock MetaList component
jest.mock( '../../user-interface/meta-list/meta-list', () => ( {
	__esModule: true,
	default: ( { metaList } ) => (
		<ul data-testid="meta-list">
			{ metaList.map( ( item, i ) => (
				<li key={ i }>
					{ item.label }: { item.value }
				</li>
			) ) }
		</ul>
	),
} ) );

describe( 'MetadataViewer component', () => {
	it( 'renders header and icon', () => {
		render(
			<MetadataViewer
				fulfillment={ {
					meta_data: [
						{ key: 'test_key', value: 'test_value' },
						{ key: 'test_key_2', value: 'test_value_2' },
						{ key: 'test_key_3', value: 'test_value_3' },
					],
				} }
			/>
		);
		expect( screen.getByTestId( 'card-header' ) ).toHaveTextContent(
			'Fulfillment details'
		);
		expect( screen.getByTestId( 'post-list-icon' ) ).toBeInTheDocument();
	} );

	it( 'renders list of metadata items', () => {
		render(
			<MetadataViewer
				fulfillment={ {
					meta_data: [
						{ key: 'test_key', value: 'test_value' },
						{ key: 'test_key_2', value: 'test_value_2' },
						{ key: 'test_key_3', value: 'test_value_3' },
					],
				} }
			/>
		);
		const list = screen.getByTestId( 'meta-list' );
		expect( list ).toBeInTheDocument();
		expect( screen.getAllByRole( 'listitem' ) ).toHaveLength( 3 );
	} );
	it( 'renders empty state when no metadata', () => {
		render( <MetadataViewer fulfillment={ { meta_data: [] } } /> );
		expect(
			screen.getByText( /No metadata available/ )
		).toBeInTheDocument();
	} );
} );
