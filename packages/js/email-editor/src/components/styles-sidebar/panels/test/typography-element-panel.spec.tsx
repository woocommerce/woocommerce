/**
 * External dependencies
 */
import { act, render } from '@testing-library/react';
import { FontSizePicker } from '@wordpress/components';
import {
	useSettings,
	__experimentalFontAppearanceControl as FontAppearanceControl,
} from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import { TypographyElementPanel } from '../typography-element-panel';
import { useEmailStyles } from '../../../../hooks';
import { getElementStyles } from '../../utils';

// Holds the `onChange` the panel passes to FontAppearanceControl, so tests can
// fire an Appearance selection the way the dropdown does.
const fontAppearance: {
	onChange?: ( value: { fontStyle?: string; fontWeight?: string } ) => void;
} = {};

const emailStyles = {
	styles: {},
	defaultStyles: {},
	userStyles: {} as Record< string, unknown >,
	updateStyleProp: jest.fn(),
	updateStyles: jest.fn(),
};

jest.mock( '@wordpress/components', () => ( {
	FontSizePicker: jest.fn( () => null ),
	__experimentalToolsPanel: ( { children } ) => <div>{ children }</div>,
	__experimentalToolsPanelItem: ( { children } ) => <div>{ children }</div>,
} ) );

jest.mock( '@wordpress/block-editor', () => ( {
	useSettings: jest.fn(),
	__experimentalFontAppearanceControl: jest.fn( ( props ) => {
		fontAppearance.onChange = props.onChange;
		return null;
	} ),
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

// Stubbed so the real `use-email-styles` module below can load without
// pulling in `@wordpress/core-data`.
jest.mock( '../../../../hooks/use-user-theme', () => ( {
	useUserTheme: () => ( { userTheme: {}, updateUserTheme: jest.fn() } ),
} ) );

// `setImmutably` stays real: the panel builds the saved styles object with it,
// and a stub would hide whether both Appearance props survive the write.
jest.mock( '../../../../hooks', () => ( {
	setImmutably: jest.requireActual( '../../../../hooks/use-email-styles' )
		.setImmutably,
	useEmailStyles: jest.fn( () => emailStyles ),
} ) );

jest.mock( '../../utils', () => ( {
	getElementStyles: jest.fn( () => ( {
		typography: { fontSize: '16px', fontFamily: 'Inter, sans-serif' },
		color: {},
	} ) ),
	getFontFamilyFaces: jest.requireActual( '../../utils' ).getFontFamilyFaces,
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
const fontAppearanceControlMock = FontAppearanceControl as unknown as jest.Mock;
const useEmailStylesMock = useEmailStyles as jest.Mock;
const getElementStylesMock = getElementStyles as jest.Mock;
const defaultGetElementStyles = getElementStylesMock.getMockImplementation();
const defaultUseEmailStyles = useEmailStylesMock.getMockImplementation();

const mockFontSettings = () =>
	useSettingsMock.mockImplementation( ( ...paths: string[] ) => {
		if ( paths[ 0 ] === 'spacing.units' ) {
			return [ [ 'px' ] ];
		}
		if ( paths[ 0 ] === 'custom.fontWeights' ) {
			return [ { inter: '300, 700' } ];
		}
		return [
			[],
			{
				default: [],
				theme: [ { slug: 'inter', fontFamily: 'Inter, sans-serif' } ],
			},
		];
	} );

const selectAppearance = ( value: {
	fontStyle?: string;
	fontWeight?: string;
} ) => {
	expect( fontAppearance.onChange ).toBeDefined();
	act( () => fontAppearance.onChange( value ) );
};

describe( 'TypographyElementPanel', () => {
	beforeEach( () => {
		fontSizePickerMock.mockClear();
		fontAppearanceControlMock.mockClear();
		getElementStylesMock.mockImplementation( defaultGetElementStyles );
		useEmailStylesMock.mockImplementation( defaultUseEmailStyles );
		emailStyles.updateStyleProp.mockClear();
		emailStyles.updateStyles.mockClear();
		emailStyles.userStyles = {};
		delete fontAppearance.onChange;
		useSettingsMock.mockImplementation( ( ...paths: string[] ) =>
			paths[ 0 ] === 'spacing.units'
				? [ [ 'px' ] ]
				: [ [], { default: [] } ]
		);
	} );

	it( 'passes the spacing units from the email theme to the font size picker', () => {
		render( <TypographyElementPanel element="text" headingLevel="" /> );

		expect( fontSizePickerMock ).toHaveBeenCalled();
		expect( fontSizePickerMock.mock.calls[ 0 ][ 0 ].units ).toEqual( [
			'px',
		] );
	} );

	it( 'stores both Appearance props in a single update, keeping other user styles', () => {
		emailStyles.userStyles = {
			typography: { fontSize: '16px' },
			color: { text: '#111111' },
		};

		render( <TypographyElementPanel element="text" headingLevel="" /> );

		selectAppearance( { fontStyle: 'italic', fontWeight: '700' } );

		// Two separate writes would both read the same stale theme, so the
		// second would overwrite the first and drop `fontStyle`.
		expect( emailStyles.updateStyleProp ).not.toHaveBeenCalled();
		expect( emailStyles.updateStyles ).toHaveBeenCalledTimes( 1 );
		expect( emailStyles.updateStyles ).toHaveBeenCalledWith( {
			typography: {
				fontSize: '16px',
				fontStyle: 'italic',
				fontWeight: '700',
			},
			color: { text: '#111111' },
		} );
	} );

	it( 'stores both Appearance props under the heading level for headings', () => {
		render(
			<TypographyElementPanel element="heading" headingLevel="h2" />
		);

		selectAppearance( { fontStyle: 'italic', fontWeight: '400' } );

		expect( emailStyles.updateStyles ).toHaveBeenCalledTimes( 1 );
		expect( emailStyles.updateStyles ).toHaveBeenCalledWith( {
			elements: {
				h2: {
					typography: { fontStyle: 'italic', fontWeight: '400' },
				},
			},
		} );
	} );

	it( 'clears both Appearance props in a single update when deselected', () => {
		emailStyles.userStyles = {
			typography: {
				fontSize: '16px',
				fontStyle: 'italic',
				fontWeight: '700',
			},
			color: { text: '#111111' },
		};

		render( <TypographyElementPanel element="text" headingLevel="" /> );

		// What the Appearance tool's onDeselect hands back.
		selectAppearance( { fontStyle: undefined, fontWeight: undefined } );

		expect( emailStyles.updateStyleProp ).not.toHaveBeenCalled();
		expect( emailStyles.updateStyles ).toHaveBeenCalledTimes( 1 );
		// toStrictEqual, so a missing key fails rather than matching undefined.
		expect( emailStyles.updateStyles.mock.calls[ 0 ][ 0 ] ).toStrictEqual( {
			typography: {
				fontSize: '16px',
				fontStyle: undefined,
				fontWeight: undefined,
			},
			color: { text: '#111111' },
		} );
	} );

	it( 'stores both Appearance props under the element for other elements', () => {
		render( <TypographyElementPanel element="link" headingLevel="" /> );

		selectAppearance( { fontStyle: 'italic', fontWeight: '700' } );

		expect( emailStyles.updateStyles ).toHaveBeenCalledTimes( 1 );
		expect( emailStyles.updateStyles ).toHaveBeenCalledWith( {
			elements: {
				link: {
					typography: { fontStyle: 'italic', fontWeight: '700' },
				},
			},
		} );
	} );

	it( 'passes the weights the theme lists for the font to the appearance control', () => {
		mockFontSettings();

		render( <TypographyElementPanel element="text" headingLevel="" /> );

		expect(
			fontAppearanceControlMock.mock.calls[ 0 ][ 0 ].fontFamilyFaces
		).toEqual( [
			{ fontStyle: 'normal', fontWeight: '300' },
			{ fontStyle: 'italic', fontWeight: '300' },
			{ fontStyle: 'normal', fontWeight: '700' },
			{ fontStyle: 'italic', fontWeight: '700' },
		] );
	} );

	it( 'uses the inherited font when the element has no font of its own', () => {
		mockFontSettings();
		getElementStylesMock.mockImplementation(
			jest.requireActual( '../../utils' ).getElementStyles
		);
		useEmailStylesMock.mockReturnValue( {
			...emailStyles,
			styles: {
				typography: { fontFamily: 'Inter, sans-serif' },
				elements: { h2: { typography: { fontSize: '28px' } } },
			},
		} );

		render(
			<TypographyElementPanel element="heading" headingLevel="h2" />
		);

		expect(
			fontAppearanceControlMock.mock.calls[ 0 ][ 0 ].fontFamilyFaces
		).toHaveLength( 4 );
	} );

	it( 'prefers a theme font over a default font with the same slug', () => {
		const fontFace = [
			{ fontFamily: 'Inter', fontStyle: 'normal', fontWeight: '600' },
		];
		useSettingsMock.mockImplementation( ( ...paths: string[] ) => {
			if ( paths[ 0 ] === 'custom.fontWeights' ) {
				return [ { inter: '300, 700' } ];
			}
			return [
				[],
				{
					default: [
						{ slug: 'inter', fontFamily: 'Inter, sans-serif' },
					],
					theme: [
						{
							slug: 'inter',
							fontFamily: 'Inter, sans-serif',
							fontFace,
						},
					],
				},
			];
		} );

		render( <TypographyElementPanel element="text" headingLevel="" /> );

		expect(
			fontAppearanceControlMock.mock.calls[ 0 ][ 0 ].fontFamilyFaces
		).toEqual( fontFace );
	} );
} );
