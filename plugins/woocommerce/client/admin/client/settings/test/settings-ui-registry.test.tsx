/**
 * External dependencies
 */
import type { ErrorInfo } from 'react';
import type { SettingsUISchema } from '@woocommerce/settings-ui';

/**
 * Internal dependencies
 */
import { registerSettingsUIScreens } from '../settings-ui-registry';
import { getAdminSetting } from '~/utils/admin-settings';

const RENDER_FAILED_CLASS = 'woocommerce-settings-ui-render-failed';
const DRILL_DOWN_CLASS = 'woocommerce-settings-ui-drill-down';
const SHELL_HEADER_CLASS = 'wc-settings-ui-shell__header';
const RENDER_WATCHDOG_MS = 4000;

const schema: SettingsUISchema = { id: 'general', groups: {} };

const renderMock = jest.fn();

jest.mock( '@wordpress/element', () => ( {
	...jest.requireActual( '@wordpress/element' ),
	createRoot: jest.fn( () => ( { render: renderMock } ) ),
} ) );

jest.mock( '~/utils/admin-settings', () => ( {
	getAdminSetting: jest.fn(),
} ) );

const mockGetAdminSetting = getAdminSetting as jest.Mock;

const setDom = () => {
	document.body.className = DRILL_DOWN_CLASS;
	document.body.innerHTML =
		'<div data-wc-settings-ui="1" data-wc-settings-page="general" data-wc-settings-section=""></div>';
};

const appendShellHeader = () => {
	const header = document.createElement( 'div' );
	header.className = SHELL_HEADER_CLASS;
	document.body.appendChild( header );
};

const setValidSettingsUIGlobal = () => {
	window.wc = {
		settingsUi: {
			SettingsUIErrorBoundary: ( () => null ) as never,
			SettingsUIPage: () => null,
		},
	};
};

describe( 'registerSettingsUIScreens', () => {
	beforeEach( () => {
		jest.useRealTimers();
		renderMock.mockReset();
		mockGetAdminSetting.mockReset();
		mockGetAdminSetting.mockReturnValue( { general: { default: schema } } );
		document.body.className = '';
		document.body.innerHTML = '';
		delete ( window as { wc?: unknown } ).wc;
		jest.spyOn( console, 'warn' ).mockImplementation( () => {} );
	} );

	afterEach( () => {
		jest.useRealTimers();
		jest.restoreAllMocks();
	} );

	it( 'marks render failed and warns when the wc-settings-ui script global is missing', () => {
		setDom();

		registerSettingsUIScreens();

		expect( document.body.classList.contains( RENDER_FAILED_CLASS ) ).toBe(
			true
		);
		// eslint-disable-next-line no-console
		expect( console.warn ).toHaveBeenCalled();
	} );

	it( 'marks render failed and warns when the settings schema is missing', () => {
		setDom();
		setValidSettingsUIGlobal();
		mockGetAdminSetting.mockReturnValue( {} );

		registerSettingsUIScreens();

		expect( document.body.classList.contains( RENDER_FAILED_CLASS ) ).toBe(
			true
		);
		// eslint-disable-next-line no-console
		expect( console.warn ).toHaveBeenCalled();
	} );

	it( 'marks render failed and warns when mounting throws synchronously', () => {
		setDom();
		setValidSettingsUIGlobal();
		renderMock.mockImplementation( () => {
			throw new Error( 'boom' );
		} );

		registerSettingsUIScreens();

		expect( document.body.classList.contains( RENDER_FAILED_CLASS ) ).toBe(
			true
		);
		// eslint-disable-next-line no-console
		expect( console.warn ).toHaveBeenCalled();
	} );

	it( 'marks render failed when the error boundary reports a render error', () => {
		setDom();
		setValidSettingsUIGlobal();

		registerSettingsUIScreens();
		const element = renderMock.mock.calls[ 0 ][ 0 ];
		element.props.onError( new Error( 'render failed' ), {
			componentStack: '',
		} as ErrorInfo );

		expect( document.body.classList.contains( RENDER_FAILED_CLASS ) ).toBe(
			true
		);
	} );

	it( 'does not mark render failed once the shell header mounts', () => {
		setDom();
		setValidSettingsUIGlobal();

		registerSettingsUIScreens();
		appendShellHeader();

		expect( document.body.classList.contains( RENDER_FAILED_CLASS ) ).toBe(
			false
		);
	} );

	describe( 'render watchdog', () => {
		beforeEach( () => {
			jest.useFakeTimers();
		} );

		it( 'marks render failed if the shell header never appears', () => {
			setDom();
			setValidSettingsUIGlobal();

			registerSettingsUIScreens();
			jest.advanceTimersByTime( RENDER_WATCHDOG_MS );

			expect(
				document.body.classList.contains( RENDER_FAILED_CLASS )
			).toBe( true );
			// eslint-disable-next-line no-console
			expect( console.warn ).toHaveBeenCalled();
		} );

		it( 'does not mark render failed if the shell header appears before the timeout', () => {
			setDom();
			setValidSettingsUIGlobal();

			registerSettingsUIScreens();
			appendShellHeader();
			jest.advanceTimersByTime( RENDER_WATCHDOG_MS );

			expect(
				document.body.classList.contains( RENDER_FAILED_CLASS )
			).toBe( false );
		} );

		it( 'clears the render-failed class once the shell header appears after a slow-but-successful mount past the watchdog timeout', async () => {
			setDom();
			setValidSettingsUIGlobal();

			registerSettingsUIScreens();
			jest.advanceTimersByTime( RENDER_WATCHDOG_MS );
			expect(
				document.body.classList.contains( RENDER_FAILED_CLASS )
			).toBe( true );

			appendShellHeader();

			// The MutationObserver callback fires as a microtask. Jest's
			// modern fake timers fake `queueMicrotask` too, so flush it
			// through the fake-timer clock rather than a real Promise tick.
			await jest.advanceTimersByTimeAsync( 0 );

			expect(
				document.body.classList.contains( RENDER_FAILED_CLASS )
			).toBe( false );
		} );
	} );
} );
