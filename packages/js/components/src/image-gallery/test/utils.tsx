/**
 * External dependencies
 */
import React, { createElement, cloneElement } from 'react';

/**
 * Internal dependencies
 */
import { removeItem, replaceItem } from '../utils';

describe( 'removeItem', () => {
	it( 'should return a new array with the index element removed', () => {
		const arr = [ 'apple', 'orange', 'banana' ];
		const newArr = removeItem( arr, 1 );
		expect( newArr ).toEqual( [ 'apple', 'banana' ] );
	} );

	it( 'should remove the first element correctly', () => {
		const arr = [ 'apple', 'orange', 'banana' ];
		const newArr = removeItem( arr, 0 );
		expect( newArr ).toEqual( [ 'orange', 'banana' ] );
	} );

	it( 'should remove the last element correctly', () => {
		const arr = [ 'apple', 'orange', 'banana' ];
		const newArr = removeItem( arr, 2 );
		expect( newArr ).toEqual( [ 'apple', 'orange' ] );
	} );
} );

describe( 'replaceItem', () => {
	it( 'should add props to component at selected index ', () => {
		const TestElement = <div>Test</div>;
		const arr = [
			cloneElement( TestElement, { id: 1 } ),
			cloneElement( TestElement, { id: 2 } ),
			cloneElement( TestElement, { id: 3 } ),
		];
		const newArr = replaceItem( arr, 1, { added: 123 } );
		expect( newArr[ 0 ].props.added ).toBeUndefined();
		expect( newArr[ 2 ].props.added ).toBeUndefined();
		expect( newArr[ 1 ].props.added ).toEqual( 123 );
	} );

	it( 'should replace preexisting props by the same name', () => {
		const TestElement = <div>Test</div>;
		const arr = [
			cloneElement( TestElement, { id: 1, type: 'apple' } ),
			cloneElement( TestElement, { id: 2, type: 'banana' } ),
			cloneElement( TestElement, { id: 3, type: 'orange' } ),
		];
		const newArr = replaceItem( arr, 2, { type: 'pineapple' } );
		expect( newArr[ 2 ].props.type ).toEqual( 'pineapple' );
	} );
} );
