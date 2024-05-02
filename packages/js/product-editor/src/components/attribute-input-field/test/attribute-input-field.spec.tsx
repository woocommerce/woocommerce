/**
 * External dependencies
 */
import { render, waitFor } from '@testing-library/react';
import { useDispatch } from '@wordpress/data';
import { useState, createElement } from '@wordpress/element';
import type {
	ProductAttribute,
	ProductProductAttribute,
	QueryProductAttribute,
} from '@woocommerce/data';

/**
 * Internal dependencies
 */
import { AttributeInputField } from '../attribute-input-field';
import type { AttributeInputFieldItemProps } from '../types';

jest.mock( '@wordpress/data', () => ( {
	...jest.requireActual( '@wordpress/data' ),
	useDispatch: jest.fn().mockReturnValue( {
		createErrorNotice: jest.fn(),
		createProductAttribute: jest.fn(),
	} ),
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
			getFilteredItems,
			onSelect,
			onRemove,
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
					<button onClick={ () => onRemove( items[ 0 ] ) }>
						remove attribute
					</button>
					<div>
						{ children( {
							isOpen: true,
							items: getFilteredItems( items, input, [] ),
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
	};
} );

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

const items: AttributeInputFieldItemProps[] = attributeList.map(
	( attribute, i ) => ( {
		id: attribute.id,
		name: attribute.name,
		slug: attribute.slug,
		isDisabled: ! Boolean( i % 2 ),
	} )
);

describe( 'AttributeInputField', () => {
	beforeEach( () => {
		jest.clearAllMocks();
	} );

	it( 'should show spinner while attributes are loading', () => {
		const { queryByText } = render(
			<AttributeInputField isLoading={ true } onChange={ jest.fn() } />
		);
		expect( queryByText( 'spinner' ) ).toBeInTheDocument();
	} );

	it( 'should render attributes when finished loading', () => {
		const { queryByText } = render(
			<AttributeInputField
				items={ items }
				isLoading={ false }
				onChange={ jest.fn() }
			/>
		);
		expect( queryByText( 'spinner' ) ).not.toBeInTheDocument();
		expect( queryByText( items[ 0 ].name ) ).toBeInTheDocument();
		expect( queryByText( items[ 1 ].name ) ).toBeInTheDocument();
	} );

	it( 'should filter attributes by name case insensitive', () => {
		const { queryByText } = render(
			<AttributeInputField
				items={ items }
				isLoading={ false }
				onChange={ jest.fn() }
			/>
		);
		queryByText( 'Update Input' )?.click();
		expect(
			queryByText( attributeList[ 0 ].name )
		).not.toBeInTheDocument();
		expect( queryByText( attributeList[ 1 ].name ) ).toBeInTheDocument();
	} );

	it( 'should trigger onChange when onSelect is triggered with attribute value', () => {
		const onChangeMock = jest.fn();

		const { queryByText } = render(
			<AttributeInputField
				items={ items }
				isLoading={ false }
				onChange={ onChangeMock }
			/>
		);

		queryByText( items[ 0 ].name )?.click();

		expect( onChangeMock ).toHaveBeenCalledWith( items[ 0 ] );
	} );

	it( 'should trigger onChange when onRemove is triggered with undefined', () => {
		const onChangeMock = jest.fn();

		const { queryByText } = render(
			<AttributeInputField onChange={ onChangeMock } />
		);
		queryByText( 'remove attribute' )?.click();
		expect( onChangeMock ).toHaveBeenCalledWith();
	} );

	it( 'should show the create option when the search value does not match any attributes', () => {
		const { queryByText } = render(
			<AttributeInputField onChange={ jest.fn() } />
		);
		queryByText( 'Update Input' )?.click();
		expect( queryByText( 'Create "Co"' ) ).toBeInTheDocument();
	} );

	it( 'trigger the onChange callback when the create new value is clicked with only a string', async () => {
		const onChangeMock = jest.fn();
		const { queryByText } = render(
			<AttributeInputField
				items={ items }
				isLoading={ false }
				onChange={ onChangeMock }
			/>
		);
		queryByText( 'Update Input' )?.click();
		queryByText( 'Create "Co"' )?.click();
		expect( onChangeMock ).toHaveBeenCalledWith( 'Co' );
	} );

	describe( 'createNewAttributesAsGlobal is true', () => {
		it( 'should create a new global attribute', async () => {
			const onChangeMock = jest.fn();
			const createProductAttributeMock = jest
				.fn()
				.mockImplementation( ( newAttribute: ProductAttribute ) => {
					return Promise.resolve( {
						name: newAttribute.name,
						id: 123,
						slug: newAttribute.name?.toLowerCase(),
					} );
				} );
			( useDispatch as jest.Mock ).mockReturnValue( {
				createErrorNotice: jest.fn(),
				createProductAttribute: createProductAttributeMock,
			} );
			const { queryByText } = render(
				<AttributeInputField
					onChange={ onChangeMock }
					createNewAttributesAsGlobal={ true }
				/>
			);
			queryByText( 'Update Input' )?.click();
			queryByText( 'Create "Co"' )?.click();
			await waitFor( () => {
				expect( createProductAttributeMock ).toHaveBeenCalledWith(
					{
						name: 'Co',
						generate_slug: true,
					},
					{
						optimisticQueryUpdate: {
							order_by: 'name',
						},
					}
				);
			} );

			expect( onChangeMock ).toHaveBeenCalledWith( {
				name: 'Co',
				slug: 'co',
				id: 123,
			} );
		} );

		it( 'should show an error notice and not call onChange when creation failed', async () => {
			const onChangeMock = jest.fn();
			const createProductAttributeMock = jest
				.fn()
				.mockImplementation( () => {
					return Promise.reject( {
						code: 'woocommerce_rest_cannot_create',
						message: 'Duplicate slug',
					} );
				} );
			const createErrorNoticeMock = jest.fn();
			( useDispatch as jest.Mock ).mockReturnValue( {
				createErrorNotice: createErrorNoticeMock,
				createProductAttribute: createProductAttributeMock,
			} );
			const { queryByText } = render(
				<AttributeInputField
					onChange={ onChangeMock }
					createNewAttributesAsGlobal={ true }
				/>
			);
			queryByText( 'Update Input' )?.click();
			queryByText( 'Create "Co"' )?.click();
			expect( createProductAttributeMock ).toHaveBeenCalledWith(
				{
					name: 'Co',
					generate_slug: true,
				},
				{
					optimisticQueryUpdate: {
						order_by: 'name',
					},
				}
			);

			await waitFor( () => {
				expect( createErrorNoticeMock ).toHaveBeenCalledWith(
					'Duplicate slug',
					{ explicitDismiss: true }
				);
			} );
			expect( onChangeMock ).not.toHaveBeenCalled();
		} );
	} );
} );
