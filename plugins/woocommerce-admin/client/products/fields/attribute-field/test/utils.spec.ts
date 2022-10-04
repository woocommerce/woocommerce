/**
 * External dependencies
 */
import { ProductAttribute } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import { reorderSortableProductAttributePositions } from '../utils';

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
			{ key: '3' },
			{ key: '15' },
			{ key: '1' },
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

	it( 'should filter out elements that do not contain a key', () => {
		const elements = [
			{ key: '3' },
			{},
			{ key: '15' },
			{},
			{ key: '1' },
		] as JSX.Element[];
		const newList = reorderSortableProductAttributePositions(
			elements,
			attributeList
		);
		expect( newList.length ).toEqual( 3 );
	} );
} );
