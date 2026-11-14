/**
 * External dependencies
 */
import { existsSync, readFileSync } from 'fs';

/**
 * Internal dependencies
 */
export {
	median,
	getMetricUnit,
	formatMetricValue,
} from '../../../../tools/compare-perf/metric-utils.js';

export function readFile( filePath ) {
	if ( ! existsSync( filePath ) ) {
		throw new Error( `File does not exist: ${ filePath }` );
	}

	return readFileSync( filePath, 'utf8' ).trim();
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

function getEmptyWooEditorAssetMetrics() {
	return {
		wooEditorAssetCount: 0,
		wooEditorNetworkTransferAssetSize: 0,
		wooEditorNetworkTransferScriptSize: 0,
		wooEditorNetworkTransferStyleSize: 0,
	};
}

function formatWooEditorAssetMetrics( metrics ) {
	return {
		wooEditorAssetCount: metrics.wooEditorAssetCount,
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

export async function startWooEditorAssetMetrics( page ) {
	const client = await page.context().newCDPSession( page );
	const { frameTree } = await client.send( 'Page.getFrameTree' );
	const mainFrameId = frameTree.frame.id;
	const requests = new Map();
	const assetUrls = new Set();
	const metrics = getEmptyWooEditorAssetMetrics();
	let isCollecting = true;

	const onRequestWillBeSent = ( event ) => {
		// Exclude assets loaded within editor iframes.
		if ( event.frameId !== mainFrameId ) {
			return;
		}

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
		metrics.wooEditorAssetCount += 1;

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
			return formatWooEditorAssetMetrics( metrics );
		}

		isCollecting = false;
		client.off( 'Network.requestWillBeSent', onRequestWillBeSent );
		client.off( 'Network.loadingFinished', onLoadingFinished );
		await client.detach();

		return formatWooEditorAssetMetrics( metrics );
	};
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
