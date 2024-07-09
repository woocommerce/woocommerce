/**
 * External dependencies
 */
import { render, act, screen } from '@testing-library/react';
import {
	useState,
	useEffect,
	createElement,
	Fragment,
} from '@wordpress/element';
import { ProductProductAttribute } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import { AttributeControl } from '../attribute-control';

const attributeList: ProductProductAttribute[] = [
	{
		id: 15,
		name: 'Automotive',
		slug: 'Automotive',
		position: 0,
		visible: true,
		variation: false,
		options: [ 'test' ],
	},
	{
		id: 1,
		name: 'Color',
		slug: 'Color',
		position: 2,
		visible: true,
		variation: true,
		options: [
			'beige',
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

let triggerDrag: ( items: Array< { key: string } > ) => void;

jest.mock( '@wordpress/data', () => ( {
	...jest.requireActual( '@wordpress/data' ),
	resolveSelect: jest.fn().mockReturnValue( {
		getProductAttributeTerms: ( {
			attribute_id,
		}: {
			attribute_id: number;
		} ) =>
			new Promise( ( resolve ) => {
				const attr = attributeList.find(
					( item ) => item.id === attribute_id
				);
				resolve(
					attr?.options.map( ( itemName, index ) => ( {
						id: ++index,
						slug: itemName.toLowerCase(),
						name: itemName,
						description: '',
						menu_order: ++index,
						count: ++index,
					} ) )
				);
			} ),
	} ),
} ) );

jest.mock( '@woocommerce/components', () => ( {
	__esModule: true,
	__experimentalSelectControlMenuSlot: () => <div></div>,
	ListItem: ( { children }: { children: JSX.Element } ) => children,
	Tag: ( { label }: { label: string } ) => <span>{ label }</span>,
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

describe( 'AttributeControl', () => {
	beforeEach( () => {
		jest.clearAllMocks();
	} );

	it( 'should render the list of all attributes', async () => {
		act( () => {
			render(
				<AttributeControl
					value={ [ ...attributeList ] }
					onChange={ () => {} }
				/>
			);
		} );

		expect(
			await screen.queryByText( 'No attributes yet' )
		).not.toBeInTheDocument();
		expect(
			await screen.queryByText( attributeList[ 0 ].name )
		).toBeInTheDocument();
		expect(
			await screen.queryByText( attributeList[ 1 ].name )
		).toBeInTheDocument();
	} );

	it( 'should render the first two terms of each option, and show "+ n more" for the rest', async () => {
		act( () => {
			render(
				<AttributeControl
					value={ [ ...attributeList ] }
					onChange={ () => {} }
				/>
			);
		} );

		expect(
			await screen.findByText( attributeList[ 1 ].options[ 0 ] )
		).toBeInTheDocument();
		expect(
			await screen.findByText( attributeList[ 1 ].options[ 1 ] )
		).toBeInTheDocument();
		expect(
			await screen.queryByText( attributeList[ 1 ].options[ 2 ] )
		).not.toBeInTheDocument();
		expect(
			await screen.queryByText(
				`+ ${ attributeList[ 1 ].options.length - 2 }&nbsp;more`
			)
		).not.toBeInTheDocument();
	} );

	describe( 'deleting', () => {
		it( 'should show a window confirm when trash icon is clicked', async () => {
			jest.spyOn( globalThis, 'confirm' ).mockReturnValueOnce( false );
			act( () => {
				render(
					<AttributeControl
						value={ [ ...attributeList ] }
						onChange={ () => {} }
					/>
				);
			} );
			(
				await screen.findAllByLabelText( 'Remove attribute' )
			 )[ 0 ].click();
			expect( globalThis.confirm ).toHaveBeenCalled();
		} );

		it( 'should trigger onChange with removed item when user clicks ok on alert', async () => {
			jest.spyOn( globalThis, 'confirm' ).mockReturnValueOnce( true );
			const onChange = jest.fn();

			act( () => {
				render(
					<AttributeControl
						value={ [ ...attributeList ] }
						onChange={ onChange }
					/>
				);
			} );

			(
				await screen.findAllByLabelText( 'Remove attribute' )
			 )[ 0 ].click();

			expect( globalThis.confirm ).toHaveBeenCalled();
			expect( onChange ).toHaveBeenCalledWith( [ attributeList[ 1 ] ] );
		} );

		it( 'should not trigger onChange with removed item when user cancel', async () => {
			jest.spyOn( globalThis, 'confirm' ).mockReturnValueOnce( false );
			const onChange = jest.fn();
			act( () => {
				render(
					<AttributeControl
						value={ [ ...attributeList ] }
						onChange={ onChange }
					/>
				);
			} );
			(
				await screen.findAllByLabelText( 'Remove attribute' )
			 )[ 0 ].click();
			expect( globalThis.confirm ).toHaveBeenCalled();
			expect( onChange ).not.toHaveBeenCalled();
		} );
	} );

	describe( 'dragging', () => {
		it.skip( 'should trigger onChange with new order when onOrderChange triggered', async () => {
			jest.spyOn( globalThis, 'confirm' ).mockReturnValueOnce( true );
			const onChange = jest.fn();

			act( () => {
				render(
					<AttributeControl
						value={ [ ...attributeList ] }
						onChange={ onChange }
					/>
				);
			} );

			if ( triggerDrag ) {
				triggerDrag( [
					{ key: attributeList[ 1 ].id.toString() },
					{ key: attributeList[ 0 ].id.toString() },
				] );
			}

			(
				await screen.findAllByLabelText( 'Remove attribute' )
			 )[ 0 ].click();

			expect( onChange ).toHaveBeenCalledWith( [
				{ ...attributeList[ 1 ], position: 0 },
				{ ...attributeList[ 0 ], position: 1 },
			] );
		} );
	} );
} );
