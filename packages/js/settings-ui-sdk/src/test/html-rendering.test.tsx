/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import { act } from 'react';
import { createRoot } from 'react-dom/client';
import type { ReactNode } from 'react';

jest.mock( '@wordpress/admin-ui', () => ( {
	Page: ( { children }: { children: ReactNode } ) => <>{ children }</>,
} ) );

/**
 * Internal dependencies
 */
import { SettingsUIPage } from '../settings-ui-page';
import { NativeSettingsField } from '../native-fields';
import type { SettingsUISchema } from '../types';

globalThis.IS_REACT_ACT_ENVIRONMENT = true;

const unsafeDescription =
	'<strong>Safe</strong><script>alert("x")</script><img src=x onerror=alert(1)><a href="javascript:alert(1)" onclick="alert(1)">Link</a><iframe src="https://example.com"></iframe>';

const renderElement = ( element: JSX.Element ) => {
	const container = document.createElement( 'div' );
	document.body.appendChild( container );
	const root = createRoot( container );

	act( () => {
		root.render( element );
	} );

	return { container, root };
};

const expectUnsafeMarkupRemoved = ( container: HTMLElement ) => {
	expect( container.querySelector( 'strong' )?.textContent ).toBe( 'Safe' );
	expect( container.querySelector( 'script' ) ).toBeNull();
	expect( container.querySelector( 'img' ) ).toBeNull();
	expect( container.querySelector( 'iframe' ) ).toBeNull();
	expect( container.innerHTML ).not.toContain( 'onerror' );
	expect( container.innerHTML ).not.toContain( 'onclick' );
	expect( container.innerHTML ).not.toContain( 'javascript:' );
};

describe( 'settings HTML rendering', () => {
	it( 'sanitizes native field help before rendering', () => {
		const { container, root } = renderElement(
			<NativeSettingsField
				field={ {
					id: 'test_field',
					label: 'Test field',
					type: 'text',
					description: unsafeDescription,
				} }
				value=""
				onChange={ jest.fn() }
				context={ { page: 'test' } }
				values={ {} }
				initialValues={ {} }
				setValue={ jest.fn() }
				setValues={ jest.fn() }
			/>
		);

		expectUnsafeMarkupRemoved( container );

		act( () => root.unmount() );
		container.remove();
	} );

	it( 'hides fields with unmet native visibility rules', () => {
		const schema: SettingsUISchema = {
			id: 'test-page',
			title: 'Test page',
			section: 'default',
			save: { adapter: 'none' },
			groups: {
				general: {
					id: 'general',
					fields: [
						{
							id: 'controller',
							label: 'Controller',
							type: 'checkbox',
							value: false,
						},
						{
							id: 'dependent',
							label: 'Dependent field',
							type: 'text',
							visibility: {
								controller: 'controller',
								value: true,
							},
						},
					],
				},
			},
		};

		const { container, root } = renderElement(
			<SettingsUIPage schema={ schema } />
		);

		expect( container.textContent ).toContain( 'Controller' );
		expect( container.textContent ).not.toContain( 'Dependent field' );

		act( () => root.unmount() );
		container.remove();
	} );

	it( 'sanitizes info fields and group descriptions before rendering', () => {
		const schema: SettingsUISchema = {
			id: 'test-page',
			title: 'Test page',
			section: 'default',
			save: { adapter: 'none' },
			groups: {
				general: {
					id: 'general',
					title: 'General',
					description: unsafeDescription,
					fields: [
						{
							id: 'info_field',
							label: 'Info field',
							type: 'info',
							description: unsafeDescription,
						},
					],
				},
			},
		};

		const { container, root } = renderElement(
			<SettingsUIPage schema={ schema } />
		);

		expect( container.textContent ).toContain( 'Info field' );
		expectUnsafeMarkupRemoved( container );

		act( () => root.unmount() );
		container.remove();
	} );
} );
