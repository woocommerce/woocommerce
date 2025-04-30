/**
 * External dependencies
 */
import { useState, memo } from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { BlockInstance, BlockEditProps } from '@wordpress/blocks';
import { Spinner } from '@wordpress/components';
import { store as coreStore } from '@wordpress/core-data';
import {
	// eslint-disable-next-line @typescript-eslint/ban-ts-comment
	// @ts-ignore No types for this exist yet.
	BlockContextProvider,
	useBlockProps,
	// @ts-expect-error no exported member.
	useInnerBlocksProps,
	store as blockEditorStore,
	// @ts-expect-error no exported member.
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalUseBlockPreview as useBlockPreview,
} from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import { useCommentQueryArgs, useCommentTree } from './hooks';

interface Comment {
	commentId: number;
	children?: Comment[];
}

interface ReviewTemplateInnerBlocksProps {
	comment: Comment;
	activeCommentId: number;
	setActiveCommentId: ( id: number ) => void;
	firstCommentId: number;
	blocks: BlockInstance[];
}

interface ReviewTemplatePreviewProps {
	blocks: BlockInstance[];
	commentId: number;
	setActiveCommentId: ( id: number ) => void;
	isHidden: boolean;
}

type ReviewTemplateAttributes = {
	postId?: number;
};

const TEMPLATE = [
	[ 'core/avatar' ],
	[ 'core/comment-reply-link' ],
	[ 'core/comment-edit-link' ],
	[ 'woocommerce/product-review-author-name' ],
	[ 'woocommerce/product-review-date' ],
	[ 'woocommerce/product-review-content' ],
];

interface ReviewSettings {
	perPage: number;
	pageComments: boolean;
	threadComments: boolean;
	threadCommentsDepth: number;
}

const getCommentsPlaceholder = ( {
	perPage,
	pageComments,
	threadComments,
	threadCommentsDepth,
}: ReviewSettings ) => {
	// Limit commentsDepth to 3
	const commentsDepth = ! threadComments
		? 1
		: Math.min( threadCommentsDepth, 3 );

	const buildChildrenComment = ( commentsLevel: number ): Comment[] => {
		// Render children comments until commentsDepth is reached
		if ( commentsLevel < commentsDepth ) {
			const nextLevel = commentsLevel + 1;

			return [
				{
					commentId: -( commentsLevel + 3 ),
					children: buildChildrenComment( nextLevel ),
				},
			];
		}
		return [];
	};

	// Add the first comment and its children
	const placeholderComments = [
		{ commentId: -1, children: buildChildrenComment( 1 ) },
	];

	// Add a second comment unless the break comments setting is active and set to less than 2, and there is one nested comment max
	if ( ( ! pageComments || perPage >= 2 ) && commentsDepth < 3 ) {
		placeholderComments.push( {
			commentId: -2,
			children: [],
		} );
	}

	// Add a third comment unless the break comments setting is active and set to less than 3, and there aren't nested comments
	if ( ( ! pageComments || perPage >= 3 ) && commentsDepth < 2 ) {
		placeholderComments.push( {
			commentId: -3,
			children: [],
		} );
	}

	// In case that the value is set but larger than 3 we truncate it to 3.
	return placeholderComments;
};

const ReviewTemplatePreview = ( {
	blocks,
	commentId,
	setActiveCommentId,
	isHidden,
}: ReviewTemplatePreviewProps ) => {
	const blockPreviewProps = useBlockPreview( {
		blocks,
	} );

	const handleOnClick = () => {
		setActiveCommentId( commentId );
	};

	const style = {
		display: isHidden ? 'none' : undefined,
	};

	return (
		<div
			{ ...blockPreviewProps }
			tabIndex={ 0 }
			role="button"
			style={ style }
			onClick={ handleOnClick }
			onKeyDown={ handleOnClick }
		/>
	);
};

const MemoizedReviewTemplatePreview = memo( ReviewTemplatePreview );

