export function setSearchParams(
	name: string | undefined,
	value: unknown,
	params: URLSearchParams
): void {
	if ( value === undefined ) {
		return;
	}
	if ( Array.isArray( value ) ) {
		value.forEach( ( item, index ) => {
			setSearchParams( `${ name }[${ index }]`, item, params );
		} );
		return;
	}
	if ( typeof value === 'object' && value !== null ) {
		Object.entries( value ).forEach( ( [ prop, item ] ) => {
			if ( name ) {
				setSearchParams( `${ name }[${ prop }]`, item, params );
			} else {
				setSearchParams( prop, item, params );
			}
		} );
		return;
	}
	if ( name !== undefined ) {
		params.set( name, `${ value }` );
	}
}

export function checkStatus( response: Response ) {
	if ( response.status >= 200 && response.status < 300 ) {
		return response;
	}

	throw response;
}

const NONCE_TTL = 1000 * 60;
let nonce: string | undefined = undefined;
let lastNonceCheck = Date.now();

export async function getNonce() {
	const isNonceActive = Date.now() - lastNonceCheck < NONCE_TTL;

	if ( isNonceActive && nonce ) {
		return nonce;
	}

	return fetch( '/wp-admin/admin-ajax.php?action=rest-nonce', {
		credentials: 'include',
	} )
		.then( checkStatus )
		.then( ( response ) => {
			lastNonceCheck = Date.now();
			return response.text();
		} );
}

export async function apiFetch(
	input: RequestInfo,
	{ params, ...init }: RequestInit & { params?: unknown } = {}
) {
	const requestParams = new URLSearchParams();

	setSearchParams( undefined, params, requestParams );

	const isNonceExpired = Date.now() - lastNonceCheck > NONCE_TTL;
	const needsNewNonce = nonce === undefined || isNonceExpired;
	if ( needsNewNonce ) {
		nonce = await getNonce();
	}

	const urlOrRequest =
		typeof input === 'string' && requestParams.size > 0
			? `${ input }?${ requestParams }`
			: input;

	return fetch( urlOrRequest, {
		...init,
		headers: {
			'Content-Type': 'application/json',
			...init.headers,
			'X-Wp-Nonce': await getNonce(),
		},
		credentials: init.credentials || 'include',
	} ).then( checkStatus );
}
