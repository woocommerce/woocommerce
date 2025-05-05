/**
 * External dependencies
 */
import classnames from 'classnames';
import { useRef } from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import { useMergeRefs } from '@wordpress/compose';
import { store as editorStore } from '@wordpress/editor';
import {
	BlockList,
	// @ts-expect-error No types for this exist yet.
	__unstableUseTypewriter as useTypewriter, // eslint-disable-line
	// @ts-expect-error No types for this exist yet.
	RecursionProvider,
	// @ts-expect-error No types for this exist yet.
	__experimentalUseResizeCanvas as useResizeCanvas, // eslint-disable-line
} from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import EditTemplateBlocksNotification from './edit-template-blocks-notification';
import useSelectNearestEditableBlock from './use-select-nearest-editable-block';
import { BlockCanvas } from '../../../private-apis';

export const TEMPLATE_POST_TYPE = 'wp_template';
export const TEMPLATE_PART_POST_TYPE = 'wp_template_part';
export const PATTERN_POST_TYPE = 'wp_block';
export const NAVIGATION_POST_TYPE = 'wp_navigation';

/**
 * These post types have a special editor where they don't allow you to fill the title
 * and they don't apply the layout styles.
 */
const DESIGN_POST_TYPES = [
	PATTERN_POST_TYPE,
	TEMPLATE_POST_TYPE,
	NAVIGATION_POST_TYPE,
	TEMPLATE_PART_POST_TYPE,
];

/**
 * Copied and simplified from https://github.com/WordPress/gutenberg/blob/c754c783a9004db678fcfebd9a21a22820f2115c/packages/editor/src/components/visual-editor/index.js
 * Simplifications:
 *  - doesn't support post-only mode for no design post types. We use the post-only mode only for templates
 *  - removed logic for layout styles. We currently pass them among all styles in styles property
 *  - removed support for post title
 *  - removed support for zooming
 *  - removed support for resizing
 *
 * @param root0
 * @param root0.styles
 * @param root0.disableIframe
 * @param root0.iframeProps
 * @param root0.contentRef
 * @param root0.className
 *  @todo Need to fix layout so that we support Align settings properly
 */
export function VisualEditor( {
	// Ideally as we unify post and site editors, we won't need these props
	styles,
	disableIframe = false,
	iframeProps,
	contentRef,
	className,
} ) {
	const {
		renderingMode,
		wrapperBlockName,
		wrapperUniqueId,
		deviceType,
		isFocusedEntity,
		layout,
	} = useSelect( ( select ) => {
		const {
			getCurrentPostId,
			getCurrentPostType,
			getEditorSettings,
			// @ts-expect-error No types for this exist yet.
			getRenderingMode,
			// @ts-expect-error No types for this exist yet.
			getDeviceType,
		} = select( editorStore );
		const postTypeSlug = getCurrentPostType();
		const checkRenderingMode = getRenderingMode();
		let checkWrapperBlockName;

		if ( postTypeSlug === PATTERN_POST_TYPE ) {
			checkWrapperBlockName = 'core/block';
		} else if ( checkRenderingMode === 'post-only' ) {
			checkWrapperBlockName = 'core/post-content';
		}

		const editorSettings = getEditorSettings();

		return {
			renderingMode: checkRenderingMode,
			isDesignPostType: DESIGN_POST_TYPES.includes( postTypeSlug ),
			// Post template fetch returns a 404 on classic themes, which
			// messes with e2e tests, so check it's a block theme first.
			wrapperBlockName: checkWrapperBlockName,
			wrapperUniqueId: getCurrentPostId(),
			deviceType: getDeviceType() as string,
			// @ts-expect-error No types for this exist yet.
			isFocusedEntity: !! editorSettings.onNavigateToPreviousEntityRecord,
			postType: postTypeSlug,
			// @ts-expect-error No types for this exist yet.
			// eslint-disable-next-line no-underscore-dangle
			isPreview: editorSettings.__unstableIsPreviewMode,
			// @ts-expect-error There are no types for the experimental features settings.
			// eslint-disable-next-line no-underscore-dangle
			layout: editorSettings.__experimentalFeatures.layout,
		};
	}, [] );

	const deviceStyles = useResizeCanvas( deviceType );

	// We want to use the same layout.
	const blockListLayout = layout;

	const localRef = useRef();
	const typewriterRef = useTypewriter();
	const newContentRef = useMergeRefs( [
		localRef,
		contentRef,
		renderingMode === 'post-only' ? typewriterRef : null,
		useSelectNearestEditableBlock( {
			isEnabled: renderingMode === 'template-locked',
		} ),
	] );

	const shouldIframe =
		! disableIframe || [ 'Tablet', 'Mobile' ].includes( deviceType );
	const containerWidth =
		deviceType === 'Desktop' ? ( layout.contentSize as string ) : '100%';

	const iframeStyles = [
		...( ( styles as string[] ) ?? [] ),
		{
			css: `.is-root-container{display:flow-root; width:${ containerWidth }; margin: 0 auto;box-sizing: border-box;}`,
		},
	];

	return (
		<div
			className={ classnames(
				'editor-visual-editor',
				// this class is here for backward compatibility reasons.
				'edit-post-visual-editor',
				className as string,
				{
					'has-padding': isFocusedEntity,
					'is-iframed': shouldIframe,
				}
			) }
		>
			<BlockCanvas
				shouldIframe={ shouldIframe }
				contentRef={ newContentRef }
				styles={ iframeStyles }
				height="100%"
				iframeProps={ {
					...iframeProps,
					style: {
						...iframeProps?.style,
						...deviceStyles,
					},
				} }
			>
				<RecursionProvider
					blockName={ wrapperBlockName }
					uniqueId={ wrapperUniqueId }
				>
					<BlockList
						className={ classnames(
							`is-${ deviceType.toLowerCase() }-preview`,
							'has-global-padding' // Ensures that padding is applied at the top level
						) }
						// @ts-expect-error No types for this exist yet.
						layout={ blockListLayout }
						dropZoneElement={
							// When iframed, pass in the html element of the iframe to
							// ensure the drop zone extends to the edges of the iframe.
							disableIframe
								? localRef.current // @ts-expect-error No types for this exist yet.
								: localRef.current?.parentNode
						}
						__unstableDisableDropZone={
							// In template preview mode, disable drop zones at the root of the template.
							renderingMode === 'template-locked'
						}
					/>
					{ renderingMode === 'template-locked' && (
						<EditTemplateBlocksNotification
							contentRef={ localRef }
						/>
					) }
				</RecursionProvider>
			</BlockCanvas>
		</div>
	);
}
