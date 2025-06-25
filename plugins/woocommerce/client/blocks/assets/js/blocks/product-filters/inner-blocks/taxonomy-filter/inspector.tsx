/**
 * External dependencies
 */
import { InspectorControls } from '@wordpress/block-editor';
import { PanelBody, SelectControl, ToggleControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { getSetting } from '@woocommerce/settings';

/**
 * Internal dependencies
 */
import type { EditProps, TaxonomyItem } from './types';
import { DisplayStyleSwitcher } from '../../components/display-style-switcher';
import metadata from './block.json';
import { updateFilterHeading } from '../../utils/update-filter-heading';
import { getTaxonomyLabel } from './utils';

export const Inspector = ( {
	attributes,
	setAttributes,
	clientId,
}: EditProps ) => {
	const { taxonomy, showCounts, sortOrder, hideEmpty, displayStyle } =
		attributes;

	const taxonomies = getSetting< TaxonomyItem[] >(
		'filterableProductTaxonomies',
		[]
	);
	const taxonomyOptions = taxonomies.map( ( item ) => ( {
		label: item.label,
		value: item.name,
	} ) );

	return (
		<InspectorControls>
			<PanelBody title={ __( 'Taxonomy', 'woocommerce' ) }>
				<SelectControl
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
			</PanelBody>
			<PanelBody title={ __( 'Display', 'woocommerce' ) }>
				<SelectControl
					label={ __( 'Sort Order', 'woocommerce' ) }
					value={ sortOrder }
					options={ [
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
					] }
					onChange={ ( value: string ) =>
						setAttributes( { sortOrder: value } )
					}
				/>
				<DisplayStyleSwitcher
					clientId={ clientId }
					currentStyle={ displayStyle }
					onChange={ ( value: string ) =>
						setAttributes( { displayStyle: value } )
					}
					parentBlockName={ metadata.name }
				/>
				<ToggleControl
					label={ __( 'Product counts', 'woocommerce' ) }
					checked={ showCounts }
					onChange={ ( value: boolean ) =>
						setAttributes( { showCounts: value } )
					}
				/>
				<ToggleControl
					label={ __( 'Hide items with no products', 'woocommerce' ) }
					checked={ hideEmpty }
					onChange={ ( value: boolean ) =>
						setAttributes( { hideEmpty: value } )
					}
				/>
			</PanelBody>
		</InspectorControls>
	);
};
