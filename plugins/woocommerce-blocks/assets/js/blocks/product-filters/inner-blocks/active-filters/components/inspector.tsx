/**
 * External dependencies
 */
import { InspectorControls } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';
import {
	PanelBody,
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalToggleGroupControl as ToggleGroupControl,
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalToggleGroupControlOption as ToggleGroupControlOption,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import type { EditProps, BlockAttributes } from '../types';

export const Inspector = ( { attributes, setAttributes }: EditProps ) => {
	const { displayStyle } = attributes;

	return (
		<InspectorControls>
			<PanelBody title={ __( 'Display Settings', 'woocommerce' ) }>
				<ToggleGroupControl
					label={ __( 'Display Style', 'woocommerce' ) }
					value={ displayStyle }
					onChange={ ( value: BlockAttributes[ 'displayStyle' ] ) =>
						setAttributes( {
							displayStyle: value,
						} )
					}
				>
					<ToggleGroupControlOption
						value="list"
						label={ __( 'List', 'woocommerce' ) }
					/>
					<ToggleGroupControlOption
						value="chips"
						label={ __( 'Chips', 'woocommerce' ) }
					/>
				</ToggleGroupControl>
			</PanelBody>
		</InspectorControls>
	);
};
