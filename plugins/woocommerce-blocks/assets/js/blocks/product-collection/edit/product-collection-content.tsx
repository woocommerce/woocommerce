/**
 * External dependencies
 */
import { useBlockProps, useInnerBlocksProps } from '@wordpress/block-editor';
import { useInstanceId } from '@wordpress/compose';
import { useEffect, useState } from '@wordpress/element';
import { Button } from '@wordpress/components';

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
import InspectorAdvancedControls from './inspector-advanced-controls';
import ToolbarControls from './toolbar-controls';

const usePreviewState = ( handlePreviewState ) => {
	// I have implemented this internal state to handle the preview state.
	// As you can see it contains isPreview and previewMessage.
	// - isPreview is a boolean to check if the block is in preview mode.
	// - previewMessage is a string to display the preview message in tooltip.
	const [ previewState, setPreviewState ] = useState( {
		isPreview: false,
		previewMessage: '',
	} );

	// Running handlePreviewState function provided by Collection, if it exists.
	useEffect( () => {
		handlePreviewState?.( {
			setPreviewState,
		} );

		// We want this to run only once, adding deps will cause performance issues.
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [] );

	return [ previewState, setPreviewState ];
};

const ProductCollectionContent = ( {
	handlePreviewState,
	...props
}: ProductCollectionEditComponentProps ) => {
	const [ previewState ] = usePreviewState( handlePreviewState );

	const { attributes, setAttributes } = props;
	const { queryId } = attributes;

	const blockProps = useBlockProps();
	const innerBlocksProps = useInnerBlocksProps( blockProps, {
		template: INNER_BLOCKS_TEMPLATE,
	} );

	const instanceId = useInstanceId( ProductCollectionContent );

	// We need this for multi-query block pagination.
	// Query parameters for each block are scoped to their ID.
	useEffect( () => {
		if ( ! Number.isFinite( queryId ) ) {
			setAttributes( { queryId: Number( instanceId ) } );
		}
	}, [ queryId, instanceId, setAttributes ] );

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
		} );
		// We don't wanna add attributes as a dependency here.
		// Because we want this to run only once.
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [ setAttributes ] );

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
			{ previewState.isPreview && (
				<Button
					variant="primary"
					size="small"
					style={ {
						position: 'absolute',
						top: 0,
						right: 0,
						zIndex: 1000,
					} }
					showTooltip
					label={ previewState.previewMessage }
					className="wc-block-product-collection__preview-button"
				>
					Preview
				</Button>
			) }

			<InspectorControls { ...props } />
			<InspectorAdvancedControls { ...props } />
			<ToolbarControls { ...props } />
			<div { ...innerBlocksProps } />
		</div>
	);
};

export default ProductCollectionContent;
