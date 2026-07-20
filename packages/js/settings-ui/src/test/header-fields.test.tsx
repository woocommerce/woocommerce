/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import { act } from 'react';
import type { ReactNode } from 'react';

// Jest stubs CSS modules, so the real Badge renders nothing that reveals its intent.
jest.mock( '@wordpress/ui', () => ( {
	...jest.requireActual( '@wordpress/ui' ),
	Badge: ( {
		intent,
		children,
	}: {
		intent?: string;
		children: ReactNode;
	} ) => (
		<span data-testid="shell-badge" data-intent={ intent }>
			{ children }
		</span>
	),
} ) );

// Mirror the real admin-ui NavigableRegion, which wraps the shell in a labeled region.
jest.mock( '@wordpress/admin-ui', () => ( {
	NavigableRegion: ( {
		children,
		className,
		ariaLabel,
	}: {
		children: ReactNode;
		className?: string;
		ariaLabel?: string;
	} ) => (
		<div className={ className } role="region" aria-label={ ariaLabel }>
			{ children }
		</div>
	),
} ) );

/**
 * Internal dependencies
 */
import { SettingsUIPage } from '../settings-ui-page';
import { __resetRegistry, registerSettingsExtension } from '../registry';
import type { SettingsRegionComponentProps, SettingsUISchema } from '../types';
import { renderElement } from './helpers/render-element';

globalThis.IS_REACT_ACT_ENVIRONMENT = true;

const baseSchema = (
	shell: SettingsUISchema[ 'shell' ]
): SettingsUISchema => ( {
	id: 'test_page',
	title: 'Test page',
	save: { adapter: 'none' },
	shell,
	groups: {
		main: {
			id: 'main',
			title: 'Main',
			fields: [
				{
					id: 'field_a',
					label: 'Field A',
					type: 'text',
					value: '',
					save: { adapter: 'none' },
				},
			],
		},
	},
} );

describe( 'settings UI shell header fields', () => {
	afterEach( () => {
		__resetRegistry();
		jest.restoreAllMocks();
		// Safety net for failures between render and the inline `container.remove()`.
		document.body.innerHTML = '';
	} );

	it( 'renders the shell subtitle', () => {
		const { container, root } = renderElement(
			<SettingsUIPage
				schema={ baseSchema( {
					header: 'visible',
					title: 'Test page',
					subtitle: 'Manage your test settings.',
				} ) }
				page="test_page"
			/>
		);

		const subtitle = container.querySelector(
			'.wc-settings-ui-shell__subtitle'
		);
		expect( subtitle?.textContent ).toBe( 'Manage your test settings.' );

		act( () => root.unmount() );
		container.remove();
	} );

	it( 'maps schema intents to Badge intents', () => {
		const { container, root } = renderElement(
			<SettingsUIPage
				schema={ baseSchema( {
					header: 'visible',
					title: 'Test page',
					badges: [
						{ label: 'Active', intent: 'success' },
						{ label: 'Beta' },
					],
				} ) }
				page="test_page"
			/>
		);

		const badges = container.querySelectorAll(
			'[data-testid="shell-badge"]'
		);
		expect( badges ).toHaveLength( 2 );
		expect( badges[ 0 ].textContent ).toBe( 'Active' );
		expect( badges[ 0 ].getAttribute( 'data-intent' ) ).toBe( 'stable' );
		// Defaults to the neutral intent when none is provided.
		expect( badges[ 1 ].getAttribute( 'data-intent' ) ).toBe( 'draft' );

		act( () => root.unmount() );
		container.remove();
	} );

	it( 'falls back to the default intent for an unknown intent value', () => {
		const { container, root } = renderElement(
			<SettingsUIPage
				schema={ baseSchema( {
					header: 'visible',
					title: 'Test page',
					// Simulate an extension passing an unrecognized intent string at runtime
					// (TS unions are erased; PHP-supplied schemas can carry arbitrary strings).
					badges: [
						{
							label: 'Mystery',
							intent: 'magic' as never,
						},
					],
				} ) }
				page="test_page"
			/>
		);

		const badge = container.querySelector( '[data-testid="shell-badge"]' );
		expect( badge ).not.toBeNull();
		expect( badge?.getAttribute( 'data-intent' ) ).toBe( 'draft' );

		act( () => root.unmount() );
		container.remove();
	} );

	it( 'falls back to the default intent for Object.prototype key intent values', () => {
		const { container, root } = renderElement(
			<SettingsUIPage
				schema={ baseSchema( {
					header: 'visible',
					title: 'Test page',
					// 'constructor' exists on Object.prototype, so an `in`
					// check would wrongly resolve it to a function.
					badges: [
						{
							label: 'Mystery',
							intent: 'constructor' as never,
						},
					],
				} ) }
				page="test_page"
			/>
		);

		const badge = container.querySelector( '[data-testid="shell-badge"]' );
		expect( badge ).not.toBeNull();
		expect( badge?.getAttribute( 'data-intent' ) ).toBe( 'draft' );

		act( () => root.unmount() );
		container.remove();
	} );

	it( 'renders a legacy region after the default navigation', () => {
		jest.spyOn( console, 'warn' ).mockImplementation( () => undefined );
		const received: SettingsRegionComponentProps[] = [];
		const LegacyNavigation = ( props: SettingsRegionComponentProps ) => {
			received.push( props );
			return <div>Legacy navigation</div>;
		};
		registerSettingsExtension( {
			scope: { page: 'test_page' },
			regions: { 'legacy/navigation': LegacyNavigation },
		} );
		const schema = baseSchema( {
			navigation: [
				{
					id: 'general',
					label: 'General navigation',
					href: '#general',
				},
			],
			navigationComponent: 'legacy/navigation',
		} );
		const { container, cleanup } = renderElement(
			<SettingsUIPage schema={ schema } />
		);
		const navigation = container.querySelector(
			'.wc-settings-ui-shell__navigation'
		);

		expect( navigation?.textContent ).toContain( 'General navigation' );
		expect( navigation?.textContent ).toContain( 'Legacy navigation' );
		expect( received[ 0 ] ).toEqual( {
			values: { field_a: '' },
			initialValues: { field_a: '' },
			context: { page: 'test_page', section: undefined },
			schema,
		} );
		cleanup();
	} );

	it( 'renders the navigation container for a legacy-region-only shell', () => {
		jest.spyOn( console, 'warn' ).mockImplementation( () => undefined );
		registerSettingsExtension( {
			scope: { page: 'test_page' },
			regions: { 'legacy/navigation': () => <div>Legacy only</div> },
		} );
		const { container, cleanup } = renderElement(
			<SettingsUIPage
				schema={ baseSchema( {
					navigationComponent: 'legacy/navigation',
				} ) }
			/>
		);

		expect(
			container.querySelector( '.wc-settings-ui-shell__navigation' )
		).toHaveTextContent( 'Legacy only' );
		cleanup();
	} );

	it( 'omits subtitle and badges when not provided', () => {
		const { container, root } = renderElement(
			<SettingsUIPage
				schema={ baseSchema( {
					header: 'visible',
					title: 'Test page',
				} ) }
				page="test_page"
			/>
		);

		expect(
			container.querySelector( '.wc-settings-ui-shell__subtitle' )
		).toBeNull();
		expect(
			container.querySelector( '[data-testid="shell-badge"]' )
		).toBeNull();

		act( () => root.unmount() );
		container.remove();
	} );
} );
