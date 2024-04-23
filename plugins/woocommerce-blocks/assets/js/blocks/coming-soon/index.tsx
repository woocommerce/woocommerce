/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, InnerBlocks } from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import Edit from './edit';
import metadata from './block.json';

registerBlockType( metadata, {
	title: __( 'Coming Soon', 'woocommerce' ),
	edit: Edit,
	save: ( { attributes } ) => {
		const { color } = attributes;
		return (
			<div { ...useBlockProps.save() }>
				<InnerBlocks.Content />
				<style>
					{ `body {
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
                .coming-soon-is-vertically-aligned-center:not(.block-editor-block-list__block) {
                    position: absolute;
                    top: 50%;
                    transform: translateY(-50%);
                    margin-block-start: 0;
                    width: 100%;
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
			</div>
		);
	},
} );
