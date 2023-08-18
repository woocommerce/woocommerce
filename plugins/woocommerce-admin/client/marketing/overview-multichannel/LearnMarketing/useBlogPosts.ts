/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';
import { WPDataSelectors } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '~/marketing/data/constants';
import { Post } from './types';

export const useBlogPosts = ( category: string ) => {
	return useSelect(
		( select ) => {
			const {
				getBlogPosts,
				getBlogPostsError,
				isResolving,
			}: {
				getBlogPosts: < T >( category: string ) => T;
				getBlogPostsError: ( category: string ) => Error;
			} & WPDataSelectors = select( STORE_KEY );

			return {
				isLoading: isResolving( 'getBlogPosts', [ category ] ),
				error: getBlogPostsError( category ),
				posts: getBlogPosts< Post[] >( category ),
			};
		},
		[ category ]
	);
};
