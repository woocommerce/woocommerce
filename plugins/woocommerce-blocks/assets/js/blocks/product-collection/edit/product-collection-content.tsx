/**
 * External dependencies
 */
import {
	useBlockProps,
	useInnerBlocksProps,
	store as blockEditorStore,
} from '@wordpress/block-editor';
import { useInstanceId } from '@wordpress/compose';
import { useEffect, useRef, useMemo } from '@wordpress/element';
import { Button } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import fastDeepEqual from 'fast-deep-equal/es6';

/**
 * Internal dependencies
 */
import {
	ProductCollectionAttributes,
	ProductCollectionQuery,
	ProductCollectionContentProps,
	WidthOptions,
} from '../types';
import { DEFAULT_ATTRIBUTES, INNER_BLOCKS_TEMPLATE } from '../constants';
import {
	getDefaultValueOfInherit,
	getDefaultValueOfFilterable,
	useSetPreviewState,
} from '../utils';
import InspectorControls from './inspector-controls';
import InspectorAdvancedControls from './inspector-advanced-controls';
import ToolbarControls from './toolbar-controls';

const useQueryId = (
	clientId: string,
	attributes: ProductCollectionAttributes,
	ProductCollectionContent: React.FC
) => {
	const instanceId = useInstanceId( ProductCollectionContent );

	// eslint-disable-next-line @typescript-eslint/ban-ts-comment
	// @ts-ignore These selectors aren't getting their types loaded for some reason.
	const { getBlockParentsByBlockName } = useSelect( blockEditorStore );

	// In order to properly support pagination this block has a queryId attribute that
	// is initialized to a unique value when the block is first added to the editor.
	// We use the `instanceId` for this purpose. It is stable across saves as long
	// as the order of instances of these blocks in the editor does not change.
	// The block will be re-indexed in that case, however, this won't cause
	// any problems since the queryid only has to be stable across client
	// renders.
	let queryId = instanceId as number;

	// We need to take special care when handling instances in a sync pattern
	// to avoid an infinite loop. When two instances of a pattern are placed
	// on the same page, updating one will cause the other to be re-inserted.
	// If we change the ID on init it will trigger a loop as each competes
	// to set a new queryId and update the sync pattern.
	const blockParents = useMemo( () => {
		return getBlockParentsByBlockName( clientId, 'core/block' );
	}, [ getBlockParentsByBlockName, clientId ] );
	if ( blockParents.length > 0 ) {
		queryId = attributes.queryId;
	}

	return queryId;
};

const ProductCollectionContent = ( {
	preview: { setPreviewState, initialPreviewState } = {},
	...props
}: ProductCollectionContentProps ) => {
	const isInitialAttributesSet = useRef( false );
	const {
		clientId,
		attributes,
		setAttributes,
		location,
		isUsingReferencePreviewMode,
	} = props;

	useSetPreviewState( {
		setPreviewState,
		setAttributes,
		location,
		attributes,
		isUsingReferencePreviewMode,
	} );

	const blockProps = useBlockProps();
	const innerBlocksProps = useInnerBlocksProps( blockProps, {
		template: INNER_BLOCKS_TEMPLATE,
	} );

	const queryId = useQueryId(
		clientId,
		attributes,
		ProductCollectionContent as React.FC
	);

	const defaultAttributesValue = {
		...DEFAULT_ATTRIBUTES,
		query: {
			...( DEFAULT_ATTRIBUTES.query as ProductCollectionQuery ),
			inherit: getDefaultValueOfInherit(),
			filterable: getDefaultValueOfFilterable(),
		},
		...( attributes as Partial< ProductCollectionAttributes > ),
		queryId,
		// If initialPreviewState is provided, set it as previewState.
		...( !! attributes.collection &&
			initialPreviewState && {
				__privatePreviewState: initialPreviewState,
			} ),
	};

	let style = {};

	/**
	 * Set max-width if fixed width is set.
	 */
	if (
		WidthOptions.FIXED === attributes?.dimensions?.widthType &&
		attributes?.dimensions?.fixedWidth
	) {
		style = {
			maxWidth: attributes.dimensions.fixedWidth,
			margin: '0 auto',
		};
	}

	/**
	 * Because of issue https://github.com/WordPress/gutenberg/issues/7342,
	 * We are using this workaround to set default attributes.
	 */
	useEffect(
		() => {
			setAttributes( defaultAttributesValue );
			isInitialAttributesSet.current = true;
		},
		// This hook is only needed on initialization and sets default attributes.
		// eslint-disable-next-line react-hooks/exhaustive-deps
		[]
	);

	/**
	 * If default attributes are not set, we don't wanna render anything.
	 * Default attributes are set in the useEffect above.
	 */
	isInitialAttributesSet.current =
		isInitialAttributesSet.current ||
		fastDeepEqual( attributes, defaultAttributesValue );
	if ( ! isInitialAttributesSet.current ) {
		return null;
	}

	return (
		<div { ...blockProps }>
			{ attributes.__privatePreviewState?.isPreview &&
				props.isSelected && (
					<Button
						variant="primary"
						size="small"
						showTooltip
						label={
							attributes.__privatePreviewState?.previewMessage
						}
						className="wc-block-product-collection__preview-button"
						data-testid="product-collection-preview-button"
					>
						Preview
					</Button>
				) }

			<InspectorControls { ...props } />
			<InspectorAdvancedControls { ...props } />
			<ToolbarControls { ...props } />
			<div { ...innerBlocksProps } style={ style } />
		</div>
	);
};

export default ProductCollectionContent;
