/**
 * External dependencies
 */
import { fireEvent, render, screen, waitFor } from '@testing-library/react';
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import {
	SUPPORT_REPORT_PATH,
	SupportDownload,
} from '../components/support-download';
import { CheckedCard } from '../components/checked-card';
import type { ReadinessView } from '../types';

jest.mock( '@wordpress/api-fetch', () => jest.fn() );
const mockFetch = apiFetch as unknown as jest.Mock;

describe( 'SupportDownload', () => {
	let click: jest.SpyInstance;

	beforeEach( () => {
		mockFetch.mockReset();
		URL.createObjectURL = jest.fn( () => 'blob:report' );
		URL.revokeObjectURL = jest.fn();
		click = jest
			.spyOn( window.HTMLAnchorElement.prototype, 'click' )
			.mockImplementation( () => undefined );
	} );
	afterEach( () => click.mockRestore() );

	it( 'saves the report from the support route under its file name', async () => {
		mockFetch.mockResolvedValueOnce( {
			filename: 'ai-sales-readiness-example.org-2026-11-18.txt',
			text: 'Site: https://example.org\n',
		} );
		const onError = jest.fn();
		render( <SupportDownload onError={ onError } /> );

		fireEvent.click(
			screen.getByRole( 'button', { name: 'Download your results' } )
		);

		await waitFor( () => expect( click ).toHaveBeenCalledTimes( 1 ) );
		expect( mockFetch ).toHaveBeenCalledWith( {
			path: SUPPORT_REPORT_PATH,
		} );
		const link = click.mock.instances[ 0 ] as unknown as HTMLAnchorElement;
		expect( link.download ).toBe(
			'ai-sales-readiness-example.org-2026-11-18.txt'
		);
		expect( link.isConnected ).toBe( false );
		const blob = ( URL.createObjectURL as jest.Mock ).mock
			.calls[ 0 ][ 0 ] as Blob;
		expect( blob.type ).toBe( 'text/plain;charset=utf-8' );
		expect( blob.size ).toBe( 'Site: https://example.org\n'.length );
		expect( onError ).toHaveBeenLastCalledWith( '' );
	} );

	it( 'reports an error when the report can’t be loaded, and saves nothing', async () => {
		mockFetch.mockRejectedValueOnce( new Error( 'Forbidden' ) );
		const onError = jest.fn();
		render( <SupportDownload onError={ onError } /> );

		fireEvent.click(
			screen.getByRole( 'button', { name: 'Download your results' } )
		);

		await waitFor( () =>
			expect( onError ).toHaveBeenLastCalledWith(
				'The report couldn’t be downloaded. Try again.'
			)
		);
		expect( click ).not.toHaveBeenCalled();
		await waitFor( () =>
			expect(
				screen.getByRole( 'button', { name: 'Download your results' } )
			).toBeEnabled()
		);
	} );
} );

describe( 'CheckedCard', () => {
	const checked = ( intro: string | null ) =>
		( {
			last_checked: 'Last checked today',
			checked_note: null,
			intro,
			support_url: 'https://woocommerce.com/my-account/create-a-ticket/',
			run: {
				label: 'Check again',
				pending: false,
				stalled: false,
				message: '',
				link: null,
			},
		} ) as unknown as ReadinessView;

	const renderCard = ( intro: string | null ) =>
		render(
			<CheckedCard
				view={ checked( intro ) }
				running={ false }
				onRun={ jest.fn() }
				onDownloadError={ jest.fn() }
			/>
		);

	it( 'offers Need help? with the results to download and a link to support', () => {
		renderCard( null );

		expect(
			screen.getByText( /^Need help\?/ ).closest( 'span' )
		).toHaveTextContent(
			/^Need help\? Download your results and contact support/
		);
		expect(
			screen.getByRole( 'button', { name: 'Download your results' } )
		).toBeEnabled();
		expect(
			screen.getByRole( 'link', { name: /contact support/ } )
		).toHaveAttribute(
			'href',
			'https://woocommerce.com/my-account/create-a-ticket/'
		);
	} );

	it( 'shows no Need help? line before the first check, as in the design', () => {
		renderCard( 'Run a check to see what AI assistants get.' );

		expect(
			screen.queryByRole( 'button', { name: 'Download your results' } )
		).toBeNull();
		expect( screen.queryByText( /Need help\?/ ) ).toBeNull();
	} );
} );
