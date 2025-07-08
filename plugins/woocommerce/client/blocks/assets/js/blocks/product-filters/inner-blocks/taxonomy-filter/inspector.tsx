/**
 * External dependencies
 */
import { InspectorControls } from '@wordpress/block-editor';
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
import type { EditProps, TaxonomyItem } from './types';
import {
	DisplayStyleSwitcher,
	resetDisplayStyleBlock,
} from '../../components/display-style-switcher';
import metadata from './block.json';
import { updateFilterHeading } from '../../utils/update-filter-heading';
import { getTaxonomyLabel } from './utils';

const taxonomies = getSetting< TaxonomyItem[] >(
	'filterableProductTaxonomies',
	[]
);
const taxonomyOptions = taxonomies.map( ( item ) => ( {
	label: item.label,
	value: item.name,
} ) );

export const TaxonomyFilterInspectorControls = ( {
	attributes,
	setAttributes,
	clientId,
}: EditProps ) => {
	const { taxonomy, showCounts, sortOrder, hideEmpty, displayStyle } =
		attributes;

	return (
		<InspectorControls>
			<ToolsPanel
				label={ __( 'Taxonomy Filter Settings', 'woocommerce' ) }
				resetAll={ () => {
					setAttributes( {
						taxonomy: metadata.attributes.taxonomy.default,
						sortOrder: metadata.attributes.sortOrder.default,
						displayStyle: metadata.attributes.displayStyle.default,
						showCounts: metadata.attributes.showCounts.default,
						hideEmpty: metadata.attributes.hideEmpty.default,
					} );
					resetDisplayStyleBlock(
						clientId,
						metadata.attributes.displayStyle.default,
						metadata.name
					);
				} }
			>
				<ToolsPanelItem
					label={ __( 'Taxonomy', 'woocommerce' ) }
					hasValue={ () => !! taxonomy }
					onDeselect={ () =>
						setAttributes( {
							taxonomy: metadata.attributes.taxonomy.default,
						} )
					}
					isShownByDefault={ true }
				>
					<SelectControl
						label={ __( 'Taxonomy', 'woocommerce' ) }
						help={ __(
							'Select a taxonomy to filter by.',
							'woocommerce'
						) }
						value={ taxonomy }
						options={ [
							{
								label: __( 'Select a taxonomy', 'woocommerce' ),
								value: '',
							},
							...taxonomyOptions,
						] }
						onChange={ ( value: string ) => {
							setAttributes( { taxonomy: value } );
							updateFilterHeading(
								clientId,
								getTaxonomyLabel( value )
							);
						} }
					/>
				</ToolsPanelItem>
				<ToolsPanelItem
					label={ __( 'Sort Order', 'woocommerce' ) }
					hasValue={ () => sortOrder !== 'count-desc' }
					onDeselect={ () =>
						setAttributes( {
							sortOrder: metadata.attributes.sortOrder.default,
						} )
					}
				>
					<SelectControl
						label={ __( 'Sort Order', 'woocommerce' ) }
						value={ sortOrder }
						options={ [
							{
								label: __(
									'Count (High to Low)',
									'woocommerce'
								),
								value: 'count-desc',
							},
							{
								label: __(
									'Count (Low to High)',
									'woocommerce'
								),
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
						] }
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
							displayStyle:
								metadata.attributes.displayStyle.default,
						} );
						resetDisplayStyleBlock(
							clientId,
							metadata.attributes.displayStyle.default,
							metadata.name
						);
					} }
				>
					<DisplayStyleSwitcher
						clientId={ clientId }
						currentStyle={ displayStyle }
						onChange={ ( value: string | number | undefined ) =>
							setAttributes( { displayStyle: value as string } )
						}
						parentBlockName={ metadata.name }
					/>
				</ToolsPanelItem>
				<ToolsPanelItem
					label={ __( 'Product counts', 'woocommerce' ) }
					hasValue={ () => showCounts }
					onDeselect={ () =>
						setAttributes( {
							showCounts: metadata.attributes.showCounts.default,
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
							hideEmpty: metadata.attributes.hideEmpty.default,
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
