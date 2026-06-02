/// <reference path="./__experimental-fetch-link-suggestions.d.ts" />
/// <reference path="./__experimental-fetch-url-data.d.ts" />

declare module '@wordpress/core-data/build-types/fetch' {
	export function fetchBlockPatterns(): Promise<any>;
	export { default as __experimentalFetchLinkSuggestions } from "@wordpress/core-data/build-types/fetch/__experimental-fetch-link-suggestions";
	export { default as __experimentalFetchUrlData } from "@wordpress/core-data/build-types/fetch/__experimental-fetch-url-data";
}
