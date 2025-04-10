/**
 * External dependencies
 */
import {
	ProductAttributeTerm,
	ProductProductAttribute,
} from '@woocommerce/data';

/**
 * Internal dependencies
 */
import {
	getAttributeKey,
	hasTermsOrOptions,
	isAttributeFilledOut,
	reorderSortableProductAttributePositions,
} from '../utils';
import { EnhancedProductAttribute } from '../../../hooks/use-product-attributes';

const attributeList: Record< number | string, ProductProductAttribute > = {
	15: {
		id: 15,
		name: 'Automotive',
		slug: 'Automotive',
		position: 0,
		visible: true,
		variation: false,
		options: [ 'test' ],
	},
	1: {
		id: 1,
		name: 'Color',
		slug: 'Color',
		position: 1,
		visible: true,
		variation: true,
		options: [ 'Beige', 'black', 'Blue' ],
	},
	Quality: {
		id: 0,
		name: 'Quality',
		slug: 'Quality',
		position: 2,
		visible: true,
		variation: false,
		options: [ 'low', 'high' ],
	},
	3: {
		id: 3,
		name: 'Random',
		slug: 'Random',
		position: 3,
		visible: true,
		variation: true,
		options: [ 'Beige', 'black', 'Blue' ],
	},
};

describe( 'reorderSortableProductAttributePositions', () => {
	it( 'should update product attribute positions depending on JSX.Element order', () => {
		const elements = { 1: 0, 15: 1, 3: 2, Quality: 3 };
		const newList = reorderSortableProductAttributePositions(
			elements,
			attributeList
		);
		expect( newList[ 0 ].position ).toEqual( 0 );
		expect( newList[ 0 ].id ).toEqual( 1 );
		expect( newList[ 1 ].position ).toEqual( 2 );
		expect( newList[ 1 ].id ).toEqual( 3 );
		expect( newList[ 2 ].position ).toEqual( 1 );
		expect( newList[ 2 ].id ).toEqual( 15 );
		expect( newList[ 3 ].position ).toEqual( 3 );
		expect( newList[ 3 ].id ).toEqual( 0 );
	} );
} );

describe( 'getAttributeKey', () => {
	it( 'should return the attribute key', () => {
		expect( getAttributeKey( attributeList[ '15' ] ) ).toEqual( 15 );
		expect( getAttributeKey( attributeList.Quality ) ).toEqual( 'Quality' );
	} );
} );

describe( 'hasTermsOrOptions', () => {
	it( 'should return true if the attribute has local terms (options)', () => {
		const attribute: EnhancedProductAttribute = {
			name: 'Color',
			id: 0,
			slug: 'color',
			position: 0,
			visible: true,
			variation: true,
			options: [ 'Beige', 'black', 'Blue' ],
		};

		expect( hasTermsOrOptions( attribute ) ).toBe( true );
	} );

	it( 'should return true if the attribute has global terms', () => {
		const terms: ProductAttributeTerm[] = [
			{
				id: 1,
				name: 'red',
				slug: 'red',
				description: 'red color',
				count: 1,
				menu_order: 0,
			},
			{
				id: 2,
				name: 'blue',
				slug: 'blue',
				description: 'blue color',
				count: 1,
				menu_order: 1,
			},
			{
				id: 3,
				name: 'green',
				slug: 'green',
				description: 'green color',
				count: 1,
				menu_order: 2,
			},
		];

		const attribute: EnhancedProductAttribute = {
			name: 'Color',
			id: 123,
			slug: 'color',
			position: 0,
			visible: true,
			variation: true,
			terms,
			options: [],
		};

		expect( hasTermsOrOptions( attribute ) ).toBe( true );
	} );

	it( 'should return false if the attribute has neither terms nor options', () => {
		const attribute: EnhancedProductAttribute = {
			name: 'Empty',
			id: 999,
			slug: 'empty',
			position: 0,
			visible: true,
			variation: true,
			options: [],
		};
		expect( hasTermsOrOptions( attribute ) ).toBe( false );
	} );

	it( 'should return false if the attribute is null', () => {
		const attribute = null;
		expect( hasTermsOrOptions( attribute ) ).toBe( false );
	} );
} );

describe( 'isAttributeFilledOut', () => {
	it( 'should return true if the attribute has a name and local terms (options)', () => {
		const attribute: EnhancedProductAttribute = {
			name: 'Color',
			id: 0,
			slug: 'color',
			position: 0,
			visible: true,
			variation: true,
			options: [ 'Beige', 'black', 'Blue' ],
		};

		expect( isAttributeFilledOut( attribute ) ).toBe( true );
	} );

	it( 'should return true if the attribute has a name and global terms', () => {
		const terms: ProductAttributeTerm[] = [
			{
				id: 1,
				name: 'red',
				slug: 'red',
				description: 'red color',
				count: 1,
				menu_order: 0,
			},
			{
				id: 2,
				name: 'blue',
				slug: 'blue',
				description: 'blue color',
				count: 1,
				menu_order: 1,
			},
			{
				id: 3,
				name: 'green',
				slug: 'green',
				description: 'green color',
				count: 1,
				menu_order: 2,
			},
		];

		const attribute: EnhancedProductAttribute = {
			name: 'Color',
			id: 123,
			slug: 'color',
			position: 0,
			visible: true,
			variation: true,
			terms,
			options: [],
		};

		expect( isAttributeFilledOut( attribute ) ).toBe( true );
	} );

	it( 'should return false if the attribute has a name but no terms or options', () => {
		const attribute: EnhancedProductAttribute = {
			name: 'Empty',
			id: 999,
			slug: 'empty',
			position: 0,
			visible: true,
			variation: true,
			options: [],
		};

		expect( isAttributeFilledOut( attribute ) ).toBe( false );
	} );

	it( 'should return false if the attribute is null', () => {
		const attribute = null;
		expect( isAttributeFilledOut( attribute ) ).toBe( false );
	} );

	it( 'should return false if the attribute has no name', () => {
		const attribute: EnhancedProductAttribute = {
			name: '',
			id: 0,
			slug: 'color',
			position: 0,
			visible: true,
			variation: true,
			options: [ 'Beige', 'black', 'Blue' ],
		};

		expect( isAttributeFilledOut( attribute ) ).toBe( false );
	} );
} );
