/**
 * Internal dependencies
 */
import {
	runWooPaymentsExport,
	triggerWooPaymentsExportDownload,
} from '../money-movement/export';

describe( 'WooPayments money movement export helpers', () => {
	it( 'requests an export, fetches the download URL, and starts the download', async () => {
		const requestExport = jest.fn().mockResolvedValue( {
			export_id: 'export_test',
		} );
		const getExportUrl = jest.fn().mockResolvedValue( {
			status: 'success',
			download_url: 'https://example.com/export.csv',
		} );
		const triggerDownload = jest.fn();

		await expect(
			runWooPaymentsExport( {
				requestExport,
				getExportUrl,
				triggerDownload,
				pollDelayMs: 0,
			} )
		).resolves.toBe( 'https://example.com/export.csv' );

		expect( requestExport ).toHaveBeenCalledTimes( 1 );
		expect( getExportUrl ).toHaveBeenCalledWith( 'export_test' );
		expect( triggerDownload ).toHaveBeenCalledWith(
			'https://example.com/export.csv?force_download=true'
		);
	} );

	it( 'polls until a generated export returns a download URL', async () => {
		const requestExport = jest.fn().mockResolvedValue( {
			export_id: 'export_test',
		} );
		const getExportUrl = jest
			.fn()
			.mockResolvedValueOnce( {} )
			.mockResolvedValueOnce( {
				status: 'success',
				download_url: 'https://example.com/export.csv',
			} );

		await expect(
			runWooPaymentsExport( {
				requestExport,
				getExportUrl,
				triggerDownload: jest.fn(),
				maxAttempts: 2,
				pollDelayMs: 0,
			} )
		).resolves.toBe( 'https://example.com/export.csv' );

		expect( getExportUrl ).toHaveBeenCalledTimes( 2 );
	} );

	it( 'continues polling when a download URL check temporarily fails', async () => {
		const requestExport = jest.fn().mockResolvedValue( {
			export_id: 'export_test',
		} );
		const getExportUrl = jest
			.fn()
			.mockRejectedValueOnce( new Error( 'Unavailable' ) )
			.mockResolvedValueOnce( {
				status: 'success',
				download_url: 'https://example.com/export.csv',
			} );
		const triggerDownload = jest.fn();

		await expect(
			runWooPaymentsExport( {
				requestExport,
				getExportUrl,
				triggerDownload,
				maxAttempts: 2,
				pollDelayMs: 0,
			} )
		).resolves.toBe( 'https://example.com/export.csv' );

		expect( getExportUrl ).toHaveBeenCalledTimes( 2 );
		expect( triggerDownload ).toHaveBeenCalledWith(
			'https://example.com/export.csv?force_download=true'
		);
	} );

	it( 'fails loudly when the export response does not include an export id', async () => {
		await expect(
			runWooPaymentsExport( {
				requestExport: jest.fn().mockResolvedValue( {} ),
				getExportUrl: jest.fn(),
				triggerDownload: jest.fn(),
				pollDelayMs: 0,
			} )
		).rejects.toThrow( 'WooPayments export did not return an export ID.' );
	} );

	it( 'fails loudly when the export URL response reports failure', async () => {
		await expect(
			runWooPaymentsExport( {
				requestExport: jest.fn().mockResolvedValue( {
					export_id: 'export_test',
				} ),
				getExportUrl: jest.fn().mockResolvedValue( {
					status: 'failed',
				} ),
				triggerDownload: jest.fn(),
				pollDelayMs: 0,
			} )
		).rejects.toThrow( 'WooPayments export failed.' );
	} );

	it( 'uses a temporary anchor for browser downloads', () => {
		const click = jest
			.spyOn( HTMLAnchorElement.prototype, 'click' )
			.mockImplementation();

		triggerWooPaymentsExportDownload( 'https://example.com/export.csv' );

		expect( click ).toHaveBeenCalledTimes( 1 );
		expect(
			document.querySelector( 'a[href="https://example.com/export.csv"]' )
		).not.toBeInTheDocument();

		click.mockRestore();
	} );
} );
