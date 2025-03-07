/**
 * External dependencies
 */
import { View } from '@wordpress/dataviews/wp';
import { Post, useEntityRecords } from '@wordpress/core-data';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { EmailType } from './settings-email-listing-slotfill';

/**
 * Hook providing transactional emails enriched by woo_email post data,
 * and the available statuses of the posts.
 */
export const useTransactionalEmails = (
	emailTypes: EmailType[],
	view: View
) => {
	const postIdsMap = new Map< string, string >();
	emailTypes.forEach( ( emailType ) => {
		postIdsMap.set( emailType.id, emailType.post_id );
	} );

	const validPostIds = Array.from( postIdsMap.values() ).filter( Boolean );
	const emailPosts = useEntityRecords( 'postType', 'woo_email', {
		include: validPostIds.join( ',' ),
		per_page: -1,
		status: 'any',
	} );

	const emails = emailTypes.map( ( emailType ) => {
		const postId = postIdsMap.get( emailType.id ) || '';
		const post: Post | null = emailPosts.records?.find(
			( p ) => parseInt( p.id ) === parseInt( postId )
		) as Post | null;
		return {
			...emailType,
			status: post?.status || 'draft',
		};
	} );

	const statuses = Array.from(
		new Set( emails.map( ( email ) => email.status ) )
	).map( ( status ) => ( {
		value: status,
		label: __(
			status.charAt( 0 ).toUpperCase() + status.slice( 1 ),
			'woocommerce'
		),
	} ) );

	return {
		emails,
		statuses,
	};
};
