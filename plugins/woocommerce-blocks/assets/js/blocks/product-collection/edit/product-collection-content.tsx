/**
 * External dependencies
 */
import {
	useBlockProps,
	useInnerBlocksProps,
	store as blockEditorStore,
} from '@wordpress/block-editor';
import { useInstanceId } from '@wordpress/compose';
import { useEffect } from '@wordpress/element';
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import type {
	ProductCollectionAttributes,
	ProductCollectionQuery,
	ProductCollectionEditComponentProps,
} from '../types';
import { DEFAULT_ATTRIBUTES, INNER_BLOCKS_TEMPLATE } from '../constants';
import { getDefaultValueOfInheritQueryFromTemplate } from '../utils';
import InspectorControls from './inspector-controls';
import ToolbarControls from './toolbar-controls';

const ProductCollectionContent = (
	props: ProductCollectionEditComponentProps
) => {
	const { clientId, attributes, setAttributes } = props;

	const blockProps = useBlockProps();
	const innerBlocksProps = useInnerBlocksProps( blockProps, {
		template: INNER_BLOCKS_TEMPLATE,
	} );

	const instanceId = useInstanceId( ProductCollectionContent );
	// eslint-disable-next-line @typescript-eslint/ban-ts-comment
	// @ts-ignore These selectors aren't getting their types loaded for some reason.
	const { getBlock, getBlockParents } = useSelect( blockEditorStore );

	/**
	 * Because of issue https://github.com/WordPress/gutenberg/issues/7342,
	 * We are using this workaround to set default attributes.
	 */
	useEffect( () => {
		// In order to properly support pagination this block has a queryId attribute that
		// is initialized to a unique value when the block is first added to the editor.
		// We use the `instanceId` for this purpose. It is stable across saves as long
		// as the order of instances of these blocks in the editor does not change.
		// The block will be re-indexed in that case, however, this won't cause
		// any problems since the queryid only has to be stable across client
		// renders.
		let queryId = instanceId as number;

		// We DO need to be careful to not change the queryId if the block is part of a
		// sync pattern. When multiple instances of the same pattern are placed on a
		// page, updates to one will cause the others to be re-inserted. This will
		// increment the ID again and trigger a re-insertion of another instance
		// that updates the ID again, and so on until the browser hangs.
		const blockParents = getBlockParents( clientId );
		for ( const parentClientId of blockParents ) {
			const parent = getBlock( parentClientId );
			if ( parent && parent.name === 'core/block' ) {
				// In order to prevent collisions, we're going to offset the queryID of blocks
				// in patterns. We also want to randomize them so that they don't collide
				// with other blocks in patterns on the page. Take care to check the
				// offset so that we only do this once when the block is added to
				// the page.
				if ( attributes.queryId < 10000 ) {
					queryId = 10000 + Math.floor( Math.random() * 1000000 );
					break;
				}

				queryId = attributes.queryId;
				break;
			}
		}

		setAttributes( {
			...DEFAULT_ATTRIBUTES,
			query: {
				...( DEFAULT_ATTRIBUTES.query as ProductCollectionQuery ),
				inherit: getDefaultValueOfInheritQueryFromTemplate(),
			},
			...( attributes as Partial< ProductCollectionAttributes > ),
			queryId,
		} );
		// This hook is only needed on initialization and sets default attributes.
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [] );

	/**
	 * If inherit is not a boolean, then we haven't set default attributes yet.
	 * We don't wanna render anything until default attributes are set.
	 * Default attributes are set in the useEffect above.
	 */
	if ( typeof attributes?.query?.inherit !== 'boolean' ) {
		return null;
	}

	return (
		<div { ...blockProps }>
			<InspectorControls { ...props } />
			<ToolbarControls { ...props } />
			<div { ...innerBlocksProps } />
		</div>
	);
};

export default ProductCollectionContent;
