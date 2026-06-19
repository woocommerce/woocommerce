/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

type WooPaymentsExportResponse = Record< string, unknown >;

type RunWooPaymentsExportOptions = {
	requestExport: () => Promise< WooPaymentsExportResponse >;
	getExportUrl: ( exportId: string ) => Promise< WooPaymentsExportResponse >;
	triggerDownload?: ( downloadUrl: string ) => void;
	maxAttempts?: number;
	pollDelayMs?: number;
};

const getFirstString = (
	response: WooPaymentsExportResponse,
	keys: string[]
) => {
	for ( const key of keys ) {
		const value = response[ key ];

		if ( typeof value === 'string' && value ) {
			return value;
		}
	}

	return undefined;
};

const wait = ( delayMs: number ) =>
	new Promise( ( resolve ) => {
		window.setTimeout( resolve, delayMs );
	} );

const getForcedDownloadUrl = ( downloadUrl: string ) => {
	const separator = downloadUrl.includes( '?' ) ? '&' : '?';

	return `${ downloadUrl }${ separator }force_download=true`;
};

export const triggerWooPaymentsExportDownload = ( downloadUrl: string ) => {
	const anchor = document.createElement( 'a' );

	anchor.href = downloadUrl;
	anchor.download = '';
	anchor.rel = 'noopener noreferrer';
	anchor.style.display = 'none';
	document.body.appendChild( anchor );
	anchor.click();
	anchor.remove();
};

export const runWooPaymentsExport = async ( {
	requestExport,
	getExportUrl,
	triggerDownload = triggerWooPaymentsExportDownload,
	maxAttempts = 5,
	pollDelayMs = 1000,
}: RunWooPaymentsExportOptions ): Promise< string > => {
	const exportResponse = await requestExport();
	const exportId = getFirstString( exportResponse, [ 'export_id', 'id' ] );

	if ( ! exportId ) {
		throw new Error(
			__(
				'WooPayments export did not return an export ID.',
				'woocommerce'
			)
		);
	}

	for ( let attempt = 1; attempt <= maxAttempts; attempt++ ) {
		let urlResponse: WooPaymentsExportResponse = {};

		try {
			urlResponse = await getExportUrl( exportId );
		} catch ( error ) {
			urlResponse = {};
		}

		const downloadUrl = getFirstString( urlResponse, [
			'download_url',
			'url',
		] );
		const status = getFirstString( urlResponse, [ 'status' ] );

		if ( status === 'failed' ) {
			throw new Error(
				__( 'WooPayments export failed.', 'woocommerce' )
			);
		}

		if ( downloadUrl && ( ! status || status === 'success' ) ) {
			triggerDownload( getForcedDownloadUrl( downloadUrl ) );

			return downloadUrl;
		}

		if ( attempt < maxAttempts && pollDelayMs > 0 ) {
			await wait( pollDelayMs );
		}
	}

	throw new Error(
		__( 'WooPayments export did not return a download URL.', 'woocommerce' )
	);
};
