/**
 * External dependencies
 */
import { useState, useEffect, useMemo } from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import { store as blockEditorStore } from '@wordpress/block-editor';
import { addQueryArgs } from '@wordpress/url';
import apiFetch from '@wordpress/api-fetch';

// This is limited by WP REST API
const MAX_COMMENTS_PER_PAGE = 100;

/**
 * Return an object with the query args needed to fetch the default page of
 * comments.
 */
export const useCommentQueryArgs = ( { postId }: { postId: number } ) => {
	// Initialize the query args that are not going to change.
	const queryArgs = useMemo(
		() => ( {
			status: 'approve',
			order: 'asc',
			context: 'embed',
			parent: 0,
			type: 'review',
		} ),
		[]
	);

	/**
	 * Return the index of the default page, depending on whether `defaultPage` is
	 * `newest` or `oldest`. In the first case, the only way to know the page's
	 * index is by using the `X-WP-TotalPages` header, which forces to make an
	 * additional request.
	 */
	const useDefaultPageIndex = ( {
		defaultPage,
		postId: commentPostId,
		perPage,
		queryArgs: defaultPageQueryArgs,
	}: {
		defaultPage: string;
		postId: number;
		perPage: number;
		queryArgs: Record< string, unknown >;
	} ) => {
		// Store the default page indices.
		const [ defaultPages, setDefaultPages ] = useState<
			Record< string, number >
		>( {} );
		const key = `${ commentPostId }_${ perPage }`;
		const page = defaultPages[ key ] || 0;

		useEffect( () => {
			// Do nothing if the page is already known or not the newest page.
			if ( page || defaultPage !== 'newest' ) {
				return;
			}
			// We need to fetch comments to know the index. Use HEAD and limit
			// fields just to ID, to make this call as light as possible.
			apiFetch( {
				path: addQueryArgs( '/wp/v2/comments', {
					...defaultPageQueryArgs,
					post: commentPostId,
					per_page: perPage,
					_fields: 'id',
				} ),
				method: 'HEAD',
				parse: false,
			} )
				.then( ( res ) => {
					const response = res as Response;
					const pages = parseInt(
						response.headers.get( 'X-WP-TotalPages' ) || '1',
						10
					);
					setDefaultPages( {
						...defaultPages,
						[ key ]: pages <= 1 ? 1 : pages, // If there are 0 pages, it means that there are no comments, but there is no 0th page.
					} );
				} )
				.catch( () => {
					// There's no 0th page, but we can't know the number of pages, fallback to 1.
					setDefaultPages( {
						...defaultPages,
						[ key ]: 1,
					} );
				} );
		}, [
			defaultPage,
			commentPostId,
			perPage,
			setDefaultPages,
			page,
			defaultPageQueryArgs,
			defaultPages,
			key,
		] );

		// The oldest one is always the first one.
		return defaultPage === 'newest' ? page : 1;
	};

	// Get the Discussion settings that may be needed to query the comments.
	const {
		pageComments,
		commentsPerPage,
		defaultCommentsPage: defaultPage,
	} = useSelect( ( select ) => {
		const { getSettings } = select( blockEditorStore ) as unknown as {
			getSettings(): {
				// eslint-disable-next-line @typescript-eslint/naming-convention
				__experimentalDiscussionSettings: {
					pageComments: boolean;
					commentsPerPage: number;
					defaultCommentsPage: string;
				};
			};
		};
		const { __experimentalDiscussionSettings } = getSettings();
		return __experimentalDiscussionSettings;
	}, [] );

	// WP REST API doesn't allow fetching more than max items limit set per single page of data.
	// As for the editor performance is more important than completeness of data and fetching only the
	// max allowed for single page should be enough for the purpose of design and laying out the page.
	// Fetching over the limit would return an error here but would work with backend query.
	const perPage = pageComments
		? Math.min( commentsPerPage, MAX_COMMENTS_PER_PAGE )
		: MAX_COMMENTS_PER_PAGE;

	// Get the number of the default page.
	const page = useDefaultPageIndex( {
		defaultPage,
		postId,
		perPage,
		queryArgs,
	} );

	// Merge, memoize and return all query arguments, unless the default page's
	// number is not known yet.
	return useMemo( () => {
		return page
			? {
					...queryArgs,
					post: postId,
					per_page: perPage,
					page,
			  }
			: null;
	}, [ page, queryArgs, postId, perPage ] );
};

/**
 * Generate a list of IDs from a list of review entities.
 */
export const useCommentList = (
	// eslint-disable-next-line @typescript-eslint/naming-convention
	topLevelComments: Array< {
		id: number;
	} >
) => {
	const commentList = useMemo(
		() =>
			topLevelComments?.map( ( { id }: { id: number } ) => {
				return {
					commentId: id,
				};
			} ),
		[ topLevelComments ]
	);

	return commentList;
};
