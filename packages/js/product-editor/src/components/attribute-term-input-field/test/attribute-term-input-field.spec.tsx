/**
 * External dependencies
 */
import { act, render, waitFor, screen } from '@testing-library/react';
import { useState, createElement } from '@wordpress/element';
import { resolveSelect } from '@wordpress/data';
import { ProductAttributeTerm } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import { AttributeTermInputField } from '../attribute-term-input-field';

jest.mock( '@wordpress/core-data', () => ( {
	registerStore: jest.fn(),
} ) );

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
			getFilteredItems,
		}: {
			children: ( options: {
				isOpen: boolean;
				items: ProductAttributeTerm[];
				getMenuProps: () => Record< string, string >;
				getItemProps: () => Record< string, string >;
			} ) => JSX.Element;
			items: ProductAttributeTerm[];
			getFilteredItems: (
				allItems: ProductAttributeTerm[],
				inputValue: string,
				selectedItems: ProductAttributeTerm[]
			) => ProductAttributeTerm[];
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

describe( 'AttributeTermInputField', () => {
	beforeEach( () => {
		jest.clearAllMocks();
	} );

	it( 'should not trigger resolveSelect if attributeId is not defined', () => {
		render( <AttributeTermInputField onChange={ jest.fn() } /> );
		expect( resolveSelect ).not.toHaveBeenCalled();
	} );

	it( 'should not trigger resolveSelect if attributeId is defined but field disabled', () => {
		render(
			<AttributeTermInputField
				onChange={ jest.fn() }
				attributeId={ 2 }
				disabled={ true }
			/>
		);
		expect( resolveSelect ).not.toHaveBeenCalled();
	} );

	it( 'should trigger resolveSelect if attributeId is defined and field not disabled', () => {
		const getProductAttributesMock = jest.fn().mockResolvedValue( [] );
		( resolveSelect as jest.Mock ).mockReturnValue( {
			getProductAttributeTerms: getProductAttributesMock,
		} );
		render(
			<AttributeTermInputField onChange={ jest.fn() } attributeId={ 2 } />
		);
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
