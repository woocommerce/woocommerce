/**
 * External dependencies
 */
import clsx from 'clsx';
import { __ } from '@wordpress/i18n';
import { useRef } from '@wordpress/element';
import { reset as resetIcon } from '@wordpress/icons';
import {
	Button,
	ColorIndicator,
	Dropdown,
	FlexItem,
	__experimentalDropdownContentWrapper as DropdownContentWrapper, // eslint-disable-line
	__experimentalHStack as HStack, // eslint-disable-line
	__experimentalToolsPanelItem as ToolsPanelItem, // eslint-disable-line
} from '@wordpress/components';
// eslint-disable-next-line
import {
	// @ts-expect-error TS7016: Could not find a declaration file for module '@wordpress/block-editor'.
	__experimentalColorGradientControl as ColorGradientControl, // eslint-disable-line
} from '@wordpress/block-editor';

const popoverProps = {
	placement: 'left-start' as const,
	offset: 36,
	shift: true,
};

/**
 * Color-only port of the dropdown item used by the global styles color panels
 * in the block editor, which is not exposed through the private APIs surface.
 * Reuses the core class names so the block editor stylesheet applies.
 *
 * @see https://github.com/WordPress/gutenberg/blob/trunk/packages/block-editor/src/components/global-styles/color-gradient-dropdown-item.js
 */
export function ColorDropdownItem( {
	label,
	hasValue,
	resetValue,
	isShownByDefault,
	inheritedValue,
	userValue,
	setValue,
	colorGradientControlSettings,
}: {
	label: string;
	hasValue: () => boolean;
	resetValue: () => void;
	isShownByDefault: boolean;
	inheritedValue?: string;
	userValue?: string;
	setValue: ( newValue?: string ) => void;
	colorGradientControlSettings: Record< string, unknown >;
} ): JSX.Element {
	const dropdownButtonRef = useRef< HTMLButtonElement | null >( null );
	return (
		<ToolsPanelItem
			className="block-editor-color-gradient-item block-editor-tools-panel-color-gradient-settings__item"
			hasValue={ hasValue }
			label={ label }
			onDeselect={ resetValue }
			isShownByDefault={ isShownByDefault }
		>
			<Dropdown
				popoverProps={ popoverProps }
				className="block-editor-tools-panel-color-gradient-settings__dropdown"
				renderToggle={ ( { onToggle, isOpen } ) => (
					<>
						<Button
							onClick={ onToggle }
							className={ clsx(
								'block-editor-panel-color-gradient-settings__dropdown',
								{ 'is-open': isOpen }
							) }
							aria-expanded={ isOpen }
							ref={ dropdownButtonRef }
							__next40pxDefaultSize
						>
							<HStack justify="flex-start">
								<ColorIndicator
									className="block-editor-panel-color-gradient-settings__color-indicator"
									colorValue={ inheritedValue }
								/>
								<FlexItem
									className="block-editor-panel-color-gradient-settings__color-name"
									title={ label }
								>
									{ label }
								</FlexItem>
							</HStack>
						</Button>
						{ hasValue() && (
							<Button
								__next40pxDefaultSize
								label={ __( 'Reset', __i18n_text_domain__ ) }
								className="block-editor-panel-color-gradient-settings__reset"
								size="small"
								icon={ resetIcon }
								onClick={ () => {
									resetValue();
									if ( isOpen ) {
										onToggle();
									}
									dropdownButtonRef.current?.focus();
								} }
							/>
						) }
					</>
				) }
				renderContent={ () => (
					<DropdownContentWrapper paddingSize="none">
						<div className="block-editor-panel-color-gradient-settings__dropdown-content">
							<ColorGradientControl
								{ ...colorGradientControlSettings }
								showTitle={ false }
								enableAlpha
								__experimentalIsRenderedInSidebar
								colorValue={ inheritedValue }
								onColorChange={ setValue }
								clearable={ inheritedValue === userValue }
								headingLevel={ 3 }
							/>
						</div>
					</DropdownContentWrapper>
				) }
			/>
		</ToolsPanelItem>
	);
}
