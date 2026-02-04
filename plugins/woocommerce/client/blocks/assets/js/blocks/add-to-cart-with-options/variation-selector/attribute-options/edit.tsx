/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { type BlockEditProps } from '@wordpress/blocks';
import {
	Disabled,
	PanelBody,
	SelectControl,
	ToggleControl,
	__experimentalToggleGroupControl as ToggleGroupControl,
	__experimentalToggleGroupControlOption as ToggleGroupControlOption,
} from '@wordpress/components';
import { useCustomDataContext } from '@woocommerce/shared-context';
import type { ProductResponseAttributeItem } from '@woocommerce/types';
import clsx from 'clsx';

/**
 * Internal dependencies
 */
import { useThemeColors } from '../../../../shared/hooks/use-theme-colors';

interface Attributes {
	className?: string;
	optionStyle?: 'pills' | 'dropdown';
	autoselect: boolean;
	disabledAttributesAction: 'disable' | 'hide';
}

function Pills( {
	id,
	options,
}: {
	id: string;
	options: SelectControl.Option[];
} ) {
	return (
		<ul
			id={ id }
			className="wc-block-add-to-cart-with-options-variation-selector-attribute-options__pills"
		>
			{ options.map( ( option, index ) => (
				<li
					key={ option.value }
					className={ clsx(
						'wc-block-add-to-cart-with-options-variation-selector-attribute-options__pill',
						{
							'wc-block-add-to-cart-with-options-variation-selector-attribute-options__pill--selected':
								index === 0,
							'wc-block-add-to-cart-with-options-variation-selector-attribute-options__pill--disabled':
								option.disabled,
						}
					) }
				>
					{ option.label }
				</li>
			) ) }
		</ul>
	);
}

export default function AttributeOptionsEdit(
	props: BlockEditProps< Attributes >
) {
	const { attributes, setAttributes } = props;
	const { className, optionStyle, autoselect, disabledAttributesAction } =
		attributes;

	const blockProps = useBlockProps( {
		className,
	} );

	// Apply selected variation pill styles based on Site Editor's background and text colors.
	useThemeColors(
		'add-to-cart-with-options-variation-selector-attribute-options',
		( { editorBackgroundColor, editorColor } ) => `
			:where(.wc-block-add-to-cart-with-options-variation-selector-attribute-options__pill--selected) {
				--pill-color: ${ editorBackgroundColor };
				--pill-background-color: ${ editorColor };
			}
		`
	);

	const { data: attribute } =
		useCustomDataContext< ProductResponseAttributeItem >( 'attribute' );

	if ( ! attribute ) return null;

	const options = attribute.terms.map( ( term, index ) => ( {
		value: term.slug,
		label: term.name,
		disabled: index > 1 && index === attribute.terms.length - 1,
	} ) );

	return (
		<div { ...blockProps }>
			<InspectorControls>
				<PanelBody title={ __( 'Style', 'woocommerce' ) }>
					<ToggleGroupControl
						label={ __( 'Style', 'woocommerce' ) }
						value={ optionStyle ?? 'pills' }
						onChange={ ( newOptionStyle ) => {
							if (
								newOptionStyle === 'pills' ||
								newOptionStyle === 'dropdown'
							) {
								setAttributes( {
									optionStyle: newOptionStyle,
								} );
							}
						} }
						isBlock
						hideLabelFromVision
						size="__unstable-large"
					>
						<ToggleGroupControlOption
							value="pills"
							label={ __( 'Pills', 'woocommerce' ) }
						/>
						<ToggleGroupControlOption
							value="dropdown"
							label={ __( 'Dropdown', 'woocommerce' ) }
						/>
					</ToggleGroupControl>
				</PanelBody>
				<PanelBody title={ __( 'Auto-select', 'woocommerce' ) }>
					<ToggleControl
						label={ __(
							'Auto-select when only one attribute is compatible',
							'woocommerce'
						) }
						help={ __(
							'This controls whether attributes will be auto-selected once upon loading the page and when an attribute is changed by the user. Only attributes with a single compatible value will be auto-selected.',
							'woocommerce'
						) }
						checked={ autoselect }
						onChange={ () =>
							setAttributes( { autoselect: ! autoselect } )
						}
						__nextHasNoMarginBottom
					/>
					<SelectControl
						label={ __(
							'Values in conflict with current selection',
							'woocommerce'
						) }
						help={ __(
							'This controls what to do with attribute values that conflict with the current selection.',
							'woocommerce'
						) }
						value={ disabledAttributesAction }
						options={ [
							{
								label: __( 'Hidden', 'woocommerce' ),
								value: 'hide',
							},
							{
								label: __(
									'Grayed-out/crossed-out and disabled',
									'woocommerce'
								),
								value: 'disable',
							},
						] }
						onChange={ ( value ) =>
							setAttributes( {
								disabledAttributesAction: value as
									| 'disable'
									| 'hide',
							} )
						}
						__nextHasNoMarginBottom
					/>
				</PanelBody>
			</InspectorControls>

			<Disabled>
				{ optionStyle === 'dropdown' ? (
					<select
						id={ attribute.taxonomy }
						className="wc-block-add-to-cart-with-options-variation-selector-attribute-options__dropdown"
					>
						{ options.map( ( option ) => (
							<option key={ option.value } value={ option.value }>
								{ option.label }
							</option>
						) ) }
					</select>
				) : (
					<Pills id={ attribute.taxonomy } options={ options } />
				) }
			</Disabled>
		</div>
	);
}
