/**
 * External dependencies
 */
import classNames from 'classnames';
import { useBlockProps } from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import metadata from './block.json';
import { blockAttributes } from './attributes';
import type { Attributes } from './types';

const v1 = {
	attributes: {
		...metadata.attributes,
		...blockAttributes,
	},
	save: ( { attributes }: { attributes: Attributes } ) => {
		const {
			className,
			showInputFields,
			showFilterButton,
			heading,
			headingLevel,
		} = attributes;
		const data = {
			'data-showinputfields': showInputFields,
			'data-showfilterbutton': showFilterButton,
			'data-heading': heading,
			'data-heading-level': headingLevel,
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
					className="wc-block-product-categories__placeholder"
				/>
			</div>
		);
	},
};

const deprecated = [ v1 ];

export default deprecated;
