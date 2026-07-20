/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import { act } from 'react';
import type { ReactNode } from 'react';

/**
 * Internal dependencies
 */
import { registerSettingsExtension, __resetRegistry } from '../registry';
import { SettingsUIPage } from '../settings-ui-page';
import type { SettingsFieldComponentProps, SettingsUISchema } from '../types';
import { renderElement } from './helpers/render-element';

jest.mock( '@wordpress/admin-ui', () => ( {
	NavigableRegion: ( {
		children,
		className,
	}: {
		children: ReactNode;
		className?: string;
	} ) => <div className={ className }>{ children }</div>,
} ) );

globalThis.IS_REACT_ACT_ENVIRONMENT = true;

const schema: SettingsUISchema = {
	id: 'test-page',
	section: 'default',
	save: { adapter: 'none' },
	groups: {
		general: {
			id: 'general',
			fields: [
				{
					id: 'legacy',
					label: 'Legacy field',
					type: 'text',
					value: 'initial',
					options: [ { label: 'One', value: 'one' } ],
				},
				{
					id: 'other',
					label: 'Other field',
					type: 'text',
					value: 'other initial',
				},
			],
		},
	},
};

describe( 'legacy settings component contract', () => {
	afterEach( () => {
		__resetRegistry();
		jest.restoreAllMocks();
	} );

	it( 'reconstructs the complete 10.9 component props and setters', () => {
		jest.spyOn( console, 'warn' ).mockImplementation( () => undefined );
		const received: SettingsFieldComponentProps[] = [];
		const LegacyComponent = ( props: SettingsFieldComponentProps ) => {
			received.push( props );
			return <div>Legacy component</div>;
		};
		registerSettingsExtension( {
			scope: { page: 'test-page', section: '' },
			fieldOverrides: { legacy: LegacyComponent },
		} );

		const { cleanup } = renderElement(
			<SettingsUIPage schema={ schema } />
		);
		const initialProps = received[ 0 ];

		expect( Object.keys( initialProps ).sort() ).toEqual( [
			'context',
			'field',
			'initialValues',
			'onChange',
			'setValue',
			'setValues',
			'value',
			'values',
		] );
		expect( initialProps.field ).toEqual(
			expect.objectContaining( {
				id: 'legacy',
				label: 'Legacy field',
				type: 'text',
				options: [ { label: 'One', value: 'one' } ],
			} )
		);
		expect( initialProps.value ).toBe( 'initial' );
		expect( initialProps.values ).toEqual( {
			legacy: 'initial',
			other: 'other initial',
		} );
		expect( initialProps.initialValues ).toEqual( initialProps.values );
		expect( initialProps.context ).toEqual( {
			page: 'test-page',
			section: '',
		} );

		act( () => initialProps.onChange( 'changed' ) );
		expect( received.at( -1 )?.value ).toBe( 'changed' );

		act( () => initialProps.setValue( 'other', 'other changed' ) );
		expect( received.at( -1 )?.values.other ).toBe( 'other changed' );

		act( () =>
			initialProps.setValues( {
				legacy: 'changed again',
				other: 'other changed again',
			} )
		);
		expect( received.at( -1 )?.values ).toEqual( {
			legacy: 'changed again',
			other: 'other changed again',
		} );

		cleanup();
	} );

	it( 'warns once for legacy registrations in the same scope', () => {
		const warn = jest
			.spyOn( console, 'warn' )
			.mockImplementation( () => undefined );
		const LegacyComponent = () => null;

		registerSettingsExtension( {
			scope: { page: 'test-page', section: '' },
			fieldOverrides: { first: LegacyComponent },
		} );
		registerSettingsExtension( {
			scope: { page: 'test-page', section: '' },
			fieldOverrides: { second: LegacyComponent },
		} );

		expect( warn ).toHaveBeenCalledTimes( 1 );
		expect( warn ).toHaveBeenCalledWith(
			expect.stringContaining( 'test-page::default' ),
			expect.any( Object )
		);
		expect( warn ).toHaveBeenCalledWith(
			expect.stringContaining( 'experimental feature flag' ),
			expect.any( Object )
		);
	} );

	it( 'does not emit a legacy warning for modern object registrations', () => {
		const warn = jest
			.spyOn( console, 'warn' )
			.mockImplementation( () => undefined );

		registerSettingsExtension( {
			scope: { page: 'test-page' },
			fieldOverrides: {
				legacy: { component: () => null },
			},
		} );

		expect( warn ).not.toHaveBeenCalled();
	} );
} );
