/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import { act } from 'react';
import { createRoot } from 'react-dom/client';
import type { ReactNode } from 'react';

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

		expect(
			container.querySelector( '.admin-ui-page__header-subtitle' )
				?.textContent
		).toBe( 'Manage your test settings.' );

		act( () => root.unmount() );
	} );

	it( 'renders badges with their intent class', () => {
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
			'.wc-settings-ui-shell__badge'
		);
		expect( badges ).toHaveLength( 2 );
		expect( badges[ 0 ].textContent ).toBe( 'Active' );
		expect(
			badges[ 0 ].classList.contains(
				'wc-settings-ui-shell__badge--success'
			)
		).toBe( true );
		// Defaults to the neutral intent when none is provided.
		expect(
			badges[ 1 ].classList.contains(
				'wc-settings-ui-shell__badge--default'
			)
		).toBe( true );

		act( () => root.unmount() );
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
			container.querySelector( '.wc-settings-ui-shell__badge' )
		).toBeNull();

		act( () => root.unmount() );
	} );
} );
