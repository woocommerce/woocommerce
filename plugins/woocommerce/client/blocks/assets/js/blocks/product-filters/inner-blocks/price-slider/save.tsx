/**
 * External dependencies
 */
import { useBlockProps } from '@wordpress/block-editor';
import clsx from 'clsx';

/**
 * Internal dependencies
 */
import { getHasColorClasses, getStyleColorVars } from '../../utils/colors';
import { colorNames } from './constants';
import type { BlockAttributes } from './types';

export default function save( {
	attributes,
}: {
	attributes: BlockAttributes;
} ) {
	const blockProps = useBlockProps.save( {
		className: clsx(
			'wc-block-product-filter-price-slider',
			getHasColorClasses( attributes, colorNames )
		),
		style: getStyleColorVars(
			'wc-product-filter-price',
			attributes,
			colorNames
		),
	} );

	return <div { ...blockProps } />;
}
