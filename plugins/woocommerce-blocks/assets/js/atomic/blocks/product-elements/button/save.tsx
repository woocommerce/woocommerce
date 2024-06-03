/**
 * External dependencies
 */
import { useBlockProps } from '@wordpress/block-editor';
import clsx from 'clsx';

/**
 * Internal dependencies
 */
import { BlockAttributes } from './types';

type Props = {
	attributes: BlockAttributes;
};

const Save = ( { attributes }: Props ): JSX.Element | null => {
	if (
		attributes.isDescendentOfQueryLoop ||
		attributes.isDescendentOfSingleProductBlock
	) {
		return null;
	}

	return (
		<div
			{ ...useBlockProps.save( {
				className: clsx( 'is-loading', attributes.className, {
					[ `has-custom-width wp-block-button__width-${ attributes.width }` ]:
						attributes.width,
				} ),
			} ) }
		/>
	);
};

export default Save;
