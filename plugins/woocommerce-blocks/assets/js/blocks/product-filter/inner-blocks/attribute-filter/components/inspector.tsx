/**
 * External dependencies
 */
import { getSetting } from '@woocommerce/settings';
import { AttributeSetting } from '@woocommerce/types';
import { InspectorControls } from '@wordpress/block-editor';
import {
	ComboboxControl,
	PanelBody,
	SelectControl,
	// @ts-expect-error - no types.
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalToggleGroupControl as ToggleGroupControl,
	// @ts-expect-error - no types.
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalToggleGroupControlOption as ToggleGroupControlOption,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { sortOrderOptions } from '../constants';
import { EditProps, BlockAttributes } from '../types';

const ATTRIBUTES = getSetting< AttributeSetting[] >( 'attributes', [] );

export const Inspector = ( { attributes, setAttributes }: EditProps ) => {
	const { attributeId, sortOrder, queryType } = attributes;

	return (
		<InspectorControls key="inspector">
			<PanelBody title={ __( 'Attribute', 'woocommerce' ) }>
				<ComboboxControl
					options={ ATTRIBUTES.map( ( item ) => ( {
						value: item.attribute_id,
						label: item.attribute_label,
					} ) ) }
					value={ attributeId + '' }
					onChange={ ( value ) =>
						setAttributes( {
							attributeId: parseInt( value || '', 10 ),
						} )
					}
				/>
			</PanelBody>
			<PanelBody title={ __( 'Settings', 'woocommerce' ) }>
				<SelectControl
					label={ __( 'Sort order', 'woocommerce' ) }
					value={ sortOrder }
					options={ [
						{
							value: '',
							label: __( 'Select an option', 'woocommerce' ),
							disabled: true,
						},
						...sortOrderOptions,
					] }
					onChange={ ( value ) =>
						setAttributes( { sortOrder: value } )
					}
					help={ __(
						'Determine the order of filter options.',
						'woocommerce'
					) }
				/>
				<ToggleGroupControl
					label={ __( 'Logic', 'woocommerce' ) }
					value={ queryType }
					onChange={ ( value: BlockAttributes[ 'queryType' ] ) =>
						setAttributes( { queryType: value } )
					}
					style={ { width: '100%' } }
					help={
						queryType === 'and'
							? __(
									'Show results for all selected attributes. Displayed products must contain all of them to appear in the results.',
									'woocommerce'
							  )
							: __(
									'Show results for any of the attributes selected (displayed products don’t have to have them all).',
									'woocommerce'
							  )
					}
				>
					<ToggleGroupControlOption
						label={ __( 'Any', 'woocommerce' ) }
						value="or"
					/>
					<ToggleGroupControlOption
						label={ __( 'All', 'woocommerce' ) }
						value="and"
					/>
				</ToggleGroupControl>
			</PanelBody>
		</InspectorControls>
	);
};
