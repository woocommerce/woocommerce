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
import { useCommentQueryArgs, useCommentList } from './hooks';

interface Comment {
	commentId: number;
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
	[ 'woocommerce/product-review-author-name' ],
	[ 'woocommerce/product-review-date' ],
	[ 'woocommerce/product-review-content' ],
];

interface ReviewSettings {
	perPage: number;
	pageComments: boolean;
}

const getCommentsPlaceholder = ( {
	perPage,
	pageComments,
}: ReviewSettings ) => {
	const numberOfComments = pageComments ? Math.min( perPage, 3 ) : 3;

	return Array.from( { length: numberOfComments }, ( _, i ) => ( {
		commentId: -( i + 1 ),
	} ) );
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
	const { commentOrder, commentsPerPage, pageComments } = useSelect(
		( select ) => {
			const { getSettings } = select( blockEditorStore ) as unknown as {
				getSettings(): {
					// eslint-disable-next-line @typescript-eslint/naming-convention
					__experimentalDiscussionSettings: {
						commentOrder: string;
						commentsPerPage: number;
						pageComments: boolean;
					};
				};
			};
			return getSettings().__experimentalDiscussionSettings;
		},
		[]
	);

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
				topLevelComments: commentQuery
					? getEntityRecords( 'root', 'comment', commentQuery )
					: null,
				blocks: getBlocks( clientId ),
			};
		},
		[ clientId, commentQuery ]
	);

	let commentTree = useCommentList(
		// Reverse the order of top comments if needed.
		commentOrder === 'desc' && topLevelComments
			? [
					...( topLevelComments as Array< {
						id: number;
					} > ),
			  ].reverse()
			: ( topLevelComments as Array< {
					id: number;
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
						}: {
							commentId: number;
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
								comment={ { commentId } }
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
