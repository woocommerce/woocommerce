declare const process:
	| {
			env: {
				NODE_ENV?: string;
			};
	  }
	| undefined;

const isDevelopment = () => {
	if ( typeof process === 'undefined' ) {
		return false;
	}

	return process.env.NODE_ENV !== 'production';
};

export const warn = ( message: string, context?: unknown ) => {
	if ( ! isDevelopment() ) {
		return;
	}

	if ( context ) {
		// eslint-disable-next-line no-console
		console.warn( `[WooCommerce modern settings] ${ message }`, context );
		return;
	}

	// eslint-disable-next-line no-console
	console.warn( `[WooCommerce modern settings] ${ message }` );
};
