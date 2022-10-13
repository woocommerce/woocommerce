/**
 * External dependencies
 */
import { isFeaturePluginBuild } from '@woocommerce/block-settings';
import { createBlock, registerBlockType } from '@wordpress/blocks';
import { Icon, starEmpty } from '@wordpress/icons';
import classNames from 'classnames';
import { useBlockProps } from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import edit from './edit';
import metadata from './block.json';
import type { Attributes } from './types';

if ( isFeaturePluginBuild() ) {
	registerBlockType( metadata, {
		icon: {
			src: (
				<Icon
					icon={ starEmpty }
					className="wc-block-editor-components-block-icon"
				/>
			),
		},
		attributes: {
			...metadata.attributes,
		},
		transforms: {
			from: [
				{
					type: 'block',
					blocks: [ 'core/legacy-widget' ],
					// We can't transform if raw instance isn't shown in the REST API.
					isMatch: ( { idBase, instance } ) =>
						idBase === 'woocommerce_rating_filter' &&
						!! instance?.raw,
					transform: () => createBlock( 'woocommerce/rating-filter' ),
				},
			],
		},
		edit,
		// Save the props to post content.
		save( { attributes }: { attributes: Attributes } ) {
			const { className, showCounts } = attributes;
			const data: Record< string, unknown > = {
				'data-show-counts': showCounts,
			};
			return (
				<div
					{ ...useBlockProps.save( {
						className: classNames( 'is-loading', className ),
					} ) }
					{ ...data }
				>
					<span
						aria-hidden
						className="wc-block-product-rating-filter__placeholder"
					/>
				</div>
			);
		},
	} );
}
