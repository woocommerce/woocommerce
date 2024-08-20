/**
 * External dependencies
 */
import debugFactory from 'debug';
import { getSetting } from '@woocommerce/settings';
import TraceKit from 'tracekit';
import { applyFilters } from '@wordpress/hooks';
import { bumpStat } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import { mergeLogData, isDevelopmentEnvironment } from './utils';
import { LogData, ErrorData, RemoteLoggerConfig } from './types';

const debug = debugFactory( 'wc:remote-logging' );
const warnLog = ( message: string ) => {
	// eslint-disable-next-line no-console
	console.warn( 'RemoteLogger: ' + message );
};
const errorLog = ( message: string, ...args: unknown[] ) => {
	// eslint-disable-next-line no-console
	console.error( 'RemoteLogger: ' + message, ...args );
};

export const REMOTE_LOGGING_SHOULD_SEND_ERROR_FILTER =
	'woocommerce_remote_logging_should_send_error';
export const REMOTE_LOGGING_ERROR_DATA_FILTER =
	'woocommerce_remote_logging_error_data';

export const REMOTE_LOGGING_LOG_ENDPOINT_FILTER =
	'woocommerce_remote_logging_log_endpoint';
export const REMOTE_LOGGING_JS_ERROR_ENDPOINT_FILTER =
	'woocommerce_remote_logging_js_error_endpoint';

const REMOTE_LOGGING_LAST_ERROR_SENT_KEY =
	'wc_remote_logging_last_error_sent_time';

const DEFAULT_LOG_DATA: LogData = {
	message: '',
	feature: 'woocommerce_core',
	host: window.location.hostname,
	tags: [ 'woocommerce', 'js' ],
	properties: {
		wp_version: getSetting( 'wpVersion' ),
		wc_version: getSetting( 'wcVersion' ),
	},
};
export class RemoteLogger {
	private config: RemoteLoggerConfig;
	private lastErrorSentTime = 0;

	public constructor( config: RemoteLoggerConfig ) {
		this.config = config;
		this.lastErrorSentTime = parseInt(
			localStorage.getItem( REMOTE_LOGGING_LAST_ERROR_SENT_KEY ) || '0',
			10
		);
	}

	/**
	 * Logs a message to Logstash.
	 *
	 * @param severity  - The severity of the log.
	 * @param message   - The message to log.
	 * @param extraData - Optional additional data to include in the log.
	 */
	public async log(
		severity: Exclude< LogData[ 'severity' ], undefined >,
		message: string,
		extraData?: Partial< Exclude< LogData, 'message' | 'severity' > >
	): Promise< boolean > {
		if ( ! message ) {
			debug( 'Empty message' );
			return false;
		}

		const logData: LogData = mergeLogData( DEFAULT_LOG_DATA, {
			message,
			severity,
			...extraData,
		} );

		debug( 'Logging:', logData );
		return await this.sendLog( logData );
	}

	/**
	 * Logs an error to Logstash.
	 *
	 * @param  error     - The error to log.
	 * @param  extraData - Optional additional data to include in the log.
	 *
	 * @return {Promise<void>} - A promise that resolves when the error is logged.
	 */
	public async error( error: Error, extraData?: Partial< LogData > ) {
		if ( this.isRateLimited() ) {
			return;
		}

		const errorData: ErrorData = {
			...mergeLogData( DEFAULT_LOG_DATA, {
				message: error.message,
				severity: 'error',
				...extraData,
				properties: {
					...extraData?.properties,
					request_uri:
						window.location.pathname + window.location.search,
				},
			} ),
			trace: this.getFormattedStackFrame(
				TraceKit.computeStackTrace( error )
			),
		};

		debug( 'Logging error:', errorData );
		await this.sendError( errorData );
	}

	/**
	 * Initializes error event listeners for catching unhandled errors and unhandled rejections.
	 */
	public initializeErrorHandlers(): void {
		window.addEventListener( 'error', ( event ) => {
			debug( 'Caught error event:', event );
			this.handleError( event.error ).catch( ( error ) => {
				debug( 'Failed to handle error:', error );
			} );
		} );

		window.addEventListener( 'unhandledrejection', async ( event ) => {
			debug( 'Caught unhandled rejection:', event );

			try {
				const error =
					typeof event.reason === 'string'
						? new Error( event.reason )
						: event.reason;
				await this.handleError( error );
			} catch ( error ) {
				debug( 'Failed to handle unhandled rejection:', error );
			}
		} );
	}

