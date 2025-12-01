/**
 * External dependencies
 */
import { useBlockProps } from '@wordpress/block-editor';
import clsx from 'clsx';

/**
 * Internal dependencies
 */
import { BlockAttributes } from './types';
import { getColorClasses, getColorVars } from './utils';

const Save = ( { attributes }: { attributes: BlockAttributes } ) => {
	const blockProps = useBlockProps.save( {
		className: clsx(
			'wc-block-product-filter-checkbox-list',
			attributes.className,
			getColorClasses( attributes )
		),
		style: {
			...getColorVars( attributes ),
		},
	} );

	return <div { ...blockProps } />;
};

export default Save;
