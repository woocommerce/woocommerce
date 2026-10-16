/**
 * External dependencies
 */
import { fireEvent, render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import { ResetStylesControl } from '../settings-email-color-palette-control';
import type { DefaultColors } from '../settings-email-color-palette-slotfill';

type JQueryAdapter = ( selector: string ) => {
	length: number;
	on: ( eventName: string, listener: () => void ) => void;
	off: ( eventName: string, listener: () => void ) => void;
};

const jQueryAdapter: JQueryAdapter = ( selector ) => {
	const elements = Array.from( document.querySelectorAll( selector ) );

	return {
		length: elements.length,
		on: ( eventName, listener ) => {
			elements.forEach( ( element ) =>
				element.addEventListener( eventName, listener )
			);
		},
		off: ( eventName, listener ) => {
			elements.forEach( ( element ) =>
				element.removeEventListener( eventName, listener )
			);
		},
	};
};

const colorFields = [
	{
		id: 'woocommerce_email_base_color',
		label: 'Accent',
		key: 'baseColor',
	},
	{
		id: 'woocommerce_email_background_color',
		label: 'Email background',
		key: 'bgColor',
	},
	{
		id: 'woocommerce_email_body_background_color',
		label: 'Content background',
		key: 'bodyBgColor',
	},
	{
		id: 'woocommerce_email_text_color',
		label: 'Heading and text',
		key: 'bodyTextColor',
	},
	{
		id: 'woocommerce_email_footer_text_color',
		label: 'Secondary text',
		key: 'footerTextColor',
	},
] as const;

const initialColors: DefaultColors = {
	baseColor: '#111111',
	bgColor: '#222222',
	bodyBgColor: '#333333',
	bodyTextColor: '#444444',
	footerTextColor: '#555555',
};

const themeColors: DefaultColors = {
	baseColor: '#a10000',
	bgColor: '#b20000',
	bodyBgColor: '#c30000',
	bodyTextColor: '#d40000',
	footerTextColor: '#e50000',
};

const changeAccent = () => {
	fireEvent.change( screen.getByLabelText( 'Accent' ), {
		target: { value: '#abcdef' },
	} );
};

describe( 'ResetStylesControl', () => {
	let settingsFixture = document.createElement( 'div' );
	let autoSyncInput = document.createElement( 'input' );
	let unmount: undefined | ( () => void );

	const appendColorInputs = ( colors: DefaultColors ) => {
		for ( const field of colorFields ) {
			const label = document.createElement( 'label' );
			const input = document.createElement( 'input' );

			label.htmlFor = field.id;
			label.textContent = field.label;
			input.id = field.id;
			input.value = colors[ field.key ];
			settingsFixture?.append( label, input );
		}
	};

	const expectColors = ( colors: DefaultColors ) => {
		for ( const field of colorFields ) {
			expect( screen.getByLabelText( field.label ) ).toHaveValue(
				colors[ field.key ]
			);
		}
	};

	const renderWithThemeDefaults = () => {
		const renderResult = render(
			<ResetStylesControl
				defaultColors={ initialColors }
				hasThemeJson
				autoSync
				autoSyncInput={ autoSyncInput }
			/>
		);
		unmount = renderResult.unmount;

		renderResult.rerender(
			<ResetStylesControl
				defaultColors={ themeColors }
				hasThemeJson
				autoSync
				autoSyncInput={ autoSyncInput }
			/>
		);
	};

	beforeEach( () => {
		unmount = undefined;
		settingsFixture = document.createElement( 'div' );
		settingsFixture.setAttribute( 'aria-label', 'Email color settings' );
		document.body.appendChild( settingsFixture );
		appendColorInputs( initialColors );

		autoSyncInput = document.createElement( 'input' );
		autoSyncInput.type = 'hidden';
		autoSyncInput.id = 'woocommerce_email_auto_sync_with_theme';
		autoSyncInput.value = 'yes';
		settingsFixture.appendChild( autoSyncInput );

		Object.defineProperty( globalThis, 'jQuery', {
			configurable: true,
			value: jQueryAdapter,
		} );
	} );

	afterEach( () => {
		unmount?.();
		settingsFixture.remove();
		delete ( globalThis as typeof globalThis & { jQuery?: JQueryAdapter } )
			.jQuery;
	} );

	it( 'shows sync and undo controls after a color change', () => {
		renderWithThemeDefaults();
		changeAccent();

		expect( autoSyncInput ).toHaveValue( 'no' );
		expect(
			screen.getByRole( 'button', { name: 'Sync with theme' } )
		).toBeVisible();
		expect(
			screen.getByRole( 'button', { name: 'Undo changes' } )
		).toBeVisible();
	} );

	it( 'syncs theme defaults and re-enables auto-sync', async () => {
		renderWithThemeDefaults();
		changeAccent();

		await userEvent.click(
			screen.getByRole( 'button', { name: 'Sync with theme' } )
		);

		expectColors( themeColors );
		expect( autoSyncInput ).toHaveValue( 'yes' );
	} );

	it( 'restores the initial colors and auto-sync setting with Undo', async () => {
		renderWithThemeDefaults();
		changeAccent();

		await userEvent.click(
			screen.getByRole( 'button', { name: 'Undo changes' } )
		);

		expectColors( initialColors );
		expect( autoSyncInput ).toHaveValue( 'yes' );
		expect(
			screen.queryByRole( 'button', { name: 'Undo changes' } )
		).not.toBeInTheDocument();
	} );
} );
