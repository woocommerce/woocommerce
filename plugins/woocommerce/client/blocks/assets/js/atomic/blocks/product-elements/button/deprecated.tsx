/**
 * Internal dependencies
 */
import { useBlockProps } from '@wordpress/block-editor';
import clsx from 'clsx';
import metadata from './block.json';
import { BlockAttributes } from './types';

const { attributes: blockAttributes } = metadata;

interface V1Attributes extends BlockAttributes {
	isDescendentOfQueryLoop?: boolean;
	isDescendentOfSingleProductBlock?: boolean;
}

const save = ( {
	attributes,
	innerBlocks,
}: {
	attributes: V1Attributes;
	innerBlocks?: unknown[];
} ) => {
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
