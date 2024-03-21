/**
 * External dependencies
 */
import { InspectorControls } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';
import {
	PanelBody,
	ToggleControl,
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalToggleGroupControl as ToggleGroupControl,
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalToggleGroupControlOption as ToggleGroupControlOption,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import type { EditProps } from '../types';

export const Inspector = ( { attributes, setAttributes }: EditProps ) => {
	const { showInputFields, inlineInput } = attributes;

	return (
		<InspectorControls>
			<PanelBody title={ __( 'Settings', 'woocommerce' ) }>
				<ToggleGroupControl
					label={ __( 'Price Slider', 'woocommerce' ) }
					value={ showInputFields ? 'editable' : 'text' }
					onChange={ ( value: string ) =>
						setAttributes( {
							showInputFields: value === 'editable',
						} )
					}
					className="wc-block-price-filter__price-range-toggle"
				>
					<ToggleGroupControlOption
						value="editable"
						label={ __( 'Editable', 'woocommerce' ) }
					/>
					<ToggleGroupControlOption
						value="text"
						label={ __( 'Text', 'woocommerce' ) }
					/>
				</ToggleGroupControl>
				{ showInputFields && (
					<ToggleControl
						label={ __( 'Inline input fields', 'woocommerce' ) }
						checked={ inlineInput }
						onChange={ () =>
							setAttributes( {
								inlineInput: ! inlineInput,
							} )
						}
						help={ __(
							'Show input fields inline with the slider.',
							'woocommerce'
						) }
					/>
				) }
			</PanelBody>
		</InspectorControls>
	);
};
