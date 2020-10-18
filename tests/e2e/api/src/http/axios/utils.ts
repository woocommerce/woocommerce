import { AxiosRequestConfig } from 'axios';

/**
 * Given an Axios request config this function generates the URL that Axios will
 * use to make the request.
 *
 * @param {AxiosRequestConfig} request The Axios request we're building the URL for.
 * @return {string} The merged URL.
 */
export function buildURL( request: AxiosRequestConfig ): string {
	const base = request.baseURL || '';
	if ( ! request.url ) {
		return base;
	}

	// Axios ignores the base when the URL is absolute.
	const url = request.url;
	if ( ! base || url.match( /^([a-z][a-z\d+\-.]*:)?\/\/[^\/]/i ) ) {
		return url;
	}

	// Remove trailing slashes from the base and leading slashes from the URL so we can combine them consistently.
	return base.replace( /\/+$/, '' ) + '/' + url.replace( /^\/+/, '' );
}
