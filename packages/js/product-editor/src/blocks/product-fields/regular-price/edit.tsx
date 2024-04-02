/**
 * External dependencies
 */
import { useWooBlockProps } from '@woocommerce/block-templates';
import { InspectorControls } from '@wordpress/block-editor';
import { createElement } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { PanelBody, TextControl, ToggleControl } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { View } from './view';
import type { ProductEditorBlockEditProps } from '../../../types';
import type { SalePriceBlockAttributes } from './types';

export function Edit(
	props: ProductEditorBlockEditProps< SalePriceBlockAttributes >
) {
	const { attributes, setAttributes } = props;
	const blockProps = useWooBlockProps( attributes );
	const { label, help = '', isRequired, tooltip } = attributes;

	return (
		<div { ...blockProps }>
			<View { ...props } />
			<InspectorControls>
				<PanelBody title={ __( 'Settings', 'woocommerce' ) }>
					<TextControl
						label={ __( 'Label', 'woocommerce' ) }
						value={ label }
						onChange={ ( newValue ) =>
							setAttributes( { label: newValue } )
						}
					/>
					<TextControl
						label={ __( 'Help', 'woocommerce' ) }
						value={ help }
						onChange={ ( newValue ) =>
							setAttributes( { help: newValue } )
						}
					/>
					<TextControl
						label={ __( 'Tooltip', 'woocommerce' ) }
						value={ tooltip || '' }
						onChange={ ( newValue ) =>
							setAttributes( { tooltip: newValue } )
						}
					/>
					<ToggleControl
						label={ __( 'Required', 'woocommerce' ) }
						checked={ isRequired }
						onChange={ ( newValue ) =>
							setAttributes( { isRequired: newValue } )
						}
					/>
				</PanelBody>
			</InspectorControls>
		</div>
	);
}
