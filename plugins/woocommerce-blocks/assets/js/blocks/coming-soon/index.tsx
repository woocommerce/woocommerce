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
					{ `@font-face {
                    font-family: 'Inter';
                    src: url( <?php echo esc_url( WC()->plugin_url() . '/assets/fonts/Inter-VariableFont_slnt,wght.woff2' ); ?>) format('woff2');
                    font-weight: 300 900;
                    font-style: normal;
                }

                @font-face {
                    font-family: 'Cardo';
                    src: url( <?php echo esc_url( WC()->plugin_url() . '/assets/fonts/cardo_normal_400.woff2' ); ?>) format('woff2');
                    font-weight: 400;
                    font-style: normal;
                }
                /* Reset */
                h1, p, a {
                    margin: 0;
                    padding: 0;
                    border: 0;
                    vertical-align: baseline;
                }
                ol, ul {
                    list-style: none;
                }
                a {
                    text-decoration: none;
                }
                body,
                body.custom-background {
                    margin: 0;
                    background-color: #bea0f2;
                    font-family: 'Inter', sans-serif;
                    --wp--preset--color--contrast: #111111;
                    --wp--style--global--wide-size: 1280px;
                }
                body .is-layout-constrained > .alignwide {
                    margin: 0 auto;
                }
                .wp-container-core-group-is-layout-4.wp-container-core-group-is-layout-4 {
                    justify-content: space-between;
                }
                .is-layout-flex {
                    display: flex;
                    flex-wrap: wrap;
                    align-items: center;
                    margin: 0;
                }
                .wp-block-site-title p {
                    line-height: normal;
                }
                .wp-block-site-title a {
                    font-weight: 600;
                    font-size: 20px;
                    font-style: normal;
                    line-height: normal;
                    letter-spacing: -0.4px;
                    color: var(--wp--preset--color--contrast);
                    text-decoration: none;
                }
                .wp-block-social-links {
                    gap: 0.5em 18px;
                }
                .woocommerce-coming-soon-social-login {
                    gap: 48px;
                }
                .wp-block-loginout {
                    background-color: #000000;
                    border-radius: 6px;
                    display: flex;
                    height: 40px;
                    width: 74px;
                    justify-content: center;
                    align-items: center;
                    gap: 10px;
                    box-sizing: border-box;
                }
                .wp-block-loginout a {
                    color: #ffffff;
                    text-decoration: none;
                    line-height: 17px;
                    font-size: 14px;
                    font-weight: 500;
                }
                .wp-block-spacer {
                    margin: 0;
                }
                .woocommerce-coming-soon-banner-container {
                    padding-inline: min(5.5rem, 8vw);
                    margin: 0;
                    height: 100%;
                    display: flex;
                    flex-direction: column;
                    justify-content: space-between;
                }
                .woocommerce-coming-soon-powered-by-woo {
                    width: 100%;
                    --wp--preset--spacing--30: 0;
                    --wp--preset--spacing--10: 19px;
                }
                .woocommerce-coming-soon-powered-by-woo p {
                    font-style: normal;
                    font-weight: 400;
                    line-height: 160%; /* 19.2px */
                    letter-spacing: -0.12px;
                    color: #3C434A;
                    font-size: 12px;
                    font-family: Inter;
                }
                .woocommerce-coming-soon-powered-by-woo a {
                    font-family: Inter;
                }
                body .is-layout-constrained > .woocommerce-coming-soon-banner.alignwide {
                    max-width: 820px;
                }
                .coming-soon-is-vertically-aligned-center {
                    width: 100%;
                    align-items: stretch;
                }
                .coming-soon-cover {
                    background-color: ${ color } !important;
                }
                .woocommerce-coming-soon-header {
                    height: 40px;
                }
                .woocommerce-coming-soon-banner {
                    font-size: 48px;
                    font-weight: 400;
                    line-height: 58px;
                    font-family: 'Cardo', serif;
                    letter-spacing: normal;
                    text-align: center;
                    font-style: normal;
                    max-width: 820px;
                    color: var(--wp--preset--color--contrast);
                    margin: 0 auto;
                    min-height: 100px;
                }` }
				</style>
			</div>
		);
	},
} );