	/**
	 * Sends a log entry to the remote API.
	 *
	 * @param logData - The log data to be sent.
	 */
	private async sendLog( logData: LogData ): Promise< boolean > {
		if ( isDevelopmentEnvironment ) {
			debug( 'Skipping send log in development environment' );
			return false;
		}

		const body = new window.FormData();
		body.append( 'params', JSON.stringify( logData ) );

		try {
			debug( 'Sending log to API:', logData );

			/**
			 * Filters the Log API endpoint URL.
			 *
			 * @param {string} endpoint The default Log API endpoint URL.
			 */
			const endpoint = applyFilters(
				REMOTE_LOGGING_LOG_ENDPOINT_FILTER,
				'https://public-api.wordpress.com/rest/v1.1/logstash'
			) as string;

			const response = await window.fetch( endpoint, {
				method: 'POST',
				body,
			} );
			if ( ! response.ok ) {
				throw new Error( `response body: ${ response.body }` );
			}

			return true;
		} catch ( error ) {
			// eslint-disable-next-line no-console
			console.error( 'Failed to send log to API:', error );
			return false;
		}
	}

	/**
	 * Handles an uncaught error and sends it to the remote API.
	 *
	 * @param error - The error to handle.
	 */
	private async handleError( error: Error ) {
		const trace = TraceKit.computeStackTrace( error );
		if ( ! this.shouldHandleError( error, trace.stack ) ) {
			debug( 'Irrelevant error. Skipping handling.', error );
			return;
		}

		// Bump the stat for unhandled JS errors to track the frequency of these errors.
		bumpStat( 'error', 'unhandled-js-errors' );

		if ( this.isRateLimited() ) {
			return;
		}

		const errorData: ErrorData = {
			...mergeLogData( DEFAULT_LOG_DATA, {
				message: error.message,
				severity: 'critical',
				tags: [ 'js-unhandled-error' ],
				properties: {
					request_uri:
						window.location.pathname + window.location.search,
				},
			} ),
			trace: this.getFormattedStackFrame( trace ),
		};
		/**
		 * This filter allows to modify the error data before sending it to the remote API.
		 *
		 * @filter woocommerce_remote_logging_error_data
		 * @param {ErrorData} errorData The error data to be sent.
		 */
		const filteredErrorData = applyFilters(
			REMOTE_LOGGING_ERROR_DATA_FILTER,
			errorData
		) as ErrorData;

		try {
			await this.sendError( filteredErrorData );
		} catch ( _error ) {
			// eslint-disable-next-line no-console
			console.error( 'Failed to send error:', _error );
		}
	}

	/**
	 * Sends an error to the remote API.
	 *
	 * @param error - The error data to be sent.
	 */
	private async sendError( error: ErrorData ) {
		if ( isDevelopmentEnvironment ) {
			debug( 'Skipping send error in development environment' );
			return;
		}

		const body = new window.FormData();
		body.append( 'error', JSON.stringify( error ) );

		try {
			/**
			 * Filters the JS error endpoint URL.
			 *
			 * @param {string} endpoint The default JS error endpoint URL.
			 */
			const endpoint = applyFilters(
				REMOTE_LOGGING_JS_ERROR_ENDPOINT_FILTER,
				'https://public-api.wordpress.com/rest/v1.1/js-error'
			) as string;

			debug( 'Sending error to API:', error );

			await window.fetch( endpoint, {
				method: 'POST',
				body,
			} );
		} catch ( _error: unknown ) {
			// eslint-disable-next-line no-console
			console.error( 'Failed to send error to API:', _error );
		} finally {
			this.lastErrorSentTime = Date.now();
			localStorage.setItem(
				REMOTE_LOGGING_LAST_ERROR_SENT_KEY,
				this.lastErrorSentTime.toString()
			);
		}
	}

	/**
	 * Limits the stack trace to 10 frames and formats it.
	 *
	 * @param stackTrace - The stack trace to format.
	 * @return The formatted stack trace.
	 */
	private getFormattedStackFrame( stackTrace: TraceKit.StackTrace ) {
		const trace = stackTrace.stack
			.slice( 0, 10 )
			.map( this.getFormattedFrame )
			.join( '\n\n' );

		// Set hard limit of 8192 characters for the stack trace so it does not use too much user bandwith and also our computation.
		return trace.length > 8192 ? trace.substring( 0, 8192 ) : trace;
	}

