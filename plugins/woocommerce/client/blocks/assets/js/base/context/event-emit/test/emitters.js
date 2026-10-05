import { beforeEach, describe, expect, it, vi } from 'vitest';

/**
 * Internal dependencies
 */
import { emitEvent, emitEventWithAbort } from '../emitters';

describe( 'Testing emitters', () => {
	let observerMocks = {};
	let observerA;
	let observerB;
	let observerPromiseWithResolvedValue;
	beforeEach( () => {
		observerA = vi.fn().mockReturnValue( true );
		observerB = vi.fn().mockReturnValue( true );
		observerPromiseWithResolvedValue = vi.fn().mockResolvedValue( 10 );
		observerMocks = new Map( [
			[ 'observerA', { priority: 10, callback: observerA } ],
			[ 'observerB', { priority: 10, callback: observerB } ],
			[
				'observerReturnValue',
				{ priority: 10, callback: vi.fn().mockReturnValue( 10 ) },
			],
			[
				'observerPromiseWithReject',
				{
					priority: 10,
					callback: vi.fn().mockRejectedValue( 'an error' ),
				},
			],
			[
				'observerPromiseWithResolvedValue',
				{ priority: 10, callback: observerPromiseWithResolvedValue },
			],
			[
				'observerSuccessType',
				{
					priority: 10,
					callback: vi.fn().mockReturnValue( { type: 'success' } ),
				},
			],
		] );
	} );
	describe( 'Testing emitEvent()', () => {
		it( 'invokes all observers', async () => {
			const observers = { test: observerMocks };
			const response = await emitEvent( observers, 'test', 'foo' );
			expect( console ).toHaveErroredWith( 'an error' );
			expect( observerA ).toHaveBeenCalledTimes( 1 );
			expect( observerB ).toHaveBeenCalledWith( 'foo' );
			expect( response ).toEqual( [ { type: 'success' } ] );
		} );
	} );
	describe( 'Testing emitEventWithAbort()', () => {
		it( 'does not abort on any return value other than an object with an error or fail type property', async () => {
			observerMocks.delete( 'observerPromiseWithReject' );
			const observers = { test: observerMocks };
			const response = await emitEventWithAbort(
				observers,
				'test',
				'foo'
			);
			expect( console ).not.toHaveErrored();
			expect( observerB ).toHaveBeenCalledTimes( 1 );
			expect( observerPromiseWithResolvedValue ).toHaveBeenCalled();
			expect( response ).toEqual( [ { type: 'success' } ] );
		} );
		it( 'Aborts on a return value with an object that has a a fail type property', async () => {
			const validObjectResponse = vi
				.fn()
				.mockReturnValue( { type: 'failure' } );
			observerMocks.set( 'observerValidObject', {
				priority: 5,
				callback: validObjectResponse,
			} );
			const observers = { test: observerMocks };
			const response = await emitEventWithAbort(
				observers,
				'test',
				'foo'
			);
			expect( console ).not.toHaveErrored();
			expect( validObjectResponse ).toHaveBeenCalledTimes( 1 );
			expect( observerPromiseWithResolvedValue ).not.toHaveBeenCalled();
			expect( response ).toEqual( [ { type: 'failure' } ] );
		} );
		it( 'throws an error on an object returned from observer without a type property', async () => {
			const failingObjectResponse = vi.fn().mockReturnValue( {} );
			observerMocks.set( 'observerInvalidObject', {
				priority: 5,
				callback: failingObjectResponse,
			} );
			const observers = { test: observerMocks };
			const response = await emitEventWithAbort(
				observers,
				'test',
				'foo'
			);
			expect( console ).toHaveErrored();
			expect( failingObjectResponse ).toHaveBeenCalledTimes( 1 );
			expect( observerPromiseWithResolvedValue ).not.toHaveBeenCalled();
			expect( response ).toEqual( [ { type: 'error' } ] );
		} );
	} );
	describe( 'Test Priority', () => {
		it( 'executes observers in expected order by priority', async () => {
			const a = vi.fn();
			const b = vi.fn().mockReturnValue( { type: 'error' } );
			const observers = {
				test: new Map( [
					[ 'observerA', { priority: 200, callback: a } ],
					[ 'observerB', { priority: 10, callback: b } ],
				] ),
			};
			await emitEventWithAbort( observers, 'test', 'foo' );
			expect( console ).not.toHaveErrored();
			expect( b ).toHaveBeenCalledTimes( 1 );
			expect( a ).not.toHaveBeenCalled();
		} );
	} );
} );
