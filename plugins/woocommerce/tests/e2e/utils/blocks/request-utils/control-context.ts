/**
 * External dependencies
 */
import { readFile } from 'fs/promises';
import {
	request,
	type APIRequest,
	type APIRequestContext,
	type Cookie,
} from '@playwright/test';

export type ControlRequestStorageState = {
	cookies: Cookie[];
	nonce: string;
	rootURL: string;
};

export type ControlRequestContext = {
	requestContext: APIRequestContext;
	storageState?: ControlRequestStorageState;
};

export async function createControlRequestContext( options: {
	apiRequest?: APIRequest;
	baseURL: string;
	storageStatePath?: string;
} ): Promise< ControlRequestContext > {
	let storageState: ControlRequestStorageState | undefined;

	if ( options.storageStatePath ) {
		try {
			storageState = JSON.parse(
				await readFile( options.storageStatePath, 'utf8' )
			) as ControlRequestStorageState;
		} catch ( error ) {
			if (
				! ( error instanceof Error ) ||
				! ( 'code' in error ) ||
				error.code !== 'ENOENT'
			) {
				throw error;
			}
		}
	}

	const requestContext = await ( options.apiRequest ?? request ).newContext( {
		baseURL: options.baseURL,
		extraHTTPHeaders: { Connection: 'close' },
		storageState: storageState
			? { cookies: storageState.cookies, origins: [] }
			: undefined,
	} );

	return { requestContext, storageState };
}
