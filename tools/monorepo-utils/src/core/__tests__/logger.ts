jest.spyOn( global.console, 'error' ).mockImplementation( () => {} );
// @ts-expect-error -- We're mocking process exit, it has never return type!
jest.spyOn( global.process, 'exit' ).mockImplementation( () => {} );

/**
 * External dependencies
 */
import chalk from 'chalk';

/**
 * Internal dependencies
 */
import { Logger } from '../logger';

describe( 'Logger', () => {
	afterEach( () => {
		jest.resetAllMocks();
	} );

	describe( 'error', () => {
		process.env.LOGGER_LEVEL = 'error';

		it( 'should log a message for string messages', () => {
			const message = 'test message';

			Logger.error( message );

			expect( global.console.error ).toHaveBeenCalledWith(
				chalk.red( message )
			);
		} );

		it( 'should log a message for errors', () => {
			const error = new Error( 'test error' );

			Logger.error( error );

			expect( global.console.error ).toHaveBeenCalledWith(
				chalk.red( `${ error.message }\n${ error.stack }` )
			);
		} );

		it( 'should json stringify for unknown types', () => {
			Logger.error( { foo: 'bar' } );

			expect( global.console.error ).toHaveBeenCalledWith(
				chalk.red( JSON.stringify( { foo: 'bar' }, null, 2 ) )
			);
		} );

		it( 'should call process.exit by default', () => {
			Logger.error( 'test message' );

			expect( global.process.exit ).toHaveBeenCalledWith( 1 );
		} );

		it( 'should not call process.exit when failOnErr is false', () => {
			Logger.error( 'test message', false );

			expect( global.process.exit ).not.toHaveBeenCalled();
		} );

		it( 'should not log errors if the Logger is in silent mode', () => {
			process.env.LOGGER_LEVEL = 'silent';

			Logger.error( 'test message' );

			expect( global.console.error ).not.toHaveBeenCalled();
		} );
	} );
} );
