/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '~/marketing/data/constants';
import { Post } from './types';

export const useBlogPosts = ( category: string ) => {
	return useSelect(
		( select ) => {
			const { getBlogPosts, getBlogPostsError, isResolving } =
				select( STORE_KEY );

			return {
				isLoading: isResolving( 'getBlogPosts', [ category ] ),
				error: getBlogPostsError( category ),
				posts: getBlogPosts< Post[] >( category ),
			};
		},
		[ category ]
	);
};
