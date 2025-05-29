/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, ToggleControl } from '@wordpress/components';
import { isExperimentalBlocksEnabled } from '@woocommerce/block-settings';

/**
 * Internal dependencies
 */
import Block from './block';

export type BlockAttributes = {
	className?: string;
	disableProductDescriptions?: boolean;
};

export const Edit = ( {
	attributes,
	setAttributes,
}: {
	attributes: BlockAttributes;
	setAttributes: ( attributes: Record< string, unknown > ) => void;
} ): JSX.Element => {
	const { className = '', disableProductDescriptions = false } = attributes;
	const blockProps = useBlockProps();

	return (
		<div { ...blockProps }>
			{ /* For now this setting can only be enabled if you have experimental features enabled. */ }
			{ isExperimentalBlocksEnabled() && (
				<InspectorControls>
					<PanelBody title={ __( 'Settings', 'woocommerce' ) }>
						<ToggleControl
							label={ __(
								'Disable product descriptions',
								'woocommerce'
							) }
							help={ __(
								'Disable display of product descriptions.',
								'woocommerce'
							) }
							checked={ disableProductDescriptions }
							onChange={ () =>
								setAttributes( {
									disableProductDescriptions:
										! disableProductDescriptions,
								} )
							}
						/>
					</PanelBody>
				</InspectorControls>
			) }
			<Block
				disableProductDescriptions={ disableProductDescriptions }
				className={ className }
			/>
		</div>
	);
};

export const Save = (): JSX.Element => {
	return <div { ...useBlockProps.save() } />;
};
