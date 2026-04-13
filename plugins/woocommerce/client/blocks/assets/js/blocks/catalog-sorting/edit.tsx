/**
 * External dependencies
 */
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import type { BlockEditProps } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import {
	Disabled,
	ToggleControl,
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalToolsPanel as ToolsPanel,
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalToolsPanelItem as ToolsPanelItem,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import type { BlockAttributes } from './types';

const CatalogSorting = ( {
	useLabel,
}: Pick< BlockAttributes, 'useLabel' > ) => {
	return (
		<>
			{ useLabel ? (
				<>
					<label
						className="orderby-label"
						htmlFor="woocommerce-orderby"
					>
						{ __( 'Sort by', 'woocommerce' ) }
					</label>
					<select className="orderby" id="woocommerce-orderby">
						<option>{ __( 'Default', 'woocommerce' ) }</option>
					</select>
				</>
			) : (
				<select className="orderby">
					<option>{ __( 'Default sorting', 'woocommerce' ) }</option>
				</select>
			) }
		</>
	);
};

const Edit = ( {
	attributes,
	setAttributes,
}: BlockEditProps< BlockAttributes > ) => {
	const { useLabel } = attributes;
	const blockProps = useBlockProps( {
		className: 'woocommerce wc-block-catalog-sorting',
	} );

	return (
		<>
			<InspectorControls>
				<ToolsPanel
					label={ __( 'Accessibility', 'woocommerce' ) }
					resetAll={ () => {
						setAttributes( { useLabel: false } );
					} }
				>
					<ToolsPanelItem
						hasValue={ () => useLabel !== false }
						label={ __( 'Show visual label', 'woocommerce' ) }
						onDeselect={ () =>
							setAttributes( { useLabel: false } )
						}
						isShownByDefault
					>
						<ToggleControl
							__nextHasNoMarginBottom
							label={ __( 'Show visual label', 'woocommerce' ) }
							help={ __(
								'Displays "Sort by" text before the dropdown menu to improve clarity and accessibility.',
								'woocommerce'
							) }
							checked={ useLabel }
							onChange={ ( isChecked ) =>
								setAttributes( {
									useLabel: isChecked,
								} )
							}
						/>
					</ToolsPanelItem>
				</ToolsPanel>
			</InspectorControls>
			<div { ...blockProps }>
				<Disabled>
					<CatalogSorting useLabel={ useLabel } />
				</Disabled>
			</div>
		</>
	);
};

export default Edit;
