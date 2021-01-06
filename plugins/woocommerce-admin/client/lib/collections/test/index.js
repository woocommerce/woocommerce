/**
 * Internal dependencies
 */
import { groupListOfObjectsBy, setAllPropsToValue } from '../index.js';

describe( 'groupListOfObjectsBy', () => {
	const objectList = [
		{
			id: '1',
			name: 'Object name 1',
			type: 'type1',
		},
		{
			id: '2',
			name: 'Object name 2',
			type: 'type1',
		},
		{
			id: '3',
			name: 'Object name 3',
			type: 'type1',
		},
		{
			id: '4',
			name: 'Object name 4',
			type: 'type2',
		},
	];

	it( 'handles different params', () => {
		// Using these params should return an empty object.
		const emptyObject = groupListOfObjectsBy();
		const otherEmptyObject = groupListOfObjectsBy( [] );
		const anotherEmptyObject = groupListOfObjectsBy( 'not an array' );
		expect( emptyObject ).toMatchObject( {} );
		expect( otherEmptyObject ).toMatchObject( {} );
		expect( anotherEmptyObject ).toMatchObject( {} );

		// Not sending a key to use for grouping the elements will return the sent list
		const ungroupedList = groupListOfObjectsBy( objectList );
		expect( ungroupedList.length ).toBe( 4 );
	} );

	it( 'groups objects by type', () => {
		const { type1, type2 } = groupListOfObjectsBy( objectList, 'type' );
		expect( type1.length ).toBe( 3 );
		expect( type2.length ).toBe( 1 );
	} );

	it( 'groups objects without type', () => {
		const objectWithoutType = {
			id: '5',
			name: 'Object name 5',
		};
		objectList.push( objectWithoutType );
		const { type1, type2 } = groupListOfObjectsBy(
			objectList,
			'type',
			'type2'
		);
		expect( type1.length ).toBe( 3 );
		expect( type2.length ).toBe( 2 );
	} );

	it( 'groups objects with a new type', () => {
		const objectWithNewType = {
			id: '6',
			name: 'Object name 4',
			type: 'type3',
		};
		objectList.push( objectWithNewType );
		const { type1, type2, type3 } = groupListOfObjectsBy(
			objectList,
			'type',
			'type2'
		);
		expect( type1.length ).toBe( 3 );
		expect( type2.length ).toBe( 2 );
		expect( type3.length ).toBe( 1 );
	} );
} );

describe( 'setAllPropsToValue', () => {
	it( 'sets all the shallow props of the returned object to the passed value', () => {
		const targetObject = {
			one: 1,
			two: 'two',
			three: false,
			four: { five: 'six' },
		};

		expect( setAllPropsToValue( targetObject, 'hello' ) ).toEqual( {
			one: 'hello',
			two: 'hello',
			three: 'hello',
			four: 'hello',
		} );
	} );

	it( 'does not mutate the passed in object', () => {
		const targetObject = {
			one: 1,
			two: 'two',
			three: false,
			four: { five: 'six' },
		};

		setAllPropsToValue( targetObject, 'hello' );

		expect( targetObject ).toEqual( {
			one: 1,
			two: 'two',
			three: false,
			four: { five: 'six' },
		} );
	} );
} );
