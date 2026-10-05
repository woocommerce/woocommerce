/**
 * External dependencies
 */
import { beforeEach, afterEach, expect, vi } from 'vitest';

const observed = new WeakSet();
let spies;
const matchers = {};
for ( const [ name, method ] of Object.entries( {
	toHaveWarned: 'warn',
	toHaveErrored: 'error',
	toHaveLogged: 'log',
	toHaveInformed: 'info',
} ) ) {
	matchers[ name ] = ( received ) => {
		observed.add( received[ method ] );
		return {
			pass: received[ method ].mock.calls.length > 0,
			message: () => `Expected console.${ method } to have been called.`,
		};
	};
	matchers[ `${ name }With` ] = function ( received, ...args ) {
		observed.add( received[ method ] );
		return {
			pass: received[ method ].mock.calls.some( ( call ) =>
				this.equals( call, args )
			),
			message: () =>
				`Expected console.${ method } to have been called with ${ this.utils.printExpected(
					args
				) }.`,
		};
	};
}
expect.extend( matchers );

beforeEach( () => {
	spies = [ 'warn', 'error', 'log', 'info' ].map( ( method ) => {
		const implementation = () => {};
		const spy = vi
			.spyOn( console, method )
			.mockImplementation( implementation );
		const proxy = new Proxy( spy, {
			get( target, property, receiver ) {
				// Native spy assertions read mock state; count them as expected output too.
				if ( property === 'mock' ) observed.add( proxy );
				return Reflect.get( target, property, receiver );
			},
		} );
		console[ method ] = proxy;
		return { method, spy, proxy, implementation };
	} );
} );

afterEach( () => {
	for ( const { method, spy, proxy, implementation } of spies ) {
		if (
			observed.has( proxy ) ||
			spy.getMockImplementation() !== implementation
		)
			continue;
		const calls = spy.mock.calls.filter(
			( [ message ] ) =>
				! (
					typeof message === 'string' &&
					/deprecated|Deprecation warning:|Store ".*" is already registered|The block ".*" is registered with an invalid category "woocommerce"/.test(
						message
					)
				)
		);
		expect( calls, `Unexpected console.${ method } output` ).toEqual( [] );
	}
} );
