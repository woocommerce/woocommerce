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

jest.mock( '@wordpress/components', () => ( {
	FontSizePicker: jest.fn( () => null ),
	__experimentalToolsPanel: ( { children } ) => <div>{ children }</div>,
	__experimentalToolsPanelItem: ( { children } ) => <div>{ children }</div>,
} ) );

jest.mock( '@wordpress/block-editor', () => ( {
	useSettings: jest.fn(),
	__experimentalFontAppearanceControl: () => null,
	__experimentalLetterSpacingControl: () => null,
	__experimentalFontFamilyControl: () => null,
	LineHeightControl: () => null,
	__experimentalTextDecorationControl: () => null,
	__experimentalTextTransformControl: () => null,
	__experimentalUseMultipleOriginColorsAndGradients: () => ( {} ),
} ) );

jest.mock( '@wordpress/data', () => ( {
	useSelect: jest.fn(),
} ) );

jest.mock( '../../../../store', () => ( {
	storeName: 'email-editor/editor',
} ) );

jest.mock( '@wordpress/global-styles-engine', () => ( {
	getValueFromVariable: jest.fn(),
	getPresetVariableFromValue: jest.fn(),
} ) );

jest.mock( '../../../../hooks', () => ( {
	useEmailStyles: () => ( {
		styles: {},
		defaultStyles: {},
		userStyles: {},
		updateStyleProp: jest.fn(),
		updateStyles: jest.fn(),
	} ),
	setImmutably: jest.fn(),
} ) );

jest.mock( '../../utils', () => ( {
	getElementStyles: () => ( { typography: { fontSize: '16px' }, color: {} } ),
} ) );

jest.mock( '../../hooks', () => ( {
	useHasTextColorInTypographyPanel: () => false,
} ) );

jest.mock( '../color-dropdown-item', () => ( {
	ColorDropdownItem: () => null,
} ) );

jest.mock( '../../../../events', () => ( {
	recordEvent: jest.fn(),
	debouncedRecordEvent: jest.fn(),
} ) );

const useSettingsMock = useSettings as jest.Mock;
const fontSizePickerMock = FontSizePicker as unknown as jest.Mock;

describe( 'TypographyElementPanel', () => {
	beforeEach( () => {
		fontSizePickerMock.mockClear();
	} );

	it( 'passes the spacing units from the email theme to the font size picker', () => {
		useSettingsMock.mockImplementation( ( ...paths: string[] ) =>
			paths[ 0 ] === 'spacing.units'
				? [ [ 'px' ] ]
				: [ [], { default: [] } ]
		);

		render( <TypographyElementPanel element="text" headingLevel="" /> );

		expect( fontSizePickerMock ).toHaveBeenCalled();
		expect( fontSizePickerMock.mock.calls[ 0 ][ 0 ].units ).toEqual( [
			'px',
		] );
	} );
} );
