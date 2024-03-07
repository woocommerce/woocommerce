/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';
import { InspectorControls } from '@wordpress/block-editor';
import {
	PanelBody,
	ToggleControl,
	SelectControl,
	// @ts-expect-error - no types.
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalToggleGroupControl as ToggleGroupControl,
	// @ts-expect-error - no types.
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalToggleGroupControlOption as ToggleGroupControlOption,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import { EditProps } from '../types';

export const Inspector = ( { attributes, setAttributes }: EditProps ) => {
	const { taxonomyName, showCounts, queryType, displayStyle, selectType } =
		attributes;

	const taxonomies = useSelect( ( select ) => {
		const { getTaxonomies } = select( 'core' );
		return getTaxonomies( {
			type: 'product',
			per_page: -1,
		} );
	} );

	console.log( taxonomies );

	return (
		<InspectorControls key="inspector">
			<PanelBody title={ __( 'Filter Settings', 'woocommerce' ) }>
				{ taxonomies && (
					<SelectControl
						label={ __( 'Select taxonomy', 'woocommerce' ) }
						value={ taxonomyName }
						options={ taxonomies.map( ( item ) => ( {
							value: item.slug,
							label: item.name,
						} ) ) }
						onChange={ ( newTaxonomy ) =>
							setAttributes( { taxonomyName: newTaxonomy } )
						}
					/>
				) }
				<ToggleControl
					label={ __( 'Display product count', 'woocommerce' ) }
					checked={ showCounts }
					onChange={ () =>
						setAttributes( {
							showCounts: ! showCounts,
						} )
					}
				/>
				<ToggleGroupControl
					label={ __(
						'Allow selecting multiple options?',
						'woocommerce'
					) }
					value={ selectType || 'multiple' }
					onChange={ ( value: string ) =>
						setAttributes( {
							selectType: value,
						} )
					}
					className="wc-block-attribute-filter__multiple-toggle"
				>
					<ToggleGroupControlOption
						value="multiple"
						label={ __( 'Multiple', 'woocommerce' ) }
					/>
					<ToggleGroupControlOption
						value="single"
						label={ __( 'Single', 'woocommerce' ) }
					/>
				</ToggleGroupControl>
				{ selectType === 'multiple' && (
					<ToggleGroupControl
						label={ __( 'Filter Conditions', 'woocommerce' ) }
						help={
							queryType === 'and'
								? __(
										'Choose to return filter results for all of the attributes selected.',
										'woocommerce'
								  )
								: __(
										'Choose to return filter results for any of the attributes selected.',
										'woocommerce'
								  )
						}
						value={ queryType }
						onChange={ ( value: string ) =>
							setAttributes( {
								queryType: value,
							} )
						}
						className="wc-block-attribute-filter__conditions-toggle"
					>
						<ToggleGroupControlOption
							value="and"
							label={ __( 'All', 'woocommerce' ) }
						/>
						<ToggleGroupControlOption
							value="or"
							label={ __( 'Any', 'woocommerce' ) }
						/>
					</ToggleGroupControl>
				) }
				<ToggleGroupControl
					label={ __( 'Display Style', 'woocommerce' ) }
					value={ displayStyle }
					onChange={ ( value: string ) =>
						setAttributes( {
							displayStyle: value,
						} )
					}
					className="wc-block-attribute-filter__display-toggle"
				>
					<ToggleGroupControlOption
						value="list"
						label={ __( 'List', 'woocommerce' ) }
					/>
					<ToggleGroupControlOption
						value="dropdown"
						label={ __( 'Dropdown', 'woocommerce' ) }
					/>
				</ToggleGroupControl>
			</PanelBody>
		</InspectorControls>
	);
};
