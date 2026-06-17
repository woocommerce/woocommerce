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
import type { BlockEditProps, BlockInstance } from '@wordpress/blocks';
import { useSelect } from '@wordpress/data';
import { useCollection } from '@woocommerce/base-context/hooks';
import {
	CustomDataProvider,
	useCustomDataContext,
	useProductDataContext,
} from '@woocommerce/shared-context';
import { isProductResponseItem } from '@woocommerce/entities';
import type {
	AttributeTerm,
	ProductResponseAttributeItem,
} from '@woocommerce/types';
import { __ } from '@wordpress/i18n';
import {
	DisplayStyleSwitcher,
	resetDisplayStyleBlock,
} from '@woocommerce/editor-components/display-style-switcher';
import {
	ToggleControl,
	__experimentalToggleGroupControl as ToggleGroupControl,
	__experimentalToggleGroupControlOption as ToggleGroupControlOption,
	__experimentalToolsPanel as ToolsPanel,
	__experimentalToolsPanelItem as ToolsPanelItem,
} from '@wordpress/components';
import { getSetting } from '@woocommerce/settings';

/**
 * Internal dependencies
 */
import { DEFAULT_ATTRIBUTES, EMPTY_TERM_VISUALS } from './constants';
import type {
	SelectableItem,
	SelectableItemsContext,
} from '../../../../types/type-defs/selectable-items';
import type { VisualAttributeTerm } from '../../../../base/utils/visual-attribute-terms';

const INNER_CHIPS = 'woocommerce/product-filter-chips';

const getFallbackDisplayStyleInsertionPoint = (
	parentBlock: BlockInstance
) => {
	const groupBlock = parentBlock.innerBlocks.find(
		( block ) => block.name === 'core/group'
	);

	if ( groupBlock ) {
		return {
			rootClientId: groupBlock.clientId,
			index: groupBlock.innerBlocks.length,
		};
	}

	return {
		rootClientId: parentBlock.clientId,
		index: parentBlock.innerBlocks.length,
	};
};

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
	const termIds = useMemo( () => {
		return attribute?.terms
			? attribute.terms
					.map( ( term ) => term.id )
					.filter( ( termId ) => termId > 0 )
			: [];
	}, [ attribute ] );
	const { results: attributeTerms } = useCollection< AttributeTerm >( {
		namespace: '/wc/store/v1',
		resourceName: 'products/attributes/terms',
		resourceValues: [ attribute?.id || 0 ],
		shouldSelect: !! attribute?.id && termIds.length > 0,
		query: {
			include: termIds,
			hide_empty: false,
			__experimental_visual: true,
		},
	} );
	const visualAttributesEnabled = getSetting(
		'experimentalVisualAttributes',
		false
	);
	const visualByTermId = useMemo( () => {
		return attributeTerms.reduce< Record< number, VisualAttributeTerm > >(
			( accumulator, term ) => {
				if ( term.__experimentalVisual ) {
					accumulator[ term.id ] = term.__experimentalVisual;
				}

				return accumulator;
			},
			{}
		);
	}, [ attributeTerms ] );

	const selectableContext = useMemo( () => {
		let items: SelectableItem< {
			label: string;
			ariaLabel: string;
			visual?: VisualAttributeTerm;
		} >[] = [];
		if (
			attribute &&
			Array.isArray( attribute?.terms ) &&
			attribute.terms.length > 0
		) {
			items = attribute.terms.map( ( term ) => {
				const visual =
					visualByTermId[ term.id ] ||
					( visualAttributesEnabled
						? EMPTY_TERM_VISUALS[ term.id ]
						: undefined );

				return {
					id: `${ attribute.taxonomy }-${ term.slug }`,
					label: term.name,
					value: term.slug,
					ariaLabel: term.name,
					...( visual ? { visual } : {} ),
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
			visual?: VisualAttributeTerm;
		} >;
	}, [ attribute, visualAttributesEnabled, visualByTermId ] );

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
						resetDisplayStyleBlock(
							clientId,
							INNER_CHIPS,
							getFallbackDisplayStyleInsertionPoint
						);
					} }
				>
					<ToolsPanelItem
						hasValue={ () => displayStyle !== INNER_CHIPS }
						label={ __( 'Style', 'woocommerce' ) }
						onDeselect={ () => {
							setAttributes( { displayStyle: INNER_CHIPS } );
							resetDisplayStyleBlock(
								clientId,
								INNER_CHIPS,
								getFallbackDisplayStyleInsertionPoint
							);
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
								getFallbackDisplayStyleInsertionPoint={
									getFallbackDisplayStyleInsertionPoint
								}
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
