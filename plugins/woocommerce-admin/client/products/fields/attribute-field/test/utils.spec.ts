/**
 * External dependencies
 */
import { ProductAttribute } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import {
	getAttributeKey,
	reorderSortableProductAttributePositions,
} from '../utils';

const attributeList: Record< number, ProductAttribute > = {
	15: {
		id: 15,
		name: 'Automotive',
		position: 0,
		visible: true,
		variation: false,
		options: [ 'test' ],
	},
	1: {
		id: 1,
		name: 'Color',
		position: 1,
		visible: true,
		variation: true,
		options: [ 'Beige', 'black', 'Blue' ],
	},
	3: {
		id: 3,
		name: 'Random',
		position: 2,
		visible: true,
		variation: true,
		options: [ 'Beige', 'black', 'Blue' ],
	},
};

describe( 'reorderSortableProductAttributePositions', () => {
	it( 'should update product attribute positions depending on JSX.Element order', () => {
		const elements = [
			{ props: { attribute: attributeList[ '3' ] } },
			{ props: { attribute: attributeList[ '15' ] } },
			{ props: { attribute: attributeList[ '1' ] } },
		] as JSX.Element[];
		const newList = reorderSortableProductAttributePositions(
			elements,
			attributeList
		);
		expect( newList[ 0 ].position ).toEqual( 0 );
		expect( newList[ 0 ].id ).toEqual( 3 );
		expect( newList[ 1 ].position ).toEqual( 1 );
		expect( newList[ 1 ].id ).toEqual( 15 );
		expect( newList[ 2 ].position ).toEqual( 2 );
		expect( newList[ 2 ].id ).toEqual( 1 );
	} );
} );

describe( 'getAttributeKey', () => {
	attributeList[ '20' ] = {
		id: 0,
		name: 'Quality',
		position: 3,
		visible: true,
		variation: true,
		options: [ 'low', 'high' ],
	};
	it( 'should return the attribute key', () => {
		expect( getAttributeKey( attributeList[ '15' ] ) ).toEqual( 15 );
		expect( getAttributeKey( attributeList[ '20' ] ) ).toEqual( 'Quality' );
	} );
} );
