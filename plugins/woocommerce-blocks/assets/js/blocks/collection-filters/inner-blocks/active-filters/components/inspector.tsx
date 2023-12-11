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
			<PanelBody
				title={ __(
					'Display Settings',
					'woo-gutenberg-products-block'
				) }
			>
				<ToggleGroupControl
					label={ __(
						'Display Style',
						'woo-gutenberg-products-block'
					) }
					value={ displayStyle }
					onChange={ ( value: BlockAttributes[ 'displayStyle' ] ) =>
						setAttributes( {
							displayStyle: value,
						} )
					}
					className="wc-block-active-filter__style-toggle"
				>
					<ToggleGroupControlOption
						value="list"
						label={ __( 'List', 'woo-gutenberg-products-block' ) }
					/>
					<ToggleGroupControlOption
						value="chips"
						label={ __( 'Chips', 'woo-gutenberg-products-block' ) }
					/>
				</ToggleGroupControl>
			</PanelBody>
		</InspectorControls>
	);
};
