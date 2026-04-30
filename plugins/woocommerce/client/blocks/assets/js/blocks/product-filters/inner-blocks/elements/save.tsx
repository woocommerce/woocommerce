/**
 * External dependencies
 */
import { useBlockProps } from '@wordpress/block-editor';
import clsx from 'clsx';

/**
 * Internal dependencies
 */
import { BlockAttributes } from './types';

const Save = ( { attributes }: { attributes: BlockAttributes } ) => {
	const blockProps = useBlockProps.save( {
		className: clsx(
			'wc-block-product-filter-elements',
			attributes.className
		),
	} );

	return <div { ...blockProps } />;
};

export default Save;
