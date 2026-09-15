/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useCallback } from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import {
	getValueFromVariable,
	getPresetVariableFromValue,
} from '@wordpress/global-styles-engine';
import {
	FontSizePicker,
	__experimentalToolsPanel as ToolsPanel, // eslint-disable-line
	__experimentalToolsPanelItem as ToolsPanelItem, // eslint-disable-line
} from '@wordpress/components';
// eslint-disable-next-line
import {
	useSettings,
	// We can remove the ts-expect-error comments once the types are available.
	// @see packages/block-editor/src/components/index.js
	// @ts-expect-error TS7016: Could not find a declaration file for module '@wordpress/block-editor'.
	__experimentalFontAppearanceControl as FontAppearanceControl, // eslint-disable-line
	// @ts-expect-error TS7016: Could not find a declaration file for module '@wordpress/block-editor'.
	__experimentalLetterSpacingControl as LetterSpacingControl, // eslint-disable-line
	// @ts-expect-error TS7016: Could not find a declaration file for module '@wordpress/block-editor'.
	__experimentalFontFamilyControl as FontFamilyControl, // eslint-disable-line
	// @ts-expect-error TS7016: Could not find a declaration file for module '@wordpress/block-editor'.
	LineHeightControl,
	// @ts-expect-error TS7016: Could not find a declaration file for module '@wordpress/block-editor'.
	__experimentalTextDecorationControl as TextDecorationControl, // eslint-disable-line
	// @ts-expect-error TS7016: Could not find a declaration file for module '@wordpress/block-editor'.
	__experimentalTextTransformControl as TextTransformControl, // eslint-disable-line
	// @ts-expect-error TS7016: Could not find a declaration file for module '@wordpress/block-editor'.
	__experimentalUseMultipleOriginColorsAndGradients as useMultipleOriginColorsAndGradients, // eslint-disable-line
} from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import { useEmailStyles, setImmutably } from '../../../hooks';
import { storeName } from '../../../store';
import { getElementStyles } from '../utils';
import { useHasTextColorInTypographyPanel } from '../hooks';
import { ColorDropdownItem } from './color-dropdown-item';
import { recordEvent, debouncedRecordEvent } from '../../../events';

export const DEFAULT_CONTROLS = {
	textColor: true,
	fontFamily: true,
	fontSize: true,
	fontAppearance: true,
	lineHeight: true,
	letterSpacing: false,
	textTransform: false,
	textDecoration: false,
	writingMode: true,
	textColumns: true,
};

