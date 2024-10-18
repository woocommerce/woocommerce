/**
 * External dependencies
 */
import { InspectorControls } from '@wordpress/block-editor';
import { __, _x } from '@wordpress/i18n';
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
import { EditProps } from '../types';

export const Inspector = ( { attributes, setAttributes }: EditProps ) => {
	const { showCounts, selectType, displayStyle } = attributes;

	return (
		<InspectorControls key="inspector">
			<PanelBody title={ __( 'Display Settings', 'woocommerce' ) }>
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
					value={ selectType || 'single' }
					onChange={ ( value: string ) =>
						setAttributes( {
							selectType: value,
						} )
					}
					className="wc-block-attribute-filter__multiple-toggle"
				>
					<ToggleGroupControlOption
						value="multiple"
						label={ _x(
							'Multiple',
							'Number of filters',
							'woocommerce'
						) }
					/>
					<ToggleGroupControlOption
						value="single"
						label={ _x(
							'Single',
							'Number of filters',
							'woocommerce'
						) }
					/>
				</ToggleGroupControl>
				<ToggleGroupControl
					label={ __( 'Display Style', 'woocommerce' ) }
					value={ displayStyle }
					onChange={ ( value ) =>
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
