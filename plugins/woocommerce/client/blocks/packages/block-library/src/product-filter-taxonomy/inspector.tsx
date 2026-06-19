/**
 * External dependencies
 */
import { InspectorControls } from '@wordpress/block-editor';
import { useMemo } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { getSetting } from '@woocommerce/settings';
import {
	SelectControl,
	ToggleControl,
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalToolsPanel as ToolsPanel,
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalToolsPanelItem as ToolsPanelItem,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import type { EditProps } from './types';
import {
	DEFAULT_DISPLAY_STYLE,
	DEFAULT_SORT_ORDER,
	DEFAULT_TAXONOMY,
} from './types';
import {
	DisplayStyleSwitcher,
	resetDisplayStyleBlock,
} from '../shared/product-filters/components/display-style-switcher';

// Get the list of taxonomies that support custom ordering (drag & drop in admin).
const sortableTaxonomies = getSetting< string[] >( 'sortableTaxonomies', [
	'product_cat',
] );

export const TaxonomyFilterInspectorControls = ( {
	attributes,
	setAttributes,
	clientId,
}: EditProps ) => {
	const {
		showCounts = false,
		sortOrder = DEFAULT_SORT_ORDER,
		hideEmpty = true,
		displayStyle = DEFAULT_DISPLAY_STYLE,
		taxonomy = DEFAULT_TAXONOMY,
	} = attributes;

	// Only show "Menu order" option for taxonomies that support custom ordering.
	const sortOrderOptions = useMemo( () => {
		const baseOptions = [
			{
				label: __( 'Count (High to Low)', 'woocommerce' ),
				value: 'count-desc',
			},
			{
				label: __( 'Count (Low to High)', 'woocommerce' ),
				value: 'count-asc',
			},
			{
				label: __( 'Name (A to Z)', 'woocommerce' ),
				value: 'name-asc',
			},
			{
				label: __( 'Name (Z to A)', 'woocommerce' ),
				value: 'name-desc',
			},
		];

		// Add "Menu order" option only for sortable taxonomies.
		if ( sortableTaxonomies.includes( taxonomy ) ) {
			return [
				{
					label: __( 'Menu order', 'woocommerce' ),
					value: 'menu_order-asc',
				},
				...baseOptions,
			];
		}

		return baseOptions;
	}, [ taxonomy ] );

	return (
		<InspectorControls>
			<ToolsPanel
				label={ __( 'Display Settings', 'woocommerce' ) }
				resetAll={ () => {
					setAttributes( {
						sortOrder: DEFAULT_SORT_ORDER,
						displayStyle: DEFAULT_DISPLAY_STYLE,
						showCounts: false,
						hideEmpty: true,
					} );
					resetDisplayStyleBlock( clientId, DEFAULT_DISPLAY_STYLE );
				} }
			>
				<ToolsPanelItem
					label={ __( 'Sort Order', 'woocommerce' ) }
					hasValue={ () => sortOrder !== 'count-desc' }
					onDeselect={ () =>
						setAttributes( {
							sortOrder: DEFAULT_SORT_ORDER,
						} )
					}
				>
					<SelectControl
						label={ __( 'Sort Order', 'woocommerce' ) }
						value={ sortOrder }
						options={ sortOrderOptions }
						onChange={ ( value: string ) =>
							setAttributes( { sortOrder: value } )
						}
					/>
				</ToolsPanelItem>
				<ToolsPanelItem
					label={ __( 'Display Style', 'woocommerce' ) }
					hasValue={ () =>
						displayStyle !==
						'woocommerce/product-filter-checkbox-list'
					}
					isShownByDefault={ true }
					onDeselect={ () => {
						setAttributes( {
							displayStyle: DEFAULT_DISPLAY_STYLE,
						} );
						resetDisplayStyleBlock(
							clientId,
							DEFAULT_DISPLAY_STYLE
						);
					} }
				>
					<DisplayStyleSwitcher
						clientId={ clientId }
						currentStyle={ displayStyle }
						onChange={ ( value ) =>
							setAttributes( { displayStyle: value } )
						}
					/>
				</ToolsPanelItem>
				<ToolsPanelItem
					label={ __( 'Product counts', 'woocommerce' ) }
					hasValue={ () => showCounts }
					onDeselect={ () =>
						setAttributes( {
							showCounts: false,
						} )
					}
					isShownByDefault={ true }
				>
					<ToggleControl
						label={ __( 'Product counts', 'woocommerce' ) }
						checked={ showCounts }
						onChange={ ( value: boolean ) =>
							setAttributes( { showCounts: value } )
						}
					/>
				</ToolsPanelItem>
				<ToolsPanelItem
					label={ __( 'Hide items with no products', 'woocommerce' ) }
					hasValue={ () => ! hideEmpty }
					onDeselect={ () =>
						setAttributes( {
							hideEmpty: true,
						} )
					}
				>
					<ToggleControl
						label={ __(
							'Hide items with no products',
							'woocommerce'
						) }
						checked={ hideEmpty }
						onChange={ ( value: boolean ) =>
							setAttributes( { hideEmpty: value } )
						}
					/>
				</ToolsPanelItem>
			</ToolsPanel>
		</InspectorControls>
	);
};
