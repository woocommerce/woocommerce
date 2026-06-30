/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	InspectorControls,
	useBlockProps,
	InnerBlocks,
} from '@wordpress/block-editor';
import { type BlockEditProps } from '@wordpress/blocks';
import {
	ColorPicker,
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalToolsPanel as ToolsPanel,
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalToolsPanelItem as ToolsPanelItem,
} from '@wordpress/components';

export type Attributes = {
	color?: string;
	storeOnly?: boolean;
};

export type EditProps = BlockEditProps< Attributes >;

/**
 * Internal dependencies
 */

export default function Edit( { attributes, setAttributes }: EditProps ) {
	const { color, storeOnly } = attributes;
	const blockProps = { ...useBlockProps() };

	// Existence of storeOnly attribute means it doesn't have a background color,
	// absence of custom color attribute means it's post-v1 template,
	// in both cases, no need to show the color picker.
	if ( storeOnly || ! color ) {
		return (
			<div { ...blockProps }>
				<InnerBlocks />
			</div>
		);
	}

	const DEFAULT_COLOR = '#bea0f2';

	return (
		<>
			<InspectorControls>
				<ToolsPanel
					label={ __( 'Settings', 'woocommerce' ) }
					resetAll={ () => {
						setAttributes( { color: DEFAULT_COLOR } );
					} }
				>
					<ToolsPanelItem
						hasValue={ () => color !== DEFAULT_COLOR }
						label={ __( 'Color', 'woocommerce' ) }
						onDeselect={ () =>
							setAttributes( { color: DEFAULT_COLOR } )
						}
						isShownByDefault
					>
						<ColorPicker
							color={ color }
							onChange={ ( newColor: string ) =>
								setAttributes( { color: newColor } )
							}
							enableAlpha
							defaultValue={ DEFAULT_COLOR }
						/>
					</ToolsPanelItem>
				</ToolsPanel>
			</InspectorControls>
			<div { ...blockProps }>
				<InnerBlocks />
				<style>{ `:root{--woocommerce-coming-soon-color: ${ color } }` }</style>
			</div>
		</>
	);
}
