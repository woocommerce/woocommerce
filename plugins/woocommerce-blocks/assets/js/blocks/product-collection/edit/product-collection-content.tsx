/**
 * External dependencies
 */
import { useBlockProps, useInnerBlocksProps } from '@wordpress/block-editor';
import { useInstanceId } from '@wordpress/compose';
import { useEffect } from '@wordpress/element';

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

	/**
	 * Because of issue https://github.com/WordPress/gutenberg/issues/7342,
	 * We are using this workaround to set default attributes.
	 */
	useEffect( () => {
		setAttributes( {
			...DEFAULT_ATTRIBUTES,
			query: {
				...( DEFAULT_ATTRIBUTES.query as ProductCollectionQuery ),
				inherit: getDefaultValueOfInheritQueryFromTemplate(),
			},
			...( attributes as Partial< ProductCollectionAttributes > ),
			...{
				// We need a reliable way to identify unique instances of this block regardless of
				// where it might exist. While the clientId is not stable across page loads, it is
				// a uuid and we can rely on it for uniqueness without needing to geenrate a new
				// unique ID and pull in a dependency.
				id: attributes?.id || clientId,
				// Each instance of the block needs to have a unique queryId so that pagination works
				// when multiple instances of this block are present. `instanceId` is the index of
				// the block on the page and is stable across saves with automatic handling for
				// re-indexing when these blocks are added, removed, or moved. Depending on
				// what it's set to it might be changed during block editing but that won't
				// negatively impact the functionality and pagination will still work.
				queryId: instanceId as number,
			},
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
