/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';
import {
	ErrorBoundary,
	PostLockedModal,
	EditorProvider,
} from '@wordpress/editor';
import { useMemo } from '@wordpress/element';
import { SlotFillProvider, Spinner } from '@wordpress/components';
import { store as coreStore } from '@wordpress/core-data';
import { Post } from '@wordpress/core-data/build-types/entity-types/post';

/**
 * Internal dependencies
 */
import { storeName } from '../../store';
import { Layout } from './layout';
import { useNavigateToEntityRecord } from '../../hooks/use-navigate-to-entity-record';
import { unlockPatternsRelatedSelectorsFromCoreStore } from '../../private-apis';

export function InnerEditor( {
	postId: initialPostId,
	postType: initialPostType,
	settings,
	initialEdits,
	...props
} ) {
	const {
		currentPost,
		onNavigateToEntityRecord,
		onNavigateToPreviousEntityRecord,
	} = useNavigateToEntityRecord(
		// eslint-disable-next-line @typescript-eslint/no-unsafe-argument
		initialPostId,
		// eslint-disable-next-line @typescript-eslint/no-unsafe-argument
		initialPostType,
		'post-only'
	);

	const { post, template } = useSelect(
		( select ) => {
			const { getEntityRecord } = select( coreStore );
			const { getEditedPostTemplate } = select( storeName );
			const postObject = getEntityRecord(
				'postType',
				// eslint-disable-next-line @typescript-eslint/no-unsafe-argument
				currentPost.postType,
				// eslint-disable-next-line @typescript-eslint/no-unsafe-argument
				currentPost.postId
			);
			return {
				template:
					currentPost.postType !== 'wp_template'
						? getEditedPostTemplate()
						: null,
				post: postObject,
			};
		},
		[ currentPost.postType, currentPost.postId ]
	);

	/*
	 * We need to fetch patterns ourselves. Automatic fetching of patterns is currently a private functionality
	 * that is not available in EditorProvider but only in the ExperimentalBlockEditorProvider which
	 * is not exported in the public components nor private components.
	 */
	const blockPatterns = useSelect(
		( select ) => {
			const { hasFinishedResolution, getBlockPatternsForPostType } =
				unlockPatternsRelatedSelectorsFromCoreStore( select );
			const patterns = getBlockPatternsForPostType(
				currentPost.postType
			) as Post[];
			return hasFinishedResolution( 'getBlockPatterns' )
				? patterns
				: undefined;
		},
		[ currentPost.postType ]
	);

	const editorSettings = useMemo(
		// eslint-disable-next-line @typescript-eslint/no-unsafe-return
		() => ( {
			...settings,
			onNavigateToEntityRecord,
			onNavigateToPreviousEntityRecord,
			defaultRenderingMode: 'template-locked',
			supportsTemplateMode: true,
			__experimentalBlockPatterns: blockPatterns, // eslint-disable-line
		} ),
		[
			settings,
			onNavigateToEntityRecord,
			onNavigateToPreviousEntityRecord,
			blockPatterns,
		]
	);

	if ( ! post || ( currentPost.postType !== 'wp_template' && ! template ) ) {
		return (
			<div className="spinner-container">
				<Spinner style={ { width: '80px', height: '80px' } } />
			</div>
		);
	}

	return (
		<SlotFillProvider>
			<EditorProvider
				settings={ editorSettings }
				post={ post }
				initialEdits={ initialEdits }
				useSubRegistry={ false }
				// @ts-expect-error __unstableTemplate is not in the EditorProvider props in the installed version of packages
				__unstableTemplate={ template }
				{ ...props }
			>
				{ /* @ts-expect-error ErrorBoundary type is incorrect there is no onError */ }
				<ErrorBoundary>
					<Layout />
					<PostLockedModal />
				</ErrorBoundary>
			</EditorProvider>
		</SlotFillProvider>
	);
}
