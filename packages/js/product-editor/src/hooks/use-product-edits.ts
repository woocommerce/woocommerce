/**
 * External dependencies
 */
import { useEntityProp } from '@wordpress/core-data';
import { useSelect } from '@wordpress/data';

type EntityEdits = {
	[ key: string ]: unknown;
};

// Filter out the "content" and "blocks" properties of the edits since
// we do not use these properties within the product editor and they
// will always create false positives.
function filterProductEdits( edits: EntityEdits ) {
	delete edits.content;
	delete edits.blocks;
	return edits;
}

export function useProductEdits() {
	const [ productId ] = useEntityProp< number >(
		'postType',
		'product',
		'id'
	);

	const { edits } = useSelect(
		( select ) => {
			const { getEntityRecordNonTransientEdits } = select( 'core' );

			const _edits = getEntityRecordNonTransientEdits(
				'postType',
				'product',
				productId
			) as EntityEdits;

			return {
				edits: filterProductEdits( _edits ),
			};
		},
		[ productId ]
	);

	return {
		hasEdits: Object.keys( edits ).length > 0,
		edits,
	};
}
