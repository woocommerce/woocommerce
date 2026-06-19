/**
 * External dependencies
 */
import { InspectorControls } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';
import {
	ToggleControl,
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalToolsPanel as ToolsPanel,
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalToolsPanelItem as ToolsPanelItem,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import {
	DisplayStyleSwitcher,
	resetDisplayStyleBlock,
} from '../shared/product-filters/components/display-style-switcher';
import { EditProps } from './types';

const DEFAULT_DISPLAY_STYLE = 'woocommerce/product-filter-checkbox-list';
const DEFAULT_SHOW_COUNTS = false;
const DEFAULT_HIDE_EMPTY = true;

export const Inspector = ( {
	attributes,
	setAttributes,
	clientId,
}: EditProps ) => {
	const {
		displayStyle = DEFAULT_DISPLAY_STYLE,
		showCounts = DEFAULT_SHOW_COUNTS,
		hideEmpty = DEFAULT_HIDE_EMPTY,
	} = attributes;

	return (
		<InspectorControls>
			<ToolsPanel
				label={ __( 'Display Settings', 'woocommerce' ) }
				resetAll={ () => {
					setAttributes( {
						displayStyle: DEFAULT_DISPLAY_STYLE,
						showCounts: DEFAULT_SHOW_COUNTS,
						hideEmpty: DEFAULT_HIDE_EMPTY,
					} );
					resetDisplayStyleBlock( clientId, DEFAULT_DISPLAY_STYLE );
				} }
			>
				<ToolsPanelItem
					label={ __( 'Display Style', 'woocommerce' ) }
					hasValue={ () =>
						displayStyle !==
						'woocommerce/product-filter-checkbox-list'
					}
					isShownByDefault={ true }
					onDeselect={ () => {
						setAttributes( {
							displayStyle: DEFAULT_DISPLAY_STYLE,
						} );
						resetDisplayStyleBlock(
							clientId,
							DEFAULT_DISPLAY_STYLE
						);
					} }
				>
					<DisplayStyleSwitcher
						clientId={ clientId }
						currentStyle={ displayStyle }
						onChange={ ( value ) =>
							setAttributes( { displayStyle: value } )
						}
					/>
				</ToolsPanelItem>
				<ToolsPanelItem
					label={ __( 'Product counts', 'woocommerce' ) }
					hasValue={ () => showCounts }
					onDeselect={ () =>
						setAttributes( {
							showCounts: DEFAULT_SHOW_COUNTS,
						} )
					}
					isShownByDefault={ true }
				>
					<ToggleControl
						label={ __( 'Product counts', 'woocommerce' ) }
						checked={ showCounts }
						onChange={ ( value: boolean ) =>
							setAttributes( { showCounts: value } )
						}
					/>
				</ToolsPanelItem>
				<ToolsPanelItem
					label={ __( 'Empty filter options', 'woocommerce' ) }
					hasValue={ () => ! hideEmpty }
					onDeselect={ () =>
						setAttributes( {
							hideEmpty: DEFAULT_HIDE_EMPTY,
						} )
					}
				>
					<ToggleControl
						label={ __( 'Empty filter options', 'woocommerce' ) }
						checked={ ! hideEmpty }
						onChange={ ( value: boolean ) =>
							setAttributes( { hideEmpty: ! value } )
						}
					/>
				</ToolsPanelItem>
			</ToolsPanel>
		</InspectorControls>
	);
};