export function TypographyElementPanel( {
	element,
	headingLevel,
	defaultControls = DEFAULT_CONTROLS,
}: {
	element: string;
	headingLevel: string;
	defaultControls?: typeof DEFAULT_CONTROLS;
} ) {
	const [ fontSizes, blockLevelFontFamilies ] = useSettings(
		'typography.fontSizes',
		'typography.fontFamilies'
	);

	// Ref: https://github.com/WordPress/gutenberg/issues/59778
	const fontFamilies = blockLevelFontFamilies?.default || [];
	const theme = useSelect( ( select ) => select( storeName ).getTheme(), [] );
	const colorGradientSettings = useMultipleOriginColorsAndGradients();
	// Text color renders here only when the running WordPress no longer offers
	// it in the Colors screen (WordPress 7.1 moved it to the typography panel).
	const showTextColor =
		useHasTextColorInTypographyPanel() && element === 'text';
	const { styles, defaultStyles, userStyles, updateStyleProp, updateStyles } =
		useEmailStyles();
	const elementStyles = getElementStyles( styles, element, headingLevel );
	const defaultElementStyles = getElementStyles(
		defaultStyles,
		element,
		headingLevel
	);
	const {
		fontFamily,
		fontSize,
		fontStyle,
		fontWeight,
		lineHeight,
		letterSpacing,
		textDecoration,
		textTransform,
	} = elementStyles.typography;

	const {
		fontFamily: defaultFontFamily,
		fontSize: defaultFontSize,
		fontStyle: defaultFontStyle,
		fontWeight: defaultFontWeight,
		lineHeight: defaultLineHeight,
		letterSpacing: defaultLetterSpacing,
		textDecoration: defaultTextDecoration,
		textTransform: defaultTextTransform,
	} = defaultElementStyles.typography;

	// The text color renders only for the `text` element, whose styles live at
	// the root of the styles object.
	const userTextColor = userStyles?.color?.text;
	const defaultTextColor = defaultElementStyles.color?.text;
	const decodedTextColor = getValueFromVariable(
		{ settings: theme?.settings },
		'',
		elementStyles.color?.text
	);
	const decodedUserTextColor = userTextColor
		? getValueFromVariable(
				{ settings: theme?.settings },
				'',
				userTextColor
		  )
		: undefined;

	const hasTextColor = () => !! userTextColor;
	const hasFontFamily = () => fontFamily !== defaultFontFamily;
	const hasFontSize = () => fontSize !== defaultFontSize;
	const hasFontAppearance = () =>
		fontWeight !== defaultFontWeight || fontStyle !== defaultFontStyle;
	const hasLineHeight = () => lineHeight !== defaultLineHeight;
	const hasLetterSpacing = () => letterSpacing !== defaultLetterSpacing;
	const hasTextDecoration = () => textDecoration !== defaultTextDecoration;
	const hasTextTransform = () => textTransform !== defaultTextTransform;
	const showToolFontSize =
		element !== 'heading' || headingLevel !== 'heading';

	const updateElementStyleProp = useCallback(
		( path, newValue ) => {
			if ( element === 'heading' ) {
				updateStyleProp(
					[ 'elements', headingLevel, ...path ],
					newValue
				);
			} else if ( element === 'text' ) {
				updateStyleProp( [ ...path ], newValue );
			} else {
				updateStyleProp( [ 'elements', element, ...path ], newValue );
			}
		},
		[ element, updateStyleProp, headingLevel ]
	);

	const setTextColor = ( newValue ) => {
		// Store palette colors as preset references so later palette changes
		// propagate, matching how the core ColorPanel writes them.
		const encodedValue =
			newValue === undefined
				? undefined
				: getPresetVariableFromValue(
						theme?.settings,
						undefined,
						'color.text',
						newValue
				  );
		updateElementStyleProp( [ 'color', 'text' ], encodedValue );
		debouncedRecordEvent(
			'styles_sidebar_screen_typography_element_panel_set_text_color',
			{
				element,
				newValue,
				selectedDefaultTextColor: encodedValue === defaultTextColor,
			}
		);
	};

	const setLetterSpacing = ( newValue ) => {
		updateElementStyleProp( [ 'typography', 'letterSpacing' ], newValue );
		debouncedRecordEvent(
			'styles_sidebar_screen_typography_element_panel_set_letter_spacing',
			{
				element,
				newValue,
				selectedDefaultLetterSpacing: newValue === defaultLetterSpacing,
			}
		);
	};

	const setLineHeight = ( newValue ) => {
		updateElementStyleProp( [ 'typography', 'lineHeight' ], newValue );
		debouncedRecordEvent(
			'styles_sidebar_screen_typography_element_panel_set_line_height',
			{
				element,
				newValue,
				selectedDefaultLineHeight: newValue === defaultLineHeight,
			}
		);
	};

	const setFontSize = ( newValue ) => {
		updateElementStyleProp( [ 'typography', 'fontSize' ], newValue );
		debouncedRecordEvent(
			'styles_sidebar_screen_typography_element_panel_set_font_size',
			{
				element,
				headingLevel,
				newValue,
				selectedDefaultFontSize: newValue === defaultFontSize,
			}
		);
	};

	const setFontFamily = ( newValue ) => {
		updateElementStyleProp( [ 'typography', 'fontFamily' ], newValue );
		debouncedRecordEvent(
			'styles_sidebar_screen_typography_element_panel_set_font_family',
			{
				element,
				newValue,
				selectedDefaultFontFamily: newValue === defaultFontFamily,
			}
		);
	};

	const setTextDecoration = ( newValue ) => {
		updateElementStyleProp( [ 'typography', 'textDecoration' ], newValue );
		debouncedRecordEvent(
			'styles_sidebar_screen_typography_element_panel_set_text_decoration',
			{
				element,
				newValue,
				selectedDefaultTextDecoration:
					newValue === defaultTextDecoration,
			}
		);
	};

	const setTextTransform = ( newValue ) => {
		updateElementStyleProp( [ 'typography', 'textTransform' ], newValue );
		debouncedRecordEvent(
			'styles_sidebar_screen_typography_element_panel_set_text_transform',
			{
				element,
				newValue,
				selectedDefaultTextTransform: newValue === defaultTextTransform,
			}
		);
	};

	const setFontAppearance = ( {
		fontStyle: newFontStyle,
		fontWeight: newFontWeight,
	} ) => {
		updateElementStyleProp( [ 'typography', 'fontStyle' ], newFontStyle );
		updateElementStyleProp( [ 'typography', 'fontWeight' ], newFontWeight );
		debouncedRecordEvent(
			'styles_sidebar_screen_typography_element_panel_set_font_appearance',
			{
				element,
				newFontStyle,
				newFontWeight,
				selectedDefaultFontStyle: newFontStyle === defaultFontStyle,
				selectedDefaultFontWeight: newFontWeight === defaultFontWeight,
			}
		);
	};

	const resetAll = () => {
		if ( showTextColor ) {
			// Single update — a second updateStyleProp call in the same
			// handler would work from a stale userTheme and clobber this edit.
			updateStyles(
				setImmutably(
					setImmutably(
						userStyles ?? {},
						[ 'color', 'text' ],
						undefined
					),
					[ 'typography' ],
					{}
				)
			);
		} else {
			updateElementStyleProp( [ 'typography' ], {} );
		}
		recordEvent(
			'styles_sidebar_screen_typography_element_panel_reset_all_styles_selected',
			{
				element,
				headingLevel,
			}
		);
	};

	return (
		<ToolsPanel
			label={ __( 'Typography', __i18n_text_domain__ ) }
			resetAll={ resetAll }
		>
			{ showTextColor && (
				<ColorDropdownItem
					label={ __( 'Color', __i18n_text_domain__ ) }
					hasValue={ hasTextColor }
					resetValue={ () => setTextColor( undefined ) }
					isShownByDefault={ defaultControls.textColor }
					inheritedValue={ decodedTextColor }
					userValue={ decodedUserTextColor }
					setValue={ setTextColor }
					colorGradientControlSettings={ colorGradientSettings }
				/>
			) }
			<ToolsPanelItem
				label={ __( 'Font family', __i18n_text_domain__ ) }
				hasValue={ hasFontFamily }
				onDeselect={ () => setFontFamily( undefined ) }
				isShownByDefault={ defaultControls.fontFamily }
			>
				<FontFamilyControl
					value={ fontFamily }
					onChange={ setFontFamily }
					size="__unstable-large"
					fontFamilies={ fontFamilies }
					__nextHasNoMarginBottom
				/>
			</ToolsPanelItem>
			{ showToolFontSize && (
				<ToolsPanelItem
					label={ __( 'Font size', __i18n_text_domain__ ) }
					hasValue={ hasFontSize }
					onDeselect={ () => setFontSize( undefined ) }
					isShownByDefault={ defaultControls.fontSize }
				>
					<FontSizePicker
						value={ fontSize }
						onChange={ setFontSize }
						fontSizes={ fontSizes }
						disableCustomFontSizes={ false }
						withReset={ false }
						withSlider
						size="__unstable-large"
						__nextHasNoMarginBottom
					/>
				</ToolsPanelItem>
			) }
			<ToolsPanelItem
				className="single-column"
				label={ __( 'Appearance', __i18n_text_domain__ ) }
				hasValue={ hasFontAppearance }
				onDeselect={ () => {
					setFontAppearance( {
						fontStyle: undefined,
						fontWeight: undefined,
					} );
				} }
				isShownByDefault={ defaultControls.fontAppearance }
			>
				<FontAppearanceControl
					value={ {
						fontStyle,
						fontWeight,
					} }
					onChange={ setFontAppearance }
					hasFontStyles
					hasFontWeights
					size="__unstable-large"
				/>
			</ToolsPanelItem>
			<ToolsPanelItem
				className="single-column"
				label={ __( 'Line height', __i18n_text_domain__ ) }
				hasValue={ hasLineHeight }
				onDeselect={ () => setLineHeight( undefined ) }
				isShownByDefault={ defaultControls.lineHeight }
			>
				<LineHeightControl
					__nextHasNoMarginBottom
					__unstableInputWidth="auto"
					value={ lineHeight }
					onChange={ setLineHeight }
					size="__unstable-large"
				/>
			</ToolsPanelItem>
			<ToolsPanelItem
				className="single-column"
				label={ __( 'Letter spacing', __i18n_text_domain__ ) }
				hasValue={ hasLetterSpacing }
				onDeselect={ () => setLetterSpacing( undefined ) }
				isShownByDefault={ defaultControls.letterSpacing }
			>
				<LetterSpacingControl
					value={ letterSpacing }
					onChange={ setLetterSpacing }
					size="__unstable-large"
					__unstableInputWidth="auto"
				/>
			</ToolsPanelItem>
			<ToolsPanelItem
				className="single-column"
				label={ __( 'Text decoration', __i18n_text_domain__ ) }
				hasValue={ hasTextDecoration }
				onDeselect={ () => setTextDecoration( undefined ) }
				isShownByDefault={ defaultControls.textDecoration }
			>
				<TextDecorationControl
					value={ textDecoration }
					onChange={ setTextDecoration }
					size="__unstable-large"
					__unstableInputWidth="auto"
				/>
			</ToolsPanelItem>
			<ToolsPanelItem
				label={ __( 'Letter case', __i18n_text_domain__ ) }
				hasValue={ hasTextTransform }
				onDeselect={ () => setTextTransform( defaultTextTransform ) }
				isShownByDefault={ defaultControls.textTransform }
			>
				<TextTransformControl
					value={ textTransform }
					onChange={ setTextTransform }
					showNone
					isBlock
					size="__unstable-large"
					__nextHasNoMarginBottom
				/>
			</ToolsPanelItem>
		</ToolsPanel>
	);
}

export default TypographyElementPanel;