const ReviewTemplateInnerBlocks = memo( function ReviewTemplateInnerBlocks( {
	comment,
	activeCommentId,
	setActiveCommentId,
	firstCommentId,
	blocks,
}: ReviewTemplateInnerBlocksProps ) {
	const { children, ...innerBlocksProps } = useInnerBlocksProps(
		{},
		{ template: TEMPLATE }
	);

	return (
		<li { ...innerBlocksProps }>
			{ comment.commentId === ( activeCommentId || firstCommentId )
				? children
				: null }

			<MemoizedReviewTemplatePreview
				blocks={ blocks }
				commentId={ comment.commentId }
				setActiveCommentId={ setActiveCommentId }
				isHidden={
					comment.commentId === ( activeCommentId || firstCommentId )
				}
			/>

			{ comment?.children && comment.children.length > 0 ? (
				<ol>
					{ comment.children.map(
						(
							{ commentId, ...childComment }: Comment,
							index: number
						) => (
							<BlockContextProvider
								key={ commentId || index }
								value={ {
									commentId: commentId < 0 ? null : commentId,
								} }
							>
								<ReviewTemplateInnerBlocks
									comment={ { commentId, ...childComment } }
									activeCommentId={ activeCommentId }
									setActiveCommentId={ setActiveCommentId }
									blocks={ blocks }
									firstCommentId={ firstCommentId }
								/>
							</BlockContextProvider>
						)
					) }
				</ol>
			) : null }
		</li>
	);
} );

export default function ReviewTemplateEdit( {
	clientId,
	context: { postId },
}: BlockEditProps< ReviewTemplateAttributes > & {
	context: { postId: number };
} ) {
	const blockProps = useBlockProps();

	const [ activeCommentId, setActiveCommentId ] = useState< number >( 0 );
	const {
		commentOrder,
		threadCommentsDepth,
		threadComments,
		commentsPerPage,
		pageComments,
	} = useSelect( ( select ) => {
		const { getSettings } = select( blockEditorStore ) as unknown as {
			getSettings(): {
				// eslint-disable-next-line @typescript-eslint/naming-convention
				__experimentalDiscussionSettings: {
					commentOrder: string;
					threadCommentsDepth: number;
					threadComments: boolean;
					commentsPerPage: number;
					pageComments: boolean;
				};
			};
		};
		return getSettings().__experimentalDiscussionSettings;
	}, [] );

	const commentQuery = useCommentQueryArgs( {
		postId: postId ?? 0,
	} );

	const { topLevelComments, blocks } = useSelect(
		( select ) => {
			const { getEntityRecords } = select( coreStore );
			const { getBlocks } = select( blockEditorStore ) as unknown as {
				getBlocks( clientId: string ): BlockInstance[];
			};
			return {
				// Request only top-level comments. Replies are embedded.
				topLevelComments: commentQuery
					? getEntityRecords( 'root', 'comment', commentQuery )
					: null,
				blocks: getBlocks( clientId ),
			};
		},
		[ clientId, commentQuery ]
	);

	// Generate a tree structure of comment IDs.
	let commentTree = useCommentTree(
		// Reverse the order of top comments if needed.
		commentOrder === 'desc' && topLevelComments
			? [
					...( topLevelComments as Array< {
						id: number;
						// eslint-disable-next-line @typescript-eslint/naming-convention
						_embedded?: { children?: Array< { id: number } > };
					} > ),
			  ].reverse()
			: ( topLevelComments as Array< {
					id: number;
					// eslint-disable-next-line @typescript-eslint/naming-convention
					_embedded?: { children?: Array< { id: number } > };
			  } > )
	);

	if ( ! topLevelComments ) {
		return (
			<p { ...blockProps }>
				<Spinner />
			</p>
		);
	}

	if ( ! postId ) {
		commentTree = getCommentsPlaceholder( {
			perPage: commentsPerPage,
			pageComments,
			threadComments,
			threadCommentsDepth,
		} );
	}

	if ( ! commentTree.length ) {
		return (
			<p { ...blockProps }>
				{ __( 'No results found.', 'woocommerce' ) }
			</p>
		);
	}

	return (
		<ol { ...blockProps }>
			{ commentTree &&
				commentTree.map(
					(
						{
							commentId,
							...commentData
						}: {
							commentId: number;
							children: Comment[];
						},
						index: number
					) => (
						<BlockContextProvider
							key={ commentId || index }
							value={ {
								commentId: commentId < 0 ? null : commentId,
							} }
						>
							<ReviewTemplateInnerBlocks
								comment={ { commentId, ...commentData } }
								activeCommentId={ activeCommentId }
								setActiveCommentId={ setActiveCommentId }
								blocks={ blocks }
								firstCommentId={ commentTree[ 0 ]?.commentId }
							/>
						</BlockContextProvider>
					)
				) }
		</ol>
	);
}
