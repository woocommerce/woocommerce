/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import { act } from 'react';
import { createRoot } from 'react-dom/client';
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

// Surface the header props the real admin-ui Page would render so the shell
// header wiring (subtitle, badges) can be asserted.
jest.mock( '@wordpress/admin-ui', () => ( {
	Page: ( {
		title,
		subTitle,
		breadcrumbs,
		badges,
		actions,
		children,
		className,
	}: {
		title?: ReactNode;
		subTitle?: ReactNode;
		breadcrumbs?: ReactNode;
		badges?: ReactNode;
		actions?: ReactNode;
		children: ReactNode;
		className?: string;
	} ) => (
		<div className={ className }>
			<header>
				{ title }
				{ breadcrumbs }
				{ badges }
				{ subTitle && (
					<p className="admin-ui-page__header-subtitle">
						{ subTitle }
					</p>
				) }
				{ actions }
			</header>
			{ children }
		</div>
	),
} ) );

/**
 * Internal dependencies
 */
import { SettingsUIPage } from '../settings-ui-page';
import { __resetRegistry } from '../registry';
import type { SettingsUISchema } from '../types';

globalThis.IS_REACT_ACT_ENVIRONMENT = true;

const renderElement = ( element: JSX.Element ) => {
	const container = document.createElement( 'div' );
	document.body.appendChild( container );
	const root = createRoot( container );

	act( () => {
		root.render( element );
	} );

	return { container, root };
};

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
		// Safety net for failures between render and the inline `container.remove()`.
		document.body.innerHTML = '';
	} );

	it( 'renders the shell subtitle', () => {
		const { container, root } = renderElement(
			<SettingsUIPage
				schema={ baseSchema( {
					title: 'Test page',
					subtitle: 'Manage your test settings.',
				} ) }
				page="test_page"
			/>
		);

		// Anchor on the forwarded text, not the mock's structural class —
		// this would catch a `subtitle` → `subTitle` mapping bug regardless of how the mock renders.
		expect( container.textContent ).toContain(
			'Manage your test settings.'
		);

		act( () => root.unmount() );
		container.remove();
	} );

	it( 'maps schema intents to Badge intents', () => {
		const { container, root } = renderElement(
			<SettingsUIPage
				schema={ baseSchema( {
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

	it( 'omits subtitle and badges when not provided', () => {
		const { container, root } = renderElement(
			<SettingsUIPage
				schema={ baseSchema( { title: 'Test page' } ) }
				page="test_page"
			/>
		);

		expect(
			container.querySelector( '.admin-ui-page__header-subtitle' )
		).toBeNull();
		expect(
			container.querySelector( '[data-testid="shell-badge"]' )
		).toBeNull();

		act( () => root.unmount() );
		container.remove();
	} );
} );
