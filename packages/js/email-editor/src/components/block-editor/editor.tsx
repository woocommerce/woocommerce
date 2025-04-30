/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';
import {
	PostLockedModal,
} from '@wordpress/editor';
import { useMemo } from '@wordpress/element';
import { SlotFillProvider, Spinner } from '@wordpress/components';
import { store as coreStore } from '@wordpress/core-data';
import { Post } from '@wordpress/core-data/build-types/entity-types/post';

/**
 * Internal dependencies
 */
import { storeName } from '../../store';
import { useNavigateToEntityRecord } from '../../hooks/use-navigate-to-entity-record';
import {
	Editor,
	unlockPatternsRelatedSelectorsFromCoreStore,
} from '../../private-apis';
import { useEmailCss } from '../../hooks';
import { TemplateSelection } from '../template-select';


export function InnerEditor( {
	postId: initialPostId,
	postType: initialPostType,
	settings,
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

	const [ styles ] = useEmailCss();

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
			defaultRenderingMode:
				currentPost.postType === 'wp_template'
					? 'post-only'
					: 'template-locked',
			supportsTemplateMode: true,
			__experimentalBlockPatterns: blockPatterns, // eslint-disable-line
		} ),
		[
			settings,
			onNavigateToEntityRecord,
			onNavigateToPreviousEntityRecord,
			blockPatterns,
			currentPost.postType,
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
			<Editor
				postId={ currentPost.postId }
				postType={ currentPost.postType }
				settings={ editorSettings }
				templateId={ template && template.id }
				styles={ styles }
			>
				<PostLockedModal />
				<TemplateSelection />
			</Editor>
		</SlotFillProvider>
	);
}
