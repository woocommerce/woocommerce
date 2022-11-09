/**
 * External dependencies
 */
import { render } from '@testing-library/react';
import { useSelect } from '@wordpress/data';
import { useState } from '@wordpress/element';
import { ProductAttribute, QueryProductAttribute } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import { AttributeInputField } from '../attribute-input-field';

jest.mock( '@wordpress/data', () => ( {
	...jest.requireActual( '@wordpress/data' ),
	useSelect: jest.fn(),
} ) );

jest.mock( '@wordpress/components', () => ( {
	__esModule: true,
	Spinner: () => <div>spinner</div>,
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
		}: {
			children: JSX.Element;
		} ) => <div>{ children }</div>,
		__experimentalSelectControl: ( {
			children,
			items,
			getFilteredItems,
			onSelect,
			onRemove,
		}: {
			children: ( options: {
				isOpen: boolean;
				items: QueryProductAttribute[];
				getMenuProps: () => Record< string, string >;
				getItemProps: () => Record< string, string >;
			} ) => JSX.Element;
			items: QueryProductAttribute[];
			onSelect: ( item: QueryProductAttribute ) => void;
			onRemove: ( item: QueryProductAttribute ) => void;
			getFilteredItems: (
				allItems: QueryProductAttribute[],
				inputValue: string,
				selectedItems: QueryProductAttribute[]
			) => QueryProductAttribute[];
		} ) => {
			const [ input, setInput ] = useState( '' );
			return (
				<div>
					attribute_input_field
					<button onClick={ () => setInput( 'Co' ) }>
						Update Input
					</button>
					<button onClick={ () => onSelect( items[ 0 ] ) }>
						select attribute
					</button>
					<button onClick={ () => onRemove( items[ 0 ] ) }>
						remove attribute
					</button>
					<div>
						{ children( {
							isOpen: true,
							items: getFilteredItems( items, input, [] ),
							getMenuProps: () => ( {} ),
							getItemProps: () => ( {} ),
						} ) }
					</div>
				</div>
			);
		},
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
	beforeEach( () => {
		jest.clearAllMocks();
	} );

	it( 'should show spinner while attributes are loading', () => {
		( useSelect as jest.Mock ).mockReturnValue( {
			isLoading: true,
			attributes: undefined,
		} );
		const { queryByText } = render(
			<AttributeInputField onChange={ jest.fn() } />
		);
		expect( queryByText( 'spinner' ) ).toBeInTheDocument();
	} );

	it( 'should render attributes when finished loading', () => {
		( useSelect as jest.Mock ).mockReturnValue( {
			isLoading: false,
			attributes: attributeList,
		} );
		const { queryByText } = render(
			<AttributeInputField onChange={ jest.fn() } />
		);
		expect( queryByText( 'spinner' ) ).not.toBeInTheDocument();
		expect( queryByText( attributeList[ 0 ].name ) ).toBeInTheDocument();
		expect( queryByText( attributeList[ 1 ].name ) ).toBeInTheDocument();
	} );

	it( 'should filter out attribute ids passed into ignoredAttributeIds', () => {
		( useSelect as jest.Mock ).mockReturnValue( {
			isLoading: false,
			attributes: attributeList,
		} );
		const { queryByText } = render(
			<AttributeInputField
				onChange={ jest.fn() }
				ignoredAttributeIds={ [ attributeList[ 0 ].id ] }
			/>
		);
		expect( queryByText( 'spinner' ) ).not.toBeInTheDocument();
		expect(
			queryByText( attributeList[ 0 ].name )
		).not.toBeInTheDocument();
		expect( queryByText( attributeList[ 1 ].name ) ).toBeInTheDocument();
	} );

	it( 'should filter attributes by name case insensitive', () => {
		( useSelect as jest.Mock ).mockReturnValue( {
			isLoading: false,
			attributes: attributeList,
		} );
		const { queryByText } = render(
			<AttributeInputField onChange={ jest.fn() } />
		);
		queryByText( 'Update Input' )?.click();
		expect(
			queryByText( attributeList[ 0 ].name )
		).not.toBeInTheDocument();
		expect( queryByText( attributeList[ 1 ].name ) ).toBeInTheDocument();
	} );

	it( 'should filter out attributes ids from ignoredAttributeIds', () => {
		( useSelect as jest.Mock ).mockReturnValue( {
			isLoading: false,
			attributes: attributeList,
		} );
		const { queryByText } = render(
			<AttributeInputField
				onChange={ jest.fn() }
				ignoredAttributeIds={ [ attributeList[ 1 ].id ] }
			/>
		);
		expect( queryByText( attributeList[ 0 ].name ) ).toBeInTheDocument();
		expect(
			queryByText( attributeList[ 1 ].name )
		).not.toBeInTheDocument();
	} );

	it( 'should trigger onChange when onSelect is triggered with attribute value', () => {
		const onChangeMock = jest.fn();
		( useSelect as jest.Mock ).mockReturnValue( {
			isLoading: false,
			attributes: attributeList,
		} );
		const { queryByText } = render(
			<AttributeInputField onChange={ onChangeMock } />
		);
		queryByText( 'select attribute' )?.click();
		expect( onChangeMock ).toHaveBeenCalledWith( {
			id: attributeList[ 0 ].id,
			name: attributeList[ 0 ].name,
			options: [],
		} );
	} );

	it( 'should trigger onChange when onRemove is triggered with undefined', () => {
		const onChangeMock = jest.fn();
		( useSelect as jest.Mock ).mockReturnValue( {
			isLoading: false,
			attributes: attributeList,
		} );
		const { queryByText } = render(
			<AttributeInputField onChange={ onChangeMock } />
		);
		queryByText( 'remove attribute' )?.click();
		expect( onChangeMock ).toHaveBeenCalledWith();
	} );
} );
