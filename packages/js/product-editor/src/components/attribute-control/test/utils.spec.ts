/**
 * External dependencies
 */
import { ProductProductAttribute } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import {
	getAttributeKey,
	reorderSortableProductAttributePositions,
} from '../utils';

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
