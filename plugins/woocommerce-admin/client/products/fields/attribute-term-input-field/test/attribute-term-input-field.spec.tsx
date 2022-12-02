/**
 * External dependencies
 */
import { act, render, waitFor, screen } from '@testing-library/react';
import { useState } from '@wordpress/element';
import { resolveSelect } from '@wordpress/data';
import { ProductAttribute, ProductAttributeTerm } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import { AttributeTermInputField } from '../attribute-term-input-field';

jest.mock( '@wordpress/data', () => ( {
	...jest.requireActual( '@wordpress/data' ),
	resolveSelect: jest.fn(),
} ) );

jest.mock( '@wordpress/components', () => {
	return {
		__esModule: true,
		Spinner: () => <div>spinner</div>,
	};
} );

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
		}: {
			children: JSX.Element;
		} ) => <div>{ children }</div>,
		__experimentalSelectControl: ( {
			children,
			items,
		}: {
			children: ( options: {
				isOpen: boolean;
				items: ProductAttributeTerm[];
				getMenuProps: () => Record< string, string >;
				getItemProps: () => Record< string, string >;
			} ) => JSX.Element;
			items: ProductAttributeTerm[];
		} ) => {
			const [ input, setInput ] = useState( '' );
			return (
				<div>
					attribute_input_field
					<button onClick={ () => setInput( 'Co' ) }>
						Update Input
					</button>
					<div>
						{ children( {
							isOpen: true,
							items,
							getMenuProps: () => ( {} ),
							getItemProps: () => ( {} ),
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

const attributeTermList: ProductAttributeTerm[] = [
	{
		id: 23,
		name: 'XXS',
		slug: 'xxs',
		description: '',
		menu_order: 1,
		count: 1,
	},
	{
		id: 22,
		name: 'XS',
		slug: 'xs',
		description: '',
		menu_order: 2,
		count: 1,
	},
	{
		id: 17,
		name: 'S',
		slug: 's',
		description: '',
		menu_order: 3,
		count: 1,
	},
	{
		id: 18,
		name: 'M',
		slug: 'm',
		description: '',
		menu_order: 4,
		count: 1,
	},
	{
		id: 19,
		name: 'L',
		slug: 'l',
		description: '',
		menu_order: 5,
		count: 1,
	},
];

describe( 'AttributeTermInputField', () => {
	beforeEach( () => {
		jest.clearAllMocks();
	} );

	it( 'should not trigger resolveSelect if attributeId is not defined', async () => {
		await act( async () => {
			render( <AttributeTermInputField onChange={ jest.fn() } /> );
		} );
		expect( resolveSelect ).not.toHaveBeenCalled();
	} );

	it( 'should not trigger resolveSelect if attributeId is defined but field disabled', async () => {
		await act( async () => {
			render(
				<AttributeTermInputField
					onChange={ jest.fn() }
					attributeId={ 2 }
					disabled={ true }
				/>
			);
		} );
		expect( resolveSelect ).not.toHaveBeenCalled();
	} );

	it( 'should trigger resolveSelect if attributeId is defined and field not disabled', async () => {
		const getProductAttributesMock = jest.fn().mockResolvedValue( [] );
		( resolveSelect as jest.Mock ).mockReturnValue( {
			getProductAttributeTerms: getProductAttributesMock,
		} );
		await act( async () => {
			render(
				<AttributeTermInputField
					onChange={ jest.fn() }
					attributeId={ 2 }
				/>
			);
		} );
		expect( getProductAttributesMock ).toHaveBeenCalledWith( {
			search: '',
			attribute_id: 2,
		} );
	} );

	it( 'should render spinner while retrieving products', async () => {
		const getProductAttributesMock = jest
			.fn()
			.mockReturnValue( { then: () => {} } );
		( resolveSelect as jest.Mock ).mockReturnValue( {
			getProductAttributeTerms: getProductAttributesMock,
		} );
		await act( async () => {
			render(
				<AttributeTermInputField
					onChange={ jest.fn() }
					attributeId={ 2 }
				/>
			);
		} );
		await waitFor( () => {
			expect( screen.queryByText( 'spinner' ) ).toBeInTheDocument();
		} );
	} );
} );
