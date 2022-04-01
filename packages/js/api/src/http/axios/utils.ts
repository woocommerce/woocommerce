import { AxiosRequestConfig } from 'axios';

// @ts-ignore
import buildFullPath = require( 'axios/lib/core/buildFullPath' );
// @ts-ignore
import appendParams = require( 'axios/lib/helpers/buildURL' );

/**
 * Given an Axios request config this function generates the URL that Axios will
 * use to make the request.
 *
 * @param {AxiosRequestConfig} request The Axios request we're building the URL for.
 * @return {string} The merged URL.
 */
export function buildURL( request: AxiosRequestConfig ): string {
	return buildFullPath( request.baseURL, request.url );
}

/**
 * Given an Axios request config this function generates the URL that Axios will
 * use to make the request with the query parameters included.
 *
 * @param {AxiosRequestConfig} request The Axios request we're building the URL for.
 * @return {string} The merged URL.
 */
export function buildURLWithParams( request: AxiosRequestConfig ): string {
	return appendParams( buildURL( request ), request.params, request.paramsSerializer );
}
