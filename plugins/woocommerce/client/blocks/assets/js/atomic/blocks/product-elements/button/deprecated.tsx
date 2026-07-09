/**
 * Internal dependencies
 */
import { useBlockProps } from '@wordpress/block-editor';
import clsx from 'clsx';
import metadata from './block.json';

const { attributes: blockAttributes } = metadata;

const save = ( { attributes, innerBlocks }: any ) => {
	if (
		attributes.isDescendentOfQueryLoop ||
		attributes.isDescendentOfSingleProductBlock ||
		! innerBlocks ||
		innerBlocks?.length === 0
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

const v1 = {
	attributes: {
		...blockAttributes,
		isDescendentOfQueryLoop: { type: 'boolean', default: false },
		isDescendentOfSingleProductBlock: {
			type: 'boolean',
			default: false,
		},
	},
	save,
	apiVersion: 3,
};

const deprecated = [ v1 ];

export default deprecated;
