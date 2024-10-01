export type RemoteLoggerConfig = {
	errorRateLimitMs: number; // in milliseconds
};

export type LogData = {
	/**
	 * The message to log.
	 */
	message: string;
	/**
	 * A feature slug. Defaults to 'woocommerce_core'. The feature must be added to the features list in API before using.
	 */
	feature?: string;
	/**
	 * The severity of the log.
	 */
	severity?:
		| 'emergency'
		| 'alert'
		| 'critical'
		| 'error'
		| 'warning'
		| 'notice'
		| 'info'
		| 'debug';

	/**
	 * The hostname of the client. Automatically set to the current hostname.
	 */
	host?: string;
	/**
	 * Extra data to include in the log.
	 */
	extra?: unknown;
	/**
	 * Tags to add to the log.
	 */
	tags?: string[];
	/**
	 * Properties to add to the log. Unlike `extra`, it won't be serialized to a string.
	 */
	properties?: Record< string, unknown >;
};

export type ErrorData = LogData & {
	trace: string;
};
