/**
 * External dependencies
 */
import { responseTypes } from '@woocommerce/types';

/**
 * Internal dependencies
 */
import { createEmitter } from '../event-emitter';

describe( 'Event emitter v2', () => {
	it( 'allows multiple callbacks to subscribe to events', async () => {
		const emitter = createEmitter();
		const callback = jest.fn();
		const callback2 = jest.fn();
		const testEventName = 'test';
		emitter.subscribe( callback, 10, testEventName );
		emitter.subscribe( callback2, 10, testEventName );
		await emitter.emit( testEventName, 'test data' );
		expect( callback ).toHaveBeenCalledWith( 'test data' );
		expect( callback2 ).toHaveBeenCalledWith( 'test data' );
	} );

	it( 'allows multiple callbacks to subscribe to events with different priorities', async () => {
		const emitter = createEmitter();
		const callback = jest.fn();
		const callback2 = jest.fn();
		const testEventName = 'test';
		emitter.subscribe( callback, 10, testEventName );
		emitter.subscribe( callback2, 5, testEventName );
		await emitter.emit( testEventName, 'test data' );
		expect( callback2 ).toHaveBeenCalledWith( 'test data' );
		expect( callback ).toHaveBeenCalledWith( 'test data' );
	} );

	it( 'allows multiple callbacks to subscribe to different events', async () => {
		const emitter = createEmitter();
		const callbackEvent1 = jest.fn();
		const callback2Event1 = jest.fn();
		const callbackEvent2 = jest.fn();
		const callback2Event2 = jest.fn();
		const testEventName = 'test';
		const testEventName2 = 'test2';
		emitter.subscribe( callbackEvent1, 10, testEventName );
		emitter.subscribe( callback2Event1, 10, testEventName );
		emitter.subscribe( callbackEvent2, 10, testEventName2 );
		emitter.subscribe( callback2Event2, 10, testEventName2 );
		await emitter.emit( testEventName, 'test data' );
		await emitter.emit( testEventName2, 'test data 2' );
		expect( callbackEvent1 ).toHaveBeenCalledWith( 'test data' );
		expect( callback2Event1 ).toHaveBeenCalledWith( 'test data' );
		expect( callbackEvent2 ).toHaveBeenCalledWith( 'test data 2' );
		expect( callback2Event2 ).toHaveBeenCalledWith( 'test data 2' );
	} );

	it( 'allows unsubscribing from events', async () => {
		const emitter = createEmitter();
		const callback = jest.fn();
		const testEventName = 'test';
		const unsubscribe = emitter.subscribe( callback, 10, testEventName );
		await emitter.emit( testEventName, 'test data' );
		expect( callback ).toHaveBeenCalledWith( 'test data' );
		unsubscribe();
		await emitter.emit( testEventName, 'test data' );
		expect( callback ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'allows observers to return their results in a promises', async () => {
		const emitter = createEmitter();
		const callback = jest
			.fn()
			.mockReturnValue(
				Promise.resolve( { type: responseTypes.SUCCESS } )
			);
		const testEventName = 'test';
		emitter.subscribe( callback, 10, testEventName );
		const responses = await emitter.emit( testEventName, 'test data' );
		expect( callback ).toHaveBeenCalledWith( 'test data' );
		expect( responses ).toHaveLength( 1 );
	} );

	it( 'emits events with abort, preventing subsequent observers from running after first fail', async () => {
		const emitter = createEmitter();
		const callback = jest
			.fn()
			.mockReturnValue( { type: responseTypes.ERROR } );
		const callback2 = jest.fn();
		const testEventName = 'test';
		emitter.subscribe( callback, 10, testEventName );
		emitter.subscribe( callback2, 10, testEventName );
		const responses = await emitter.emitWithAbort(
			testEventName,
			'test data'
		);
		expect( callback ).toHaveBeenCalledWith( 'test data' );
		expect( callback2 ).not.toHaveBeenCalled();
		expect( responses ).toHaveLength( 1 );
	} );

	it( 'continues executing subsequent observers if one throws on emit', () => {
		const emitter = createEmitter();
		const callback = jest.fn().mockImplementation( () => {
			throw new Error( 'test error' );
		} );
		const callback2 = jest.fn();
		const testEventName = 'test';
		emitter.subscribe( callback, 10, testEventName );
		emitter.subscribe( callback2, 10, testEventName );
		emitter.emit( testEventName, 'test data' );
		expect( callback ).toHaveBeenCalledWith( 'test data' );
		expect( callback2 ).toHaveBeenCalledWith( 'test data' );
		expect( console ).toHaveErroredWith( new Error( 'test error' ) );
	} );

	it( 'stops executing subsequent observers if one throws on emitWithAbort', async () => {
		const emitter = createEmitter();
		const callback = jest.fn().mockImplementation( () => {
			throw new Error( 'test error' );
		} );
		const callback2 = jest.fn();
		const testEventName = 'test';
		emitter.subscribe( callback, 10, testEventName );
		emitter.subscribe( callback2, 10, testEventName );
		const responses = await emitter.emitWithAbort(
			testEventName,
			'test data'
		);
		expect( callback ).toHaveBeenCalledWith( 'test data' );
		expect( callback2 ).not.toHaveBeenCalledWith( 'test data' );
		expect( console ).toHaveErroredWith( new Error( 'test error' ) );
		expect( responses ).toHaveLength( 1 );
		expect( responses[ 0 ] ).toEqual( { type: responseTypes.ERROR } );
	} );
} );
