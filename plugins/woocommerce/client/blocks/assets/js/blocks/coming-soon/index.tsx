/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { registerBlockType } from '@wordpress/blocks';
import { registerPlugin } from '@wordpress/plugins';

/**
 * Internal dependencies
 */
import Edit from './edit';
import Save from './save';
import metadata from './block.json';
import deprecated from './deprecated';
import NewsletterPanel from './newsletter-panel';
import './store-only.scss';
import './entire-site.scss';

registerBlockType( metadata, {
	title: __( 'Coming Soon', 'woocommerce' ),
	edit: Edit,
	save: Save,
	apiVersion: 3,
	deprecated,
} );

if ( typeof window.comingSoonNewsletter !== 'undefined' ) {
	registerPlugin( 'plugin-coming-soon-newsletter-setting-panel', {
		render: NewsletterPanel,
		icon: 'palmtree',
	} );
}
