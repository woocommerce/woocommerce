/**
 * External dependencies
 */
import { useMemo, useState } from '@wordpress/element';
import {
	BlockContextProvider,
	InspectorControls,
	useBlockProps,
	useInnerBlocksProps,
	store as blockEditorStore,
	__experimentalUseBlockPreview as useBlockPreview,
} from '@wordpress/block-editor';
import { BlockInstance, type BlockEditProps } from '@wordpress/blocks';
import { useSelect } from '@wordpress/data';
import {
	CustomDataProvider,
	useCustomDataContext,
	useProductDataContext,
} from '@woocommerce/shared-context';
import { isProductResponseItem } from '@woocommerce/entities';
import type { ProductResponseAttributeItem } from '@woocommerce/types';
import { __ } from '@wordpress/i18n';
import { getSetting } from '@woocommerce/settings';
import {
	ToggleControl,
	__experimentalToggleGroupControl as ToggleGroupControl,
	__experimentalToggleGroupControlOption as ToggleGroupControlOption,
	__experimentalToolsPanel as ToolsPanel,
	__experimentalToolsPanelItem as ToolsPanelItem,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import { DEFAULT_ATTRIBUTES, EMPTY_TERM_COLORS } from './constants';
import {
	DisplayStyleSwitcher,
	resetDisplayStyleBlock,
} from '../../../product-filters/components/display-style-switcher';
import type {
	SelectableItem,
	SelectableItemsContext,
} from '../../../../types/type-defs/selectable-items';

const INNER_CHIPS = 'woocommerce/product-filter-chips';

interface Attributes {
	className?: string;
	displayStyle: string;
	autoselect: boolean;
	disabledAttributesAction: 'disable' | 'hide';
}

type AttributeItemProps = {
	blocks: BlockInstance[];
	isSelected: boolean;
	onSelect(): void;
};

function AttributeItem( { blocks, isSelected, onSelect }: AttributeItemProps ) {
	const { data: attribute } =
		useCustomDataContext< ProductResponseAttributeItem >( 'attribute' );

	const termColors = getSetting< Record< string, string > >(
		'variationSelectorTermColors',
		{} as Record< string, string >
	);

	const selectableContext = useMemo( () => {
		let items: SelectableItem< {
			label: string;
			ariaLabel: string;
		} >[] = [];
		if (
			attribute &&
			Array.isArray( attribute?.terms ) &&
			attribute.terms.length > 0
		) {
			items = attribute.terms.map( ( term ) => {
				let color: string | null = null;
				if ( term.id in termColors ) {
					color = termColors[ term.id ];
				} else if ( term.id in EMPTY_TERM_COLORS ) {
					color = EMPTY_TERM_COLORS[ term.id ];
				}
				return {
					id: `${ attribute.taxonomy }-${ term.slug }`,
					label: term.name,
					value: term.slug,
					ariaLabel: term.name,
					...( color !== null ? { color } : {} ),
				};
			} );
		}

		return {
			items,
			selectionMode: 'single' as const,
			storeNamespace: 'woocommerce/add-to-cart-with-options',
			groupLabel: '',
		} satisfies SelectableItemsContext< {
			label: string;
			ariaLabel: string;
		} >;
	}, [ attribute, termColors ] );

	const blockPreviewProps = useBlockPreview( {
		blocks,
	} );
	const innerBlocksProps = useInnerBlocksProps();

	if ( ! attribute ) {
		return null;
	}

	return (
		<BlockContextProvider
			value={ {
				'woocommerce/selectableItems': selectableContext,
			} }
		>
			{ isSelected ? (
				<div { ...innerBlocksProps } />
			) : (
				// We don't need these elements to be interactive with the
				// keyboard because the first attribute blocks are always
				// editable. We allow clicking on the blocks of other attributes
				// but it's not critical, so we disable the keyboard events.
				// eslint-disable-next-line jsx-a11y/click-events-have-key-events, jsx-a11y/no-static-element-interactions
				<div { ...blockPreviewProps } onClick={ onSelect } />
			) }
		</BlockContextProvider>
	);
}

export default function AttributeItemTemplateEdit(
	props: BlockEditProps< Attributes >
) {
	const { attributes, setAttributes, clientId } = props;
	const { className, displayStyle, autoselect, disabledAttributesAction } =
		attributes;

	const blockProps = useBlockProps( {
		className,
	} );

	const { product } = useProductDataContext();

	const productAttributes =
		isProductResponseItem( product ) && product.type === 'variable'
			? product.attributes
			: DEFAULT_ATTRIBUTES;

	const { blocks } = useSelect(
		( select ) => {
			const { getBlocks } = select( blockEditorStore );
			return {
				blocks: getBlocks( clientId ),
			};
		},
		[ clientId ]
	);

	const [ selectedAttributeItem, setSelectedAttributeItem ] =
		useState< number >();

	return (
		<>
			<InspectorControls>
				<ToolsPanel
					label={ __( 'Style', 'woocommerce' ) }
					resetAll={ () => {
						setAttributes( { displayStyle: INNER_CHIPS } );
						resetDisplayStyleBlock( clientId, INNER_CHIPS );
					} }
				>
					<ToolsPanelItem
						hasValue={ () => displayStyle !== INNER_CHIPS }
						label={ __( 'Style', 'woocommerce' ) }
						onDeselect={ () => {
							setAttributes( { displayStyle: INNER_CHIPS } );
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
								currentStyle={ displayStyle }
								onChange={ ( value ) => {
									setAttributes( {
										displayStyle: value,
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

			<div { ...blockProps }>
				{ productAttributes.map( ( attribute ) => (
					<CustomDataProvider
						key={ attribute.id }
						id="attribute"
						data={ attribute }
					>
						<AttributeItem
							blocks={ blocks }
							isSelected={
								( selectedAttributeItem ||
									productAttributes[ 0 ]?.id ) ===
								attribute.id
							}
							onSelect={ () =>
								setSelectedAttributeItem( attribute.id )
							}
						/>
					</CustomDataProvider>
				) ) }
			</div>
		</>
	);
}
