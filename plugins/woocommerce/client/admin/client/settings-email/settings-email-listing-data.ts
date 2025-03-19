/**
 * External dependencies
 */
// @ts-expect-error - We need to use this /wp see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-dataviews/#dataviews
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
			link: post?.link || '',
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

	// Apply Sort
	let sortedEmails: EmailType[] = emails;
	if ( view.sort ) {
		sortedEmails = sortedEmails.sort( ( a, b ) => {
			const field = view.sort.field as keyof EmailType;
			if ( a[ field ] === undefined || b[ field ] === undefined ) {
				return 0;
			}
			const direction = view.sort.direction === 'asc' ? 1 : -1;
			return direction * a[ field ].localeCompare( b[ field ] );
		} );
	}

	let filteredEmails: EmailType[] = [];
	// Apply search filter
	filteredEmails = sortedEmails.filter( ( email ) => {
		if ( ! view.search ) {
			return true;
		}
		return email.title.toLowerCase().includes( view.search.toLowerCase() );
	} );

	// Apply Filter
	filteredEmails = filteredEmails.filter( ( email ) => {
		const statusFilter = view.filters.find(
			( filter: View.Filter ) => filter.field === 'status'
		);
		if ( ! statusFilter || ! statusFilter.value ) {
			return true;
		}
		return statusFilter.value.includes( email.status );
	} );

	// Apply pagination
	const startIndex = ( view.page - 1 ) * view.perPage;
	const endIndex = startIndex + view.perPage;
	const renderedEmails = filteredEmails.slice( startIndex, endIndex );

	return {
		emails: renderedEmails,
		statuses,
		total: filteredEmails.length,
	};
};
