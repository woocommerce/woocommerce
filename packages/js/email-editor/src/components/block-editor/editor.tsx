/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';
import { PostLockedModal } from '@wordpress/editor';
import { useMemo } from '@wordpress/element';
import { SlotFillProvider, Spinner } from '@wordpress/components';
import { store as coreStore } from '@wordpress/core-data';

/**
 * Internal dependencies
 */
import { storeName } from '../../store';
import { useNavigateToEntityRecord } from '../../hooks/use-navigate-to-entity-record';
import { Editor } from '../../private-apis';
import { useEmailCss } from '../../hooks';
import { TemplateSelection } from '../template-select';
import { StylesSidebar } from '../styles-sidebar';

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
		} ),
		[
			settings,
			onNavigateToEntityRecord,
			onNavigateToPreviousEntityRecord,
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
				<StylesSidebar />
			</Editor>
		</SlotFillProvider>
	);
}
