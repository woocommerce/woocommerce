/**
 * Internal dependencies
 */
import { showNoticeError, updateNotices } from '../notices';

type Fn = ( ...args: never[] ) => unknown;

/**
 * A stand-in for `@wordpress/interactivity`, whose `withScope`
 * (`src/utils.ts`) reproduces the one behaviour this suite depends on: a
 * `GeneratorFunction` has the scope captured at the call site restored
 * before every resumption and cleared right after; any other function only
 * has it live for its synchronous body. A helper that keeps the caller's
 * scope live across a suspension (a generator delegated with `yield*`, or
 * driven directly by `withScope`) satisfies this; a plain `async function`
 * whose continuation resumes after its own suspension does not, because by
 * then the driver already cleared the scope.
 *
 * Everything else about the real runtime — signals, directives, actual scope
 * objects — is irrelevant here: only whether a scope is live when the
 * `store-notices` `addNotice` action actually runs needs to be modeled.
 */
jest.mock(
	'@wordpress/interactivity',
	() => {
		let liveScope: string | null = null;
		const observedScopes: ( string | null )[] = [];

		function withScope< T extends Fn >( fn: T ): T {
			const capturedScope = liveScope;

			if ( fn?.constructor?.name === 'GeneratorFunction' ) {
				const wrapped = async ( ...args: unknown[] ) => {
					const gen = fn( ...( args as never[] ) ) as Generator;
					let value: unknown;
					for (;;) {
						liveScope = capturedScope;
						let step: IteratorResult< unknown >;
						try {
							step = gen.next( value );
						} finally {
							liveScope = null;
						}
						if ( step.done ) {
							return step.value;
						}
						value = await step.value;
					}
				};
				return wrapped as unknown as T;
			}

			const wrapped = ( ...args: unknown[] ) => {
				liveScope = capturedScope;
				try {
					return fn( ...( args as never[] ) );
				} finally {
					liveScope = null;
				}
			};
			return wrapped as unknown as T;
		}

		return {
			getConfig: jest.fn(),
			getContext: jest.fn(),
			withScope,
			store: jest.fn( () => ( {
				state: { notices: [] },
				get actions() {
					return {
						addNotice: withScope( () => {
							observedScopes.push( liveScope );
							return String( observedScopes.length );
						} ),
						removeNotice: withScope( () => undefined ),
					};
				},
			} ) ),
			__setLiveScope: ( scope: string | null ) => {
				liveScope = scope;
			},
			__getObservedScopes: () => observedScopes,
			__resetObservedScopes: () => {
				observedScopes.length = 0;
			},
		};
	},
	{ virtual: true }
);

type InteractivityTestDouble = {
	withScope: < T extends Fn >( fn: T ) => T;
	__setLiveScope: ( scope: string | null ) => void;
	__getObservedScopes: () => ( string | null )[];
	__resetObservedScopes: () => void;
};

const interactivity = jest.requireMock(
	'@wordpress/interactivity'
) as InteractivityTestDouble;

describe( 'the private notice helpers keep the caller scope live across their dynamic import', () => {
	let consoleErrorSpy: jest.SpyInstance;

	beforeEach( () => {
		interactivity.__resetObservedScopes();
		interactivity.__setLiveScope( null );
		consoleErrorSpy = jest
			.spyOn( console, 'error' )
			.mockImplementation( () => undefined );
	} );

	afterEach( () => {
		consoleErrorSpy.mockRestore();
	} );

	it( 'showNoticeError adds the notice while the scope live at its call site is still live, the way cart-actions.ts drives it (`withScope( showNoticeError )( ... )`)', async () => {
		interactivity.__setLiveScope( 'calling-scope' );

		await interactivity.withScope( showNoticeError )(
			new Error( 'Server rejected the request.' )
		);

		expect( interactivity.__getObservedScopes() ).toEqual( [
			'calling-scope',
		] );
	} );

	it( 'updateNotices adds its notices while the scope live at its call site is still live, when a caller delegates into it with `yield*` the way cart-actions.ts does', async () => {
		interactivity.__setLiveScope( 'calling-scope' );

		function* callingAction(): Generator< unknown, void, unknown > {
			yield* updateNotices( [
				{ notice: 'Hello', type: 'notice', dismissible: true },
			] );
		}

		await interactivity.withScope( callingAction )();

		expect( interactivity.__getObservedScopes() ).toEqual( [
			'calling-scope',
		] );
	} );
} );
