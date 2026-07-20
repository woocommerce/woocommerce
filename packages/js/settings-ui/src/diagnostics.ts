declare const process:
	| {
			env: {
				NODE_ENV?: string;
			};
	  }
	| undefined;

const isDevelopment = () => {
	if ( typeof process === 'undefined' ) {
		return true;
	}

	return process.env.NODE_ENV !== 'production';
};

const warningKeys = new Set< string >();

export const warn = (
	message: string,
	context?: unknown,
	dedupeKey?: string
) => {
	if ( ! isDevelopment() ) {
		return;
	}

	if ( dedupeKey && warningKeys.has( dedupeKey ) ) {
		return;
	}

	if ( dedupeKey ) {
		warningKeys.add( dedupeKey );
	}

	if ( context ) {
		// eslint-disable-next-line no-console
		console.warn( `[WooCommerce settings UI] ${ message }`, context );
		return;
	}

	// eslint-disable-next-line no-console
	console.warn( `[WooCommerce settings UI] ${ message }` );
};

export const __resetWarnings = () => warningKeys.clear();

export const error = ( message: string, context?: unknown ) => {
	if ( context ) {
		// eslint-disable-next-line no-console
		console.error( `[WooCommerce settings UI] ${ message }`, context );
		return;
	}

	// eslint-disable-next-line no-console
	console.error( `[WooCommerce settings UI] ${ message }` );
};
