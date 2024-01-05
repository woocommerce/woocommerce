/**
 * External dependencies
 */
import classNames from 'classnames';
import { useBlockProps } from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import metadata from './block.json';
import type { Attributes } from './types';

const v1 = {
	attributes: {
		...metadata.attributes,
		showCounts: {
			type: 'boolean',
			default: true,
		},
	},
	save: ( { attributes }: { attributes: Attributes } ) => {
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
};

const deprecated = [ v1 ];

export default deprecated;
