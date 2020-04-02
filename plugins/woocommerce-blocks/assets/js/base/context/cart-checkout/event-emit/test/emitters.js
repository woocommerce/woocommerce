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
		observerA = jest.fn().mockReturnValue( true );
		observerB = jest.fn().mockReturnValue( true );
		observerPromiseWithResolvedValue = jest.fn().mockResolvedValue( 10 );
		observerMocks = new Map( [
			[ 'observerA', observerA ],
			[ 'observerB', observerB ],
			[ 'observerReturnValue', jest.fn().mockReturnValue( 10 ) ],
			[
				'observerPromiseWithReject',
				jest.fn().mockRejectedValue( 'an error' ),
			],
			[
				'observerPromiseWithResolvedValue',
				observerPromiseWithResolvedValue,
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
			expect( response ).toBe( true );
		} );
	} );
	describe( 'Testing emitEventWithAbort()', () => {
		it(
			'aborts on non truthy value and does not invoke remaining ' +
				'observers',
			async () => {
				const observers = { test: observerMocks };
				const response = await emitEventWithAbort(
					observers,
					'test',
					'foo'
				);
				expect( console ).not.toHaveErrored();
				expect( observerB ).toHaveBeenCalledTimes( 1 );
				expect(
					observerPromiseWithResolvedValue
				).not.toHaveBeenCalled();
				expect( response ).toBe( 10 );
			}
		);
	} );
} );
