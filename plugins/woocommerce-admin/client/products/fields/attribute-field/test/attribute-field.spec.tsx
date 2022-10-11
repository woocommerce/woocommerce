/**
 * External dependencies
 */
import { render } from '@testing-library/react';
import { useState, useEffect } from '@wordpress/element';
import { ProductAttribute } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import { AttributeField } from '../attribute-field';

let triggerDrag: ( items: Array< { key: string } > ) => void;

jest.mock( '@woocommerce/components', () => ( {
	__esModule: true,
	ListItem: ( { children }: { children: JSX.Element } ) => children,
	Sortable: ( {
		onOrderChange,
		children,
	}: {
		onOrderChange: ( items: Array< { key: string } > ) => void;
		children: JSX.Element[];
	} ) => {
		const [ items, setItems ] = useState< JSX.Element[] >( [] );
		useEffect( () => {
			if ( ! children ) {
				return;
			}
			setItems( Array.isArray( children ) ? children : [ children ] );
		}, [ children ] );

		triggerDrag = ( newItems: Array< { key: string } > ) => {
			onOrderChange( newItems );
		};
		return (
			<>
				{ items.map( ( child, index ) => (
					<div key={ index }>{ child }</div>
				) ) }
			</>
		);
	},
} ) );

const attributeList: ProductAttribute[] = [
	{
		id: 15,
		name: 'Automotive',
		position: 0,
		visible: true,
		variation: false,
		options: [ 'test' ],
	},
	{
		id: 1,
		name: 'Color',
		position: 2,
		visible: true,
		variation: true,
		options: [
			'Beige',
			'black',
			'Blue',
			'brown',
			'Gray',
			'Green',
			'mint',
			'orange',
			'pink',
			'Red',
			'white',
			'Yellow',
		],
	},
];

describe( 'AttributeField', () => {
	beforeEach( () => {
		jest.clearAllMocks();
	} );

	describe( 'empty state', () => {
		it( 'should show subtitle and "Add first attribute" button', () => {
			const { queryByText } = render(
				<AttributeField value={ [] } onChange={ () => {} } />
			);
			expect( queryByText( 'No attributes yet' ) ).toBeInTheDocument();
			expect( queryByText( 'Add first attribute' ) ).toBeInTheDocument();
		} );
	} );

	it( 'should render the list of existing attributes', () => {
		const { queryByText } = render(
			<AttributeField
				value={ [ ...attributeList ] }
				onChange={ () => {} }
			/>
		);
		expect( queryByText( 'No attributes yet' ) ).not.toBeInTheDocument();
		expect( queryByText( 'Add first attribute' ) ).not.toBeInTheDocument();
		expect( queryByText( attributeList[ 0 ].name ) ).toBeInTheDocument();
		expect( queryByText( attributeList[ 1 ].name ) ).toBeInTheDocument();
	} );

	it( 'should render the first two terms of each attribute, and show "+ n more" for the rest', () => {
		const { queryByText } = render(
			<AttributeField
				value={ [ ...attributeList ] }
				onChange={ () => {} }
			/>
		);
		expect(
			queryByText( attributeList[ 0 ].options[ 0 ] )
		).toBeInTheDocument();
		expect(
			queryByText( attributeList[ 1 ].options[ 0 ] )
		).toBeInTheDocument();
		expect(
			queryByText( attributeList[ 1 ].options[ 1 ] )
		).toBeInTheDocument();
		expect(
			queryByText( attributeList[ 1 ].options[ 2 ] )
		).not.toBeInTheDocument();
		expect(
			queryByText(
				`+ ${ attributeList[ 1 ].options.length - 2 }&nbsp;more`
			)
		).not.toBeInTheDocument();
	} );

	describe( 'deleting', () => {
		it( 'should show a window confirm when trash icon is clicked', () => {
			jest.spyOn( global, 'confirm' ).mockReturnValueOnce( false );
			const { queryAllByLabelText } = render(
				<AttributeField
					value={ [ ...attributeList ] }
					onChange={ () => {} }
				/>
			);
			queryAllByLabelText( 'Remove attribute' )[ 0 ].click();
			expect( global.confirm ).toHaveBeenCalled();
		} );

		it( 'should trigger onChange with removed item when user clicks ok on alert', () => {
			jest.spyOn( global, 'confirm' ).mockReturnValueOnce( true );
			const onChange = jest.fn();
			const { queryAllByLabelText } = render(
				<AttributeField
					value={ [ ...attributeList ] }
					onChange={ onChange }
				/>
			);
			queryAllByLabelText( 'Remove attribute' )[ 0 ].click();
			expect( global.confirm ).toHaveBeenCalled();
			expect( onChange ).toHaveBeenCalledWith( [ attributeList[ 1 ] ] );
		} );

		it( 'should not trigger onChange with removed item when user cancel', () => {
			jest.spyOn( global, 'confirm' ).mockReturnValueOnce( false );
			const onChange = jest.fn();
			const { queryAllByLabelText } = render(
				<AttributeField
					value={ [ ...attributeList ] }
					onChange={ onChange }
				/>
			);
			queryAllByLabelText( 'Remove attribute' )[ 0 ].click();
			expect( global.confirm ).toHaveBeenCalled();
			expect( onChange ).not.toHaveBeenCalled();
		} );
	} );

	describe( 'dragging', () => {
		it( 'should trigger onChange with new order when onOrderChange triggered', () => {
			const onChange = jest.fn();
			const { queryAllByLabelText } = render(
				<AttributeField
					value={ [ ...attributeList ] }
					onChange={ onChange }
				/>
			);
			if ( triggerDrag ) {
				triggerDrag( [
					{ key: attributeList[ 1 ].id.toString() },
					{ key: attributeList[ 0 ].id.toString() },
				] );
			}
			queryAllByLabelText( 'Remove attribute' )[ 0 ].click();
			expect( onChange ).toHaveBeenCalledWith( [
				{ ...attributeList[ 1 ], position: 0 },
				{ ...attributeList[ 0 ], position: 1 },
			] );
		} );
	} );
} );
