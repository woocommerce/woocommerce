/**
 * External dependencies
 */
import { InspectorControls } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';
import { PanelBody, ToggleControl } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { EditProps } from './types';
import { DisplayStyleSwitcher } from '../../components/display-style-switcher';
import metadata from './block.json';

export const Inspector = ( {
	attributes,
	setAttributes,
	clientId,
}: EditProps ) => {
	const { displayStyle, showCounts, hideEmpty } = attributes;

	return (
		<>
			<InspectorControls group="settings">
				<PanelBody title={ __( 'Display', 'woocommerce' ) }>
					<DisplayStyleSwitcher
						clientId={ clientId }
						currentStyle={ displayStyle }
						onChange={ ( value ) =>
							setAttributes( { displayStyle: value } )
						}
						parentBlockName={ metadata.name }
					/>
					<ToggleControl
						label={ __( 'Product counts', 'woocommerce' ) }
						checked={ showCounts }
						onChange={ ( value ) =>
							setAttributes( { showCounts: value } )
						}
						__nextHasNoMarginBottom
					/>
					<ToggleControl
						label={ __( 'Empty filter options', 'woocommerce' ) }
						checked={ ! hideEmpty }
						onChange={ ( value ) =>
							setAttributes( { hideEmpty: ! value } )
						}
						__nextHasNoMarginBottom
					/>
				</PanelBody>
			</InspectorControls>
		</>
	);
};
