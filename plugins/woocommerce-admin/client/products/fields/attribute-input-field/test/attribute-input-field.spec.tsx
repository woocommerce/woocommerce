/**
 * External dependencies
 */
import { act, render, screen } from '@testing-library/react';
import { resolveSelect } from '@wordpress/data';
import { useEffect, useState } from '@wordpress/element';
import { ProductAttribute, QueryProductAttribute } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import { AttributeInputField } from '../attribute-input-field';

jest.mock( '@wordpress/data', () => ( {
	...jest.requireActual( '@wordpress/data' ),
	resolveSelect: jest.fn(),
} ) );

jest.mock( '@wordpress/components', () => ( {
	__esModule: true,
	Spinner: () => <div>spinner</div>,
	Icon: () => <div>icon</div>,
} ) );

jest.mock( '@woocommerce/components', () => {
	return {
		__esModule: true,
		__experimentalSelectControlMenu: ( {
			children,
		}: {
			children: JSX.Element;
		} ) => children,
		__experimentalSelectControlMenuItem: ( {
			children,
			getItemProps,
			item,
		}: {
			children: JSX.Element;
			getItemProps: ( options: { item: QueryProductAttribute } ) => {
				onClick: () => void;
			};
			item: QueryProductAttribute;
		} ) => (
			<button onClick={ () => getItemProps( { item } ).onClick() }>
				{ children }
			</button>
		),
		__experimentalSelectControl: ( {
			children,
			items,
			onSelect,
			onRemove,
			onInputChange,
		}: {
			children: ( options: {
				isOpen: boolean;
				items: QueryProductAttribute[];
				getMenuProps: () => Record< string, string >;
				getItemProps: ( options: { item: QueryProductAttribute } ) => {
					onClick: () => void;
				};
			} ) => JSX.Element;
			items: QueryProductAttribute[];
			onSelect: ( item: QueryProductAttribute ) => void;
			onRemove: ( item: QueryProductAttribute ) => void;
			onInputChange?: ( inputValue?: string ) => void;
		} ) => {
			const [ input, setInput ] = useState( '' );

			useEffect( () => {
				if ( onInputChange ) onInputChange( input );
			}, [ input ] );

			return (
				<div>
					attribute_input_field
					<button onClick={ () => setInput( 'Co' ) }>
						Update Input
					</button>
					<button onClick={ () => onRemove( items[ 0 ] ) }>
						remove attribute
					</button>
					<div>
						{ children( {
							isOpen: true,
							items,
							getMenuProps: () => ( {} ),
							getItemProps: ( {
								item,
							}: {
								item: QueryProductAttribute;
							} ) => ( {
								onClick: () => onSelect( item ),
							} ),
						} ) }
					</div>
				</div>
			);
		},
		useAsyncFilter: jest.fn(
			( { filter, onFilterStart, onFilterEnd, ...props } ) => {
				const onInputChange = ( value = '' ) => {
					onFilterStart( value );
					filter( value )
						.then( ( items: [] ) => {
							onFilterEnd( items, value );
						} )
						.catch( () => {
							onFilterEnd( [], value );
						} );
					if ( props.onInputChange ) props.onInputChange( value );
				};
				return { ...props, onInputChange };
			}
		),
		useSyncFilter: jest.fn(),
	};
} );

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

