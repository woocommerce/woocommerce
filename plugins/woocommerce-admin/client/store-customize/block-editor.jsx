/**
 * External dependencies
 */
import classnames from 'classnames';
import { useRef, useMemo } from '@wordpress/element';
import {
	useResizeObserver,
	useMergeRefs,
	useViewportMatch,
} from '@wordpress/compose';
import { useSelect } from '@wordpress/data';
import ResizableEditor from '@wordpress/edit-site/build-module/components/block-editor/resizable-editor';
import EditorCanvas from '@wordpress/edit-site/build-module/components/block-editor/editor-canvas';
import {
	BlockEditorProvider,
	BlockList,
	BlockTools,
} from '@wordpress/block-editor';
import { store as coreStore } from '@wordpress/core-data';
/**
 * Internal dependencies
 */

const LAYOUT = {
	type: 'default',
	// At the root level of the site editor, no alignments should be allowed.
	alignments: [],
};

const useSettings = ( { templateType } ) => {
	const { restBlockPatterns, restBlockPatternCategories } = useSelect(
		( select ) => ( {
			restBlockPatterns: select( coreStore ).getBlockPatterns(),
			restBlockPatternCategories:
				select( coreStore ).getBlockPatternCategories(),
		} ),
		[]
	);

	const storedSettings = window.blockSettings;

	const settingsBlockPatterns =
		storedSettings.__experimentalAdditionalBlockPatterns ?? // WP 6.0
		storedSettings.__experimentalBlockPatterns; // WP 5.9
	const settingsBlockPatternCategories =
		storedSettings.__experimentalAdditionalBlockPatternCategories ?? // WP 6.0
		storedSettings.__experimentalBlockPatternCategories; // WP 5.9

	const blockPatterns = useMemo(
		() =>
			[
				...( settingsBlockPatterns || [] ),
				...( restBlockPatterns || [] ),
			]
				.filter(
					( x, index, arr ) =>
						index === arr.findIndex( ( y ) => x.name === y.name )
				)
				.filter( ( { postTypes } ) => {
					return (
						! postTypes ||
						( Array.isArray( postTypes ) &&
							postTypes.includes( templateType ) )
					);
				} ),
		[ settingsBlockPatterns, restBlockPatterns, templateType ]
	);

	const blockPatternCategories = useMemo(
		() =>
			[
				...( settingsBlockPatternCategories || [] ),
				...( restBlockPatternCategories || [] ),
			].filter(
				( x, index, arr ) =>
					index === arr.findIndex( ( y ) => x.name === y.name )
			),
		[ settingsBlockPatternCategories, restBlockPatternCategories ]
	);

	const settings = useMemo( () => {
		const {
			__experimentalAdditionalBlockPatterns,
			__experimentalAdditionalBlockPatternCategories,
			...restStoredSettings
		} = storedSettings;

		return {
			...restStoredSettings,
			// inserterMediaCategories,
			__experimentalBlockPatterns: blockPatterns,
			__experimentalBlockPatternCategories: blockPatternCategories,
		};
	}, [ storedSettings, blockPatterns, blockPatternCategories ] );

	return settings;
};

export default function BlockEditor( { blocks, template } ) {
	const [ resizeObserver ] = useResizeObserver();
	const contentRef = useRef();
	const isMobileViewport = useViewportMatch( 'small', '<' );
	const enableResizing =
		// Disable resizing in mobile viewport.
		! isMobileViewport;

	const mergedRefs = useMergeRefs( [ contentRef ] );
	const settings = useSettings( {
		templateType: template?.type,
	} );
	return (
		<BlockEditorProvider
			settings={ settings }
			value={ blocks }
			onInput={ () => {} }
			onChange={ () => {} }
			useSubRegistry={ false }
		>
			<BlockTools
				className={ classnames( 'edit-site-visual-editor', {
					'is-focus-mode': false,
					'is-view-mode': true,
				} ) }
				__unstableContentRef={ contentRef }
			>
				<ResizableEditor
					enableResizing={ enableResizing }
					height={ '100%' }
				>
					<EditorCanvas
						enableResizing={ enableResizing }
						settings={ settings }
						contentRef={ mergedRefs }
						readonly={ true }
					>
						{ resizeObserver }
						<BlockList
							className="edit-site-block-editor__block-list wp-site-blocks"
							__experimentalLayout={ LAYOUT }
							renderAppender={ false }
						/>
					</EditorCanvas>
				</ResizableEditor>
			</BlockTools>
		</BlockEditorProvider>
	);
}
