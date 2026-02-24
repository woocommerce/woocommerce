/**
 * External dependencies
 */
import clsx from 'clsx';
import { useCustomDataContext } from '@woocommerce/shared-context';
import type { ProductResponseAttributeItem } from '@woocommerce/types';
import { __ } from '@wordpress/i18n';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { type BlockEditProps } from '@wordpress/blocks';
import {
	Disabled,
	SelectControl,
	ToggleControl,
	__experimentalToggleGroupControl as ToggleGroupControl,
	__experimentalToggleGroupControlOption as ToggleGroupControlOption,
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalToolsPanel as ToolsPanel,
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalToolsPanelItem as ToolsPanelItem,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import { useThemeColors } from '../../../../shared/hooks/use-theme-colors';

interface Attributes {
	className?: string;
	optionStyle: 'pills' | 'dropdown';
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
				<ToolsPanel
					label={ __( 'Style', 'woocommerce' ) }
					resetAll={ () => setAttributes( { optionStyle: 'pills' } ) }
				>
					<ToolsPanelItem
						hasValue={ () => optionStyle !== 'pills' }
						label={ __( 'Style', 'woocommerce' ) }
						onDeselect={ () =>
							setAttributes( { optionStyle: 'pills' } )
						}
						isShownByDefault
					>
						<ToggleGroupControl
							label={ __( 'Style', 'woocommerce' ) }
							value={ optionStyle }
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
					</ToolsPanelItem>
				</ToolsPanel>
				<ToolsPanel
					label={ __( 'Auto-select', 'woocommerce' ) }
					resetAll={ () =>
						setAttributes( {
							autoselect: false,
							disabledAttributesAction: 'disable',
						} )
					}
				>
					<ToolsPanelItem
						label={ __(
							'Auto-select when only one option is available',
							'woocommerce'
						) }
						hasValue={ () => autoselect }
						onDeselect={ () =>
							setAttributes( { autoselect: false } )
						}
						isShownByDefault
					>
						<ToggleControl
							label={ __(
								'Auto-select when only one option is available',
								'woocommerce'
							) }
							help={ __(
								'Automatically select options on page load or after the shopper changes attributes, when only one valid choice is available.',
								'woocommerce'
							) }
							checked={ autoselect }
							onChange={ () =>
								setAttributes( { autoselect: ! autoselect } )
							}
							__nextHasNoMarginBottom
						/>
					</ToolsPanelItem>
					<ToolsPanelItem
						label={ __( 'Invalid options', 'woocommerce' ) }
						hasValue={ () =>
							disabledAttributesAction !== 'disable'
						}
						onDeselect={ () =>
							setAttributes( {
								disabledAttributesAction: 'disable',
							} )
						}
						isShownByDefault
					>
						<ToggleGroupControl
							label={ __( 'Invalid options', 'woocommerce' ) }
							help={ __(
								'Control the display of invalid options.',
								'woocommerce'
							) }
							value={ disabledAttributesAction }
							onChange={ ( value ) => {
								if ( value === 'hide' || value === 'disable' ) {
									setAttributes( {
										disabledAttributesAction: value,
									} );
								}
							} }
							isBlock
							size="__unstable-large"
						>
							<ToggleGroupControlOption
								value="disable"
								label={ __( 'Grayed-out', 'woocommerce' ) }
							/>
							<ToggleGroupControlOption
								value="hide"
								label={ __( 'Hidden', 'woocommerce' ) }
							/>
						</ToggleGroupControl>
					</ToolsPanelItem>
				</ToolsPanel>
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
