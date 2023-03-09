/**
 * External dependencies
 */
import { registerBlockType } from '@wordpress/blocks';
import type { BlockConfiguration } from '@wordpress/blocks';
import classnames from 'classnames';

/**
 * Internal dependencies
 */
import sharedConfig from '../shared/config';
import attributes from './attributes';
import edit from './edit';
import {
	BLOCK_TITLE as title,
	BLOCK_ICON as icon,
	BLOCK_DESCRIPTION as description,
} from './constants';

const { ancestor, ...configuration } = sharedConfig;

const blockConfig: BlockConfiguration = {
	...configuration,
	apiVersion: 2,
	title,
	description,
	icon: { src: icon },
	usesContext: [ 'query', 'queryId', 'postId' ],
	attributes,
	ancestor: [
		'woocommerce/all-products',
		'woocommerce/single-product',
		'core/post-template',
		'woocommerce/product-meta',
	],
	edit,
	save: () => {
		if (
			attributes.isDescendentOfQueryLoop ||
			attributes.isDescendentOfSingleProductTemplate
		) {
			return null;
		}

		return (
			<div
				className={ classnames( 'is-loading', attributes.className ) }
			/>
		);
	},
};

registerBlockType( 'woocommerce/product-sku', { ...blockConfig } );
