/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createBlock, registerBlockType } from '@wordpress/blocks';
import { Icon } from '@wordpress/icons';
import { sparkles } from '@woocommerce/icons';

/**
 * Internal dependencies
 */
import Edit from './edit';

const metadata = {
	name: 'woocommerce/coming-soon',
	category: 'woocommerce',
	title: __( 'Coming Soon', 'woocommerce' ),
	attributes: {},
};

registerBlockType( metadata, {
	title: __( 'Coming Soon', 'woocommerce' ),
	edit: Edit,
} );
