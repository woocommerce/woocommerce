/**
 * External dependencies
 */
// @ts-expect-error - We need to use this /wp see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-dataviews/#dataviews
import { View } from '@wordpress/dataviews/wp';
import { Post, useEntityRecords } from '@wordpress/core-data';
import { __ } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';
import { settingsStore } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import { EmailType } from './settings-email-listing-slotfill';

/**
 * Hook providing transactional emails enriched by woo_email post data for DataViews component.
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

	// Fetch email settings from the DB to get fresh statuses
	const { emailSettings } = useSelect( ( select ) => {
		const getSettings = select( settingsStore ).getSettings;
		return {
			emailSettings: emailTypes.reduce( ( acc, type ) => {
				const settingsId = `email_${ type.id }`;
				return {
					...acc,
					[ type.id ]: getSettings( settingsId ),
				};
			}, {} ),
		};
	}, [] );

	const emails = emailTypes.map( ( emailType ) => {
		const postId = postIdsMap.get( emailType.id ) || '';
		const post: Post | null = emailPosts.records?.find(
			( p ) => parseInt( p.id ) === parseInt( postId )
		) as Post | null;
		let status = emailType.enabled ? 'enabled' : 'disabled';
		if ( emailSettings[ emailType.id ]?.enabled === 'yes' ) {
			status = 'enabled';
		} else if ( emailSettings[ emailType.id ].enabled === 'no' ) {
			status = 'disabled';
		} else if ( emailType.manual ) {
			status = 'manual';
		}
		return {
			...emailType,
			link: post?.link || '',
			status,
		};
	} );

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
		total: filteredEmails.length,
	};
};
