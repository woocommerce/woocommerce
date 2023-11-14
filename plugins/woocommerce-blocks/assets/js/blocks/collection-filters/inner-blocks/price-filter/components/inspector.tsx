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
import type { FilterComponentProps } from '../types';

export const Inspector = ( {
	attributes,
	setAttributes,
}: Omit< FilterComponentProps, 'collectionData' > ) => {
	const { showInputFields, inlineInput } = attributes;

	return (
		<InspectorControls>
			<PanelBody
				title={ __( 'Settings', 'woo-gutenberg-products-block' ) }
			>
				<ToggleGroupControl
					label={ __(
						'Price Slider',
						'woo-gutenberg-products-block'
					) }
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
						label={ __(
							'Editable',
							'woo-gutenberg-products-block'
						) }
					/>
					<ToggleGroupControlOption
						value="text"
						label={ __( 'Text', 'woo-gutenberg-products-block' ) }
					/>
				</ToggleGroupControl>
				{ showInputFields && (
					<ToggleControl
						label={ __(
							'Inline input fields',
							'woo-gutenberg-products-block'
						) }
						checked={ inlineInput }
						onChange={ () =>
							setAttributes( {
								inlineInput: ! inlineInput,
							} )
						}
						help={ __(
							'Show input fields inline with the slider.',
							'woo-gutenberg-products-block'
						) }
					/>
				) }
			</PanelBody>
		</InspectorControls>
	);
};
