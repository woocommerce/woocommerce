import { beforeEach, describe, expect, it, vi, type Mock } from 'vitest';

/**
 * External dependencies
 */
import { render } from '@testing-library/react';
import { FontSizePicker } from '@wordpress/components';
import { useSettings } from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import { TypographyElementPanel } from '../typography-element-panel';
vi.mock( '@wordpress/components', () => {
	const mock = {
		FontSizePicker: vi.fn( () => null ),
		__experimentalToolsPanel: ( { children } ) => <div>{ children }</div>,
		__experimentalToolsPanelItem: ( { children } ) => (
			<div>{ children }</div>
		),
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
vi.mock( '@wordpress/block-editor', () => {
	const mock = {
		useSettings: vi.fn(),
		__experimentalFontAppearanceControl: () => null,
		__experimentalLetterSpacingControl: () => null,
		__experimentalFontFamilyControl: () => null,
		LineHeightControl: () => null,
		__experimentalTextDecorationControl: () => null,
		__experimentalTextTransformControl: () => null,
		__experimentalUseMultipleOriginColorsAndGradients: () => ( {} ),
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
vi.mock( '@wordpress/data', () => {
	const mock = {
		useSelect: vi.fn(),
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
vi.mock( '../../../../store', () => {
	const mock = {
		storeName: 'email-editor/editor',
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
vi.mock( '@wordpress/global-styles-engine', () => {
	const mock = {
		getValueFromVariable: vi.fn(),
		getPresetVariableFromValue: vi.fn(),
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
vi.mock( '../../../../hooks', () => {
	const mock = {
		useEmailStyles: () => ( {
			styles: {},
			defaultStyles: {},
			userStyles: {},
			updateStyleProp: vi.fn(),
			updateStyles: vi.fn(),
		} ),
		setImmutably: vi.fn(),
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
vi.mock( '../../utils', () => {
	const mock = {
		getElementStyles: () => ( {
			typography: {
				fontSize: '16px',
			},
			color: {},
		} ),
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
vi.mock( '../../hooks', () => {
	const mock = {
		useHasTextColorInTypographyPanel: () => false,
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
vi.mock( '../color-dropdown-item', () => {
	const mock = {
		ColorDropdownItem: () => null,
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
vi.mock( '../../../../events', () => {
	const mock = {
		recordEvent: vi.fn(),
		debouncedRecordEvent: vi.fn(),
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
const useSettingsMock = useSettings as Mock;
const fontSizePickerMock = FontSizePicker as unknown as Mock;
describe( 'TypographyElementPanel', () => {
	beforeEach( () => {
		fontSizePickerMock.mockClear();
	} );
	it( 'passes the spacing units from the email theme to the font size picker', () => {
		useSettingsMock.mockImplementation( ( ...paths: string[] ) =>
			paths[ 0 ] === 'spacing.units'
				? [ [ 'px' ] ]
				: [
						[],
						{
							default: [],
						},
				  ]
		);
		render( <TypographyElementPanel element="text" headingLevel="" /> );
		expect( fontSizePickerMock ).toHaveBeenCalled();
		expect( fontSizePickerMock.mock.calls[ 0 ][ 0 ].units ).toEqual( [
			'px',
		] );
	} );
} );
