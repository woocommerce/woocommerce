/**
 * External dependencies
 */
import {
	useBlockProps,
	useInnerBlocksProps,
	store as blockEditorStore,
	BlockContextProvider,
} from '@wordpress/block-editor';
import { useInstanceId } from '@wordpress/compose';
import { useEffect, useRef, useMemo } from '@wordpress/element';
import { Button } from '@wordpress/components';
import { useSelect, useDispatch } from '@wordpress/data';
import fastDeepEqual from 'fast-deep-equal/es6';
import { useIsEmailEditor } from '@woocommerce/email-editor';

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

	// queryId must be unique per block instance (for pagination) but stable
	// across re-mounts. Prefer the persisted value so re-mounting an existing
	// block doesn't rewrite the attribute and flag the entity dirty on open
	// (#48936); brand-new blocks have no queryId yet and fall back to instanceId.
	let queryId = Number.isFinite( attributes.queryId )
		? ( attributes.queryId as number )
		: ( instanceId as number );

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

	const isEmailEditor = useIsEmailEditor();

	const previewState = useSetPreviewState( {
		setPreviewState,
		initialPreviewState,
		location,
		attributes,
		isUsingReferencePreviewMode,
	} );

	// Provide the preview state to inner blocks (e.g. product-template) via block
	// context instead of a persisted attribute, so deriving it never marks the
	// post/template dirty.
	const previewStateContext = useMemo(
		() => ( {
			__privateProductCollectionPreviewState: previewState,
		} ),
		[ previewState ]
	);

	const blockProps = useBlockProps();
	const innerBlocksProps = useInnerBlocksProps(
		{},
		{
			template: INNER_BLOCKS_TEMPLATE,
		}
	);

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

	const { __unstableMarkNextChangeAsNotPersistent } =
		useDispatch( blockEditorStore );

	/**
	 * Because of issue https://github.com/WordPress/gutenberg/issues/7342,
	 * We are using this workaround to set default attributes.
	 */
	useEffect(
		() => {
			__unstableMarkNextChangeAsNotPersistent();
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
			{ previewState?.isPreview &&
				( isEmailEditor || props.isSelected ) && (
					<Button
						variant="primary"
						size="small"
						showTooltip
						label={ previewState?.previewMessage }
						className="wc-block-product-collection__preview-button"
						data-testid="product-collection-preview-button"
					>
						Preview
					</Button>
				) }

			<InspectorControls { ...props } />
			<InspectorAdvancedControls { ...props } />
			<ToolbarControls { ...props } />
			<BlockContextProvider value={ previewStateContext }>
				<div { ...innerBlocksProps } style={ style } />
			</BlockContextProvider>
		</div>
	);
};

export default ProductCollectionContent;
