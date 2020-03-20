/**
 * Internal dependencies
 */
import { emitEvent, emitEventWithAbort } from '../emitters';

describe( 'Testing emitters', () => {
	let observerMocks = {};
	beforeEach( () => {
		observerMocks = {
			observerA: jest.fn().mockReturnValue( true ),
			observerB: jest.fn().mockReturnValue( true ),
			observerReturnValue: jest.fn().mockReturnValue( 10 ),
			observerPromiseWithReject: jest
				.fn()
				.mockRejectedValue( 'an error' ),
			observerPromiseWithResolvedValue: jest.fn().mockResolvedValue( 10 ),
		};
	} );
	describe( 'Testing emitEvent()', () => {
		it( 'invokes all observers', async () => {
			const observers = { test: Object.values( observerMocks ) };
			const response = await emitEvent( observers, 'test', 'foo' );
			expect( console ).toHaveErroredWith( 'an error' );
			expect( observerMocks.observerA ).toHaveBeenCalledTimes( 1 );
			expect( observerMocks.observerB ).toHaveBeenCalledWith( 'foo' );
			expect( response ).toBe( true );
		} );
	} );
	describe( 'Testing emitEventWithAbort()', () => {
		it(
			'aborts on non truthy value and does not invoke remaining ' +
				'observers',
			async () => {
				const observers = { test: Object.values( observerMocks ) };
				const response = await emitEventWithAbort(
					observers,
					'test',
					'foo'
				);
				expect( console ).not.toHaveErrored();
				expect( observerMocks.observerB ).toHaveBeenCalledTimes( 1 );
				expect(
					observerMocks.observerPromiseWithResolvedValue
				).not.toHaveBeenCalled();
				expect( response ).toBe( 10 );
			}
		);
	} );
} );