describe( 'AttributeInputField', () => {
	const getProductAttributes = jest.fn();

	beforeEach( () => {
		( resolveSelect as jest.Mock ).mockReturnValue( {
			getProductAttributes,
		} );
	} );

	afterEach( () => {
		jest.clearAllMocks();
	} );

	it( 'should show spinner while attributes are loading', async () => {
		getProductAttributes.mockImplementation(
			() => new Promise( ( resolve ) => setTimeout( resolve, 500 ) )
		);
		await act( async () => {
			render( <AttributeInputField onChange={ jest.fn() } /> );
		} );
		expect( screen.queryByText( 'spinner' ) ).toBeInTheDocument();
	} );

	it( 'should render attributes when finished loading', async () => {
		getProductAttributes.mockResolvedValue( attributeList );
		await act( async () =>
			render( <AttributeInputField onChange={ jest.fn() } /> )
		);
		expect( screen.queryByText( 'spinner' ) ).not.toBeInTheDocument();
		expect(
			screen.queryByText( attributeList[ 0 ].name )
		).toBeInTheDocument();
		expect(
			screen.queryByText( attributeList[ 1 ].name )
		).toBeInTheDocument();
	} );

	it( 'should filter out attribute ids passed into ignoredAttributeIds', async () => {
		getProductAttributes.mockResolvedValue( attributeList );
		await act( async () => {
			render(
				<AttributeInputField
					onChange={ jest.fn() }
					ignoredAttributeIds={ [ attributeList[ 0 ].id ] }
				/>
			);
		} );
		expect( screen.queryByText( 'spinner' ) ).not.toBeInTheDocument();
		expect(
			screen.queryByText( attributeList[ 0 ].name )
		).not.toBeInTheDocument();
		expect(
			screen.queryByText( attributeList[ 1 ].name )
		).toBeInTheDocument();
	} );

	it( 'should filter attributes by name case insensitive', async () => {
		getProductAttributes.mockResolvedValue( attributeList );
		await act( async () =>
			render( <AttributeInputField onChange={ jest.fn() } /> )
		);
		await act( async () => {
			screen.getByText( 'Update Input' ).click();
		} );
		expect(
			screen.queryByText( attributeList[ 0 ].name )
		).not.toBeInTheDocument();
		expect(
			screen.queryByText( attributeList[ 1 ].name )
		).toBeInTheDocument();
	} );

	it( 'should filter out attributes ids from ignoredAttributeIds', async () => {
		getProductAttributes.mockResolvedValue( attributeList );
		await act( async () => {
			render(
				<AttributeInputField
					onChange={ jest.fn() }
					ignoredAttributeIds={ [ attributeList[ 1 ].id ] }
				/>
			);
		} );
		expect(
			screen.queryByText( attributeList[ 0 ].name )
		).toBeInTheDocument();
		expect(
			screen.queryByText( attributeList[ 1 ].name )
		).not.toBeInTheDocument();
	} );

	it( 'should trigger onChange when onSelect is triggered with attribute value', async () => {
		const onChangeMock = jest.fn();
		getProductAttributes.mockResolvedValue( attributeList );
		await act( async () =>
			render( <AttributeInputField onChange={ onChangeMock } /> )
		);
		screen.queryByText( attributeList[ 0 ].name )?.click();
		expect( onChangeMock ).toHaveBeenCalledWith( {
			id: attributeList[ 0 ].id,
			name: attributeList[ 0 ].name,
			options: [],
		} );
	} );

	it( 'should trigger onChange when onRemove is triggered with undefined', async () => {
		const onChangeMock = jest.fn();
		getProductAttributes.mockResolvedValue( attributeList );
		await act( async () =>
			render( <AttributeInputField onChange={ onChangeMock } /> )
		);
		screen.queryByText( 'remove attribute' )?.click();
		expect( onChangeMock ).toHaveBeenCalledWith();
	} );

	it( 'should show the create option when the search value does not match any attributes', async () => {
		getProductAttributes.mockResolvedValue( [ attributeList[ 0 ] ] );
		await act( async () =>
			render( <AttributeInputField onChange={ jest.fn() } /> )
		);
		await act( async () => {
			screen.getByText( 'Update Input' ).click();
		} );
		expect( screen.queryByText( 'Create "Co"' ) ).toBeInTheDocument();
	} );

	it( 'trigger the onChange callback when the create new value is clicked with only a string', async () => {
		const onChangeMock = jest.fn();
		getProductAttributes.mockResolvedValue( [ attributeList[ 0 ] ] );
		await act( async () =>
			render( <AttributeInputField onChange={ onChangeMock } /> )
		);
		await act( async () => {
			screen.getByText( 'Update Input' ).click();
		} );
		await act( async () => {
			screen.getByText( 'Create "Co"' ).click();
		} );
		expect( onChangeMock ).toHaveBeenCalledWith( 'Co' );
	} );
} );
