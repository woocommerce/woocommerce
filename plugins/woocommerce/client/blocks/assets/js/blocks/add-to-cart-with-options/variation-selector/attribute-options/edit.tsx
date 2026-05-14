/**
 * External dependencies
 */
import { useMemo } from '@wordpress/element';
import { useCustomDataContext } from '@woocommerce/shared-context';
import type { ProductResponseAttributeItem } from '@woocommerce/types';
import { __ } from '@wordpress/i18n';
import {
	BlockContextProvider,
	InspectorControls,
	useBlockProps,
	useInnerBlocksProps,
} from '@wordpress/block-editor';
import { type BlockEditProps } from '@wordpress/blocks';
import {
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
import type { SelectableItemsContext } from '../../../../types/type-defs/selectable-items';
import type { FilterItemFields } from '../../../product-filters/types';
import {
	DisplayStyleSwitcher,
	resetDisplayStyleBlock,
} from '../../../product-filters/components/display-style-switcher';

const INNER_CHIPS = 'woocommerce/product-filter-chips';
const INNER_DROPDOWN = 'woocommerce/product-filter-dropdown';

function optionStyleToBlockName(
	optionStyle: 'chips' | 'dropdown' | 'pills'
): typeof INNER_CHIPS | typeof INNER_DROPDOWN {
	if ( optionStyle === 'dropdown' ) {
		return INNER_DROPDOWN;
	}
	return INNER_CHIPS;
}

function blockNameToOptionStyle( blockName: string ): 'chips' | 'dropdown' {
	return blockName === INNER_DROPDOWN ? 'dropdown' : 'chips';
}

interface Attributes {
	className?: string;
	optionStyle: 'chips' | 'dropdown' | 'pills';
	autoselect: boolean;
	disabledAttributesAction: 'disable' | 'hide';
}

export default function AttributeOptionsEdit(
	props: BlockEditProps< Attributes >
) {
	const { attributes, setAttributes, clientId } = props;
	const { className, optionStyle, autoselect, disabledAttributesAction } =
		attributes;

	const blockProps = useBlockProps( {
		className,
	} );

	const { data: attribute } =
		useCustomDataContext< ProductResponseAttributeItem >( 'attribute' );

	const selectableContext = useMemo( () => {
		if ( ! attribute ) {
			return {
				items: [] as SelectableItemsContext< FilterItemFields >[ 'items' ],
				selectionMode: 'single' as const,
				storeNamespace: 'woocommerce/add-to-cart-with-options',
				groupLabel: '',
			} satisfies SelectableItemsContext< FilterItemFields >;
		}

		const items = attribute.terms.map( ( term ) => ( {
			id: `${ attribute.taxonomy }-${ term.slug }`,
			label: term.name,
			value: term.slug,
			ariaLabel: term.name,
			count: 0,
			termId: term.id,
		} ) );

		return {
			items,
			selectionMode: 'single' as const,
			storeNamespace: 'woocommerce/add-to-cart-with-options',
			groupLabel: '',
		} satisfies SelectableItemsContext< FilterItemFields >;
	}, [ attribute ] );

	const { children, ...innerBlocksProps } = useInnerBlocksProps(
		{
			role: 'radiogroup',
			id: attribute?.taxonomy,
			'aria-label': attribute?.name,
		},
		{
			allowedBlocks: [ INNER_CHIPS, INNER_DROPDOWN ],
			template: [ [ optionStyleToBlockName( optionStyle ) ] ],
			templateLock: 'all',
		}
	);

	if ( ! attribute ) return null;

	return (
		<div { ...blockProps }>
			<InspectorControls>
				<ToolsPanel
					label={ __( 'Style', 'woocommerce' ) }
					resetAll={ () => {
						setAttributes( { optionStyle: 'chips' } );
						resetDisplayStyleBlock( clientId, INNER_CHIPS );
					} }
				>
					<ToolsPanelItem
						hasValue={ () => optionStyle === 'dropdown' }
						label={ __( 'Style', 'woocommerce' ) }
						onDeselect={ () => {
							setAttributes( { optionStyle: 'chips' } );
							resetDisplayStyleBlock( clientId, INNER_CHIPS );
						} }
						isShownByDefault
					>
						<div>
							<span className="screen-reader-text">
								{ __( 'Style', 'woocommerce' ) }
							</span>
							<DisplayStyleSwitcher
								clientId={ clientId }
								currentStyle={ optionStyleToBlockName(
									optionStyle
								) }
								onChange={ ( value ) => {
									setAttributes( {
										optionStyle:
											blockNameToOptionStyle( value ),
									} );
								} }
							/>
						</div>
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

			<BlockContextProvider
				value={ {
					woocommerceSelectableItems: selectableContext,
				} }
			>
				<div { ...innerBlocksProps }>{ children }</div>
			</BlockContextProvider>
		</div>
	);
}