	/**
	 * Formats a single stack frame.
	 *
	 * @param frame - The stack frame to format.
	 * @param index - The index of the frame in the stack.
	 * @return The formatted stack frame.
	 */
	private getFormattedFrame( frame: TraceKit.StackFrame, index: number ) {
		// Format the function name
		const funcName =
			frame.func !== '?' ? frame.func.replace( /"/g, '' ) : 'anonymous';

		// Format the URL
		const url = frame.url.replace( /"/g, '' );

		// Format the context. Limit to 256 characters.
		const context = frame.context
			? frame.context
					.map( ( line ) =>
						line.replace( /^"|"$/g, '' ).replace( /\\"/g, '"' )
					)
					.filter( ( line ) => line.trim() !== '' )
					.join( '\n    ' )
					.substring( 0, 256 )
			: '';

		// Construct the formatted string
		return (
			`#${ index + 1 } at ${ funcName } (${ url }:${ frame.line }:${
				frame.column
			})` + ( context ? `\n${ context }` : '' )
		);
	}

	/**
	 * Determines whether an error should be handled.
	 *
	 * @param error       - The error to check.
	 * @param stackFrames - The stack frames of the error.
	 * @return Whether the error should be handled.
	 */
	private shouldHandleError(
		error: Error,
		stackFrames: TraceKit.StackFrame[]
	) {
		const containsWooCommerceFrame = stackFrames.some(
			( frame ) =>
				frame.url && frame.url.startsWith( getSetting( 'wcAssetUrl' ) )
		);

		/**
		 * This filter allows to control whether an error should be sent to the remote API.
		 *
		 * @filter woocommerce_remote_logging_should_send_error
		 * @param {boolean}               shouldSendError Whether the error should be sent.
		 * @param {Error}                 error           The error object.
		 * @param {TraceKit.StackFrame[]} stackFrames     The stack frames of the error.
		 *
		 */
		return applyFilters(
			REMOTE_LOGGING_SHOULD_SEND_ERROR_FILTER,
			containsWooCommerceFrame,
			error,
			stackFrames
		) as boolean;
	}

	private isRateLimited(): boolean {
		const currentTime = Date.now();
		if (
			currentTime - this.lastErrorSentTime <
			this.config.errorRateLimitMs
		) {
			debug( 'Rate limit reached. Skipping send error' );
			return true;
		}
		return false;
	}
}

let logger: RemoteLogger | null = null;

/**
 * Checks if remote logging is enabled and if the logger is initialized.
 *
 * @return {boolean} - Returns true if remote logging is enabled and the logger is initialized, otherwise false.
 */
function canLog( _logger: RemoteLogger | null ): _logger is RemoteLogger {
	if ( ! window.wcSettings?.isRemoteLoggingEnabled ) {
		debug( 'Remote logging is disabled.' );
		return false;
	}

	if ( ! _logger ) {
		warnLog( 'RemoteLogger is not initialized. Call init() first.' );
		return false;
	}

	return true;
}

/**
 * Initializes the remote logging and error handlers.
 * This function should be called once at the start of the application.
 *
 * @param config - Configuration object for the RemoteLogger.
 *
 */
export function init( config: RemoteLoggerConfig ) {
	if ( ! window.wcSettings?.isRemoteLoggingEnabled ) {
		debug( 'Remote logging is disabled.' );
		return;
	}

	if ( logger ) {
		warnLog( 'RemoteLogger is already initialized.' );
		return;
	}

	try {
		logger = new RemoteLogger( config );
		logger.initializeErrorHandlers();

		debug( 'RemoteLogger initialized.' );
	} catch ( error ) {
		errorLog( 'Failed to initialize RemoteLogger:', error );
	}
}

/**
 * Logs a message or error.
 *
 * This function is inefficient because the data goes over the REST API, so use sparingly.
 *
 * @param severity  - The severity of the log.
 * @param message   - The message to log.
 * @param extraData - Optional additional data to include in the log.
 *
 */
export async function log(
	severity: Exclude< LogData[ 'severity' ], undefined >,
	message: string,
	extraData?: Partial< Exclude< LogData, 'message' | 'severity' > >
): Promise< boolean > {
	if ( ! canLog( logger ) ) {
		return false;
	}

	try {
		return await logger.log( severity, message, extraData );
	} catch ( error ) {
		errorLog( 'Failed to send log:', error );
		return false;
	}
}

/**
 * Captures an error and sends it to the remote API. Respects the error rate limit.
 *
 * @param error     - The error to capture.
 * @param extraData - Optional additional data to include in the log.
 */
export async function captureException(
	error: Error,
	extraData?: Partial< LogData >
) {
	if ( ! canLog( logger ) ) {
		return false;
	}

	try {
		await logger.error( error, extraData );
	} catch ( _error ) {
		errorLog( 'Failed to send log:', _error );
	}
}
