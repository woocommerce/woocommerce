/**
 * External dependencies
 */
import { existsSync, readFileSync } from 'fs';

export function median( array ) {
	if ( ! array || ! array.length ) return undefined;

	const numbers = [ ...array ].sort( ( a, b ) => a - b );
	const middleIndex = Math.floor( numbers.length / 2 );

	if ( numbers.length % 2 === 0 ) {
		return ( numbers[ middleIndex - 1 ] + numbers[ middleIndex ] ) / 2;
	}
	return numbers[ middleIndex ];
}

export function readFile( filePath ) {
	if ( ! existsSync( filePath ) ) {
		throw new Error( `File does not exist: ${ filePath }` );
	}

	return readFileSync( filePath, 'utf8' ).trim();
}

export function getMetricUnit( metric ) {
	if ( metric.endsWith( 'Size' ) ) {
		return 'KB';
	}

	if ( metric.endsWith( 'Count' ) ) {
		return 'count';
	}

	return 'ms';
}

export function formatMetricValue( metric, value ) {
	return `${ value } ${ getMetricUnit( metric ) }`;
}

const WOO_BLOCKS_ASSETS_PATH =
	'/wp-content/plugins/woocommerce/assets/client/blocks/';
const KB = 1024;

function roundSize( value ) {
	return Math.round( value * 100 ) / 100;
}

function getWooEditorAssetUrl( rawUrl, baseUrl ) {
	const url = new URL( rawUrl, baseUrl );

	if (
		! url.pathname.includes( WOO_BLOCKS_ASSETS_PATH ) ||
		! /\.(js|css)$/.test( url.pathname )
	) {
		return null;
	}

	return {
		normalizedUrl: url.origin + url.pathname,
		isScript: url.pathname.endsWith( '.js' ),
		isStyle: url.pathname.endsWith( '.css' ),
	};
}

function getEmptyWooEditorNetworkTransferMetrics() {
	return {
		wooEditorNetworkTransferAssetSize: 0,
		wooEditorNetworkTransferScriptSize: 0,
		wooEditorNetworkTransferStyleSize: 0,
	};
}

function formatWooEditorNetworkTransferMetrics( metrics ) {
	return {
		wooEditorNetworkTransferAssetSize: roundSize(
			metrics.wooEditorNetworkTransferAssetSize / KB
		),
		wooEditorNetworkTransferScriptSize: roundSize(
			metrics.wooEditorNetworkTransferScriptSize / KB
		),
		wooEditorNetworkTransferStyleSize: roundSize(
			metrics.wooEditorNetworkTransferStyleSize / KB
		),
	};
}

export async function startWooEditorNetworkTransferMetrics( page ) {
	const client = await page.context().newCDPSession( page );
	const requests = new Map();
	const assetUrls = new Set();
	const metrics = getEmptyWooEditorNetworkTransferMetrics();
	let isCollecting = true;

	const onRequestWillBeSent = ( event ) => {
		const assetUrl = getWooEditorAssetUrl( event.request.url, page.url() );

		if ( assetUrl ) {
			requests.set( event.requestId, assetUrl );
		}
	};

	const onLoadingFinished = ( event ) => {
		const assetUrl = requests.get( event.requestId );

		if ( ! assetUrl || assetUrls.has( assetUrl.normalizedUrl ) ) {
			return;
		}

		assetUrls.add( assetUrl.normalizedUrl );

		const size = event.encodedDataLength || 0;
		metrics.wooEditorNetworkTransferAssetSize += size;

		if ( assetUrl.isScript ) {
			metrics.wooEditorNetworkTransferScriptSize += size;
		}

		if ( assetUrl.isStyle ) {
			metrics.wooEditorNetworkTransferStyleSize += size;
		}
	};

	client.on( 'Network.requestWillBeSent', onRequestWillBeSent );
	client.on( 'Network.loadingFinished', onLoadingFinished );
	await client.send( 'Network.enable' );

	return async () => {
		if ( ! isCollecting ) {
			return formatWooEditorNetworkTransferMetrics( metrics );
		}

		isCollecting = false;
		client.off( 'Network.requestWillBeSent', onRequestWillBeSent );
		client.off( 'Network.loadingFinished', onLoadingFinished );
		await client.detach();

		return formatWooEditorNetworkTransferMetrics( metrics );
	};
}

export async function getWooEditorAssetMetrics( page ) {
	return await page.evaluate( () => {
		const wooBlocksAssetsPath =
			'/wp-content/plugins/woocommerce/assets/client/blocks/';
		const assetUrls = new Set();
		const metrics = {
			wooEditorAssetCount: 0,
		};

		// This intentionally excludes assets loaded only within editor iframes,
		// which have their own resource performance timelines.
		performance.getEntriesByType( 'resource' ).forEach( ( entry ) => {
			const url = new URL( entry.name, window.location.href );
			const normalizedUrl = url.origin + url.pathname;

			if (
				! url.pathname.includes( wooBlocksAssetsPath ) ||
				! /\.(js|css)$/.test( url.pathname ) ||
				assetUrls.has( normalizedUrl )
			) {
				return;
			}

			assetUrls.add( normalizedUrl );
			metrics.wooEditorAssetCount += 1;
		} );

		return {
			wooEditorAssetCount: metrics.wooEditorAssetCount,
		};
	} );
}

export async function getTotalBlockingTime( page, idleWait ) {
	const totalBlockingTime = await page.evaluate( async ( waitTime ) => {
		return new Promise( ( resolve ) => {
			const longTaskEntries = [];
			// Create a performance observer to observe long task entries
			new PerformanceObserver( ( list ) => {
				const entries = list.getEntries();
				// Store each long task entry in the longTaskEntries array
				entries.forEach( ( entry ) => longTaskEntries.push( entry ) );
			} ).observe( { type: 'longtask', buffered: true } );

			// Give some time to collect entries
			setTimeout( () => {
				// Calculate the total blocking time by summing the durations of all long tasks
				const tbt = longTaskEntries.reduce(
					( acc, entry ) => acc + entry.duration,
					0
				);
				resolve( tbt );
			}, waitTime );
		} );
	}, idleWait );
	return totalBlockingTime;
}
