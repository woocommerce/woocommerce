/**
 * External dependencies
 */
import clsx from 'clsx';
import { __ } from '@wordpress/i18n';
import {
	useBlockProps,
	RichText,
	InspectorControls,
	/* eslint-disable */
	/* @ts-expect-error HeadingLevelDropdown is not typed in @wordpress/block-editor */
	HeadingLevelDropdown,
	/* @ts-ignore module is exported as experimental */
	__experimentalUseBorderProps as useBorderProps,
	/* @ts-ignore module is exported as experimental */
	__experimentalUseColorProps as useColorProps,
	/* @ts-ignore module is exported as experimental */
	__experimentalGetSpacingClassesAndStyles as useSpacingProps,
	/* @ts-ignore module is exported as experimental */
	__experimentalGetShadowClassesAndStyles as useShadowProps,
	BlockControls,
	/* @ts-ignore module is exported as experimental */
} from '@wordpress/block-editor';
import {
	PanelBody,
	ToolbarGroup,
	/* @ts-ignore module is exported as experimental */
	__experimentalToggleGroupControl as ToggleGroupControl,
	/* @ts-ignore module is exported as experimental */
	__experimentalToggleGroupControlOption as ToggleGroupControlOption,
	/* @ts-ignore module is exported as experimental */
	__experimentalToggleGroupControlOptionIcon as ToggleGroupControlOptionIcon,
	/* eslint-enable */
} from '@wordpress/components';
/**
 * Internal dependencies
 */
import {
	caret,
	chevron,
	chevronRight,
	circlePlus,
	plus,
} from '../accordion-item/icons';

const ICONS = {
	plus,
	circlePlus,
	chevron,
	chevronRight,
	caret,
};

export default function Edit( { attributes, setAttributes } ) {
	const { level, title, textAlign, icon, iconPosition, levelOptions } =
		attributes;
	const TagName = 'h' + level;

	const blockProps = useBlockProps();
	const borderProps = useBorderProps( attributes );
	const colorProps = useColorProps( attributes );
	const spacingProps = useSpacingProps( attributes );
	const shadowProps = useShadowProps( attributes );

	const Icon = ICONS[ icon ];

	return (
		<>
			<BlockControls>
				<ToolbarGroup>
					<HeadingLevelDropdown
						value={ level }
						options={ levelOptions }
						onChange={ ( newLevel ) =>
							setAttributes( { level: newLevel } )
						}
					/>
				</ToolbarGroup>
			</BlockControls>
			<InspectorControls key="setting">
				<PanelBody title={ __( 'Settings', 'woocommerce' ) }>
					<ToggleGroupControl
						__nextHasNoMarginBottom
						__next40pxDefaultSize
						isBlock
						label={ __( 'Icon', 'woocommerce' ) }
						value={ icon }
						onChange={ ( value ) =>
							setAttributes( { icon: value } )
						}
					>
						<ToggleGroupControlOptionIcon
							label="Plus"
							icon={ plus }
							value="plus"
						/>
						<ToggleGroupControlOptionIcon
							label="Chevron"
							icon={ chevron }
							value="chevron"
						/>
						<ToggleGroupControlOptionIcon
							label="Circle Plus"
							icon={ circlePlus }
							value="circlePlus"
						/>
						<ToggleGroupControlOptionIcon
							label="Caret"
							icon={ caret }
							value="caret"
						/>
						<ToggleGroupControlOptionIcon
							label="Chevron Right"
							icon={ chevronRight }
							value="chevronRight"
						/>
						<ToggleGroupControlOption
							label="None"
							value={ false }
						/>
					</ToggleGroupControl>
					<ToggleGroupControl
						__nextHasNoMarginBottom
						__next40pxDefaultSize
						isBlock
						label={ __( 'Icon Position', 'woocommerce' ) }
						value={ iconPosition }
						onChange={ ( value ) => {
							setAttributes( { iconPosition: value } );
						} }
					>
						<ToggleGroupControlOption label="Left" value="left" />
						<ToggleGroupControlOption label="Right" value="right" />
					</ToggleGroupControl>
				</PanelBody>
			</InspectorControls>
			<TagName
				{ ...blockProps }
				className={ clsx(
					blockProps.className,
					colorProps.className,
					borderProps.className,
					'accordion-item__heading',
					{
						[ `has-custom-font-size` ]: blockProps.style.fontSize,
						[ `icon-position-left` ]: iconPosition === 'left',
						[ `has-text-align-${ textAlign }` ]: textAlign,
					}
				) }
				style={ {
					...borderProps.style,
					...colorProps.style,
					...shadowProps.style,
				} }
			>
				<button
					className={ clsx( 'accordion-item__toggle' ) }
					style={ {
						...spacingProps.style,
					} }
				>
					<RichText
						allowedFormats={ [
							'core/bold',
							'core/italic',
							'core/image',
							'core/strikethrough',
						] }
						disableLineBreaks
						tagName="span"
						value={ title }
						onChange={ ( newTitle ) =>
							setAttributes( { title: newTitle } )
						}
						placeholder={ __( 'Accordion title', 'woocommerce' ) }
					/>
					<span
						className={ clsx( `accordion-item__toggle-icon`, {
							[ `has-icon-${ icon }` ]: icon,
						} ) }
						style={ {
							// TO-DO: make this configurable
							width: `1.2em`,
							height: `1.2em`,
						} }
					>
						{ Icon && <Icon width="1.2em" height="1.2em" /> }
					</span>
				</button>
			</TagName>
		</>
	);
}
