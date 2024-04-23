/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	InspectorControls,
	useBlockProps,
	InnerBlocks,
} from '@wordpress/block-editor';
import { PanelBody, ColorPicker } from '@wordpress/components';

export default function Edit( { attributes, setAttributes } ) {
	const { color } = attributes;
	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Settings', 'woocommerce' ) }>
					<ColorPicker
						color={ color }
						onChange={ ( newColor: string ) =>
							setAttributes( { color: newColor } )
						}
						enableAlpha
						defaultValue="#bea0f2"
					/>
				</PanelBody>
			</InspectorControls>
			<div { ...useBlockProps() }>
				<InnerBlocks />
			</div>
			<style>
				{ `body, .editor-styles-wrapper.block-editor-writing-flow {
                    background-color: ${ color };
                }
                .wp-block-loginout {
                    background-color: #000000;
                    padding: 7px 17px;
                    border-radius: 6px;
                }
                .wp-block-loginout a {
                    color: #ffffff;
                    text-decoration: none;
                    line-height: 17px;
                    font-size: 14px;
                    font-weight: 500;
                }
                .woocommerce-coming-soon-powered-by-woo {
                    position: fixed;
                    bottom: 0;
                    left: 0;
                    width: 100%;
                }
                body .is-layout-constrained > .woocommerce-coming-soon-banner.alignwide {
                    max-width: 820px;
                }
                .woocommerce-coming-soon-header, .coming-soon-cover .wp-block-cover__background {
                    background-color: ${ color } !important;
                }
                .woocommerce-coming-soon-banner {
                    font-size: 48px;
                    font-weight: 400;
                    line-height: 58px;
                }` }
			</style>
		</>
	);
}
