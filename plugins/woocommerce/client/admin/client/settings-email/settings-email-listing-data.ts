/**
 * External dependencies
 */
import { Post, useEntityRecords } from '@wordpress/core-data';
import { useDispatch, select, subscribe } from '@wordpress/data';
import { settingsStore } from '@woocommerce/data';
import { useState, useCallback, useMemo } from '@wordpress/element';
// @ts-expect-error - We need to use this /wp see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-dataviews/#dataviews
import { View } from '@wordpress/dataviews/wp'; // eslint-disable-line @woocommerce/dependency-group

/**
 * Internal dependencies
 */
import { EmailType, EmailStatus } from './settings-email-listing-slotfill';

/**
 * Hook providing transactional emails enriched by woo_email post data for DataViews component.
 */
export const useTransactionalEmails = (
	emailTypes: EmailType[],
	view: View
) => {
	const [ emailTypesData, setEmailTypesData ] =
		useState< EmailType[] >( emailTypes );
	const postIdsMap = useMemo( () => {
		const map = new Map< string, string >();
		emailTypesData.forEach( ( emailType: EmailType ) => {
			map.set( emailType.id, emailType.post_id );
		} );
		return map;
	}, [ emailTypesData ] );

	const validPostIds = Array.from( postIdsMap.values() ).filter( Boolean );
	const emailPosts = useEntityRecords( 'postType', 'woo_email', {
		include: validPostIds.join( ',' ),
		per_page: -1,
		status: 'any',
	} ) as { records: Post[] };

	const { updateAndPersistSettingsForGroup } = useDispatch( settingsStore );

	const emails = useMemo(
		() =>
			emailTypesData.map( ( emailType: EmailType ) => {
				const postId = postIdsMap.get( emailType.id ) || '';
				const post: Post | null = emailPosts.records?.find(
					( p ) => p.id === parseInt( postId, 10 )
				) as Post | null;
				let status = emailType.enabled ? 'enabled' : 'disabled';
				if ( emailType.manual ) {
					status = 'manual';
				}
				return {
					...emailType,
					link: post?.link || '',
					status: status as EmailStatus,
				};
			} ),
		[ emailTypesData, emailPosts, postIdsMap ]
	);

	// Apply Sort
	let sortedEmails: EmailType[] = emails;
	if ( view.sort ) {
		sortedEmails = sortedEmails.sort( ( a, b ) => {
			const field = view.sort.field as keyof EmailType;
			if (
				a[ field ] === undefined ||
				b[ field ] === undefined ||
				typeof a[ field ] !== 'string' ||
				typeof b[ field ] !== 'string'
			) {
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

	const updateEmailEnabledStatusInState = useCallback(
		( emailId: string, value: boolean ) => {
			const updatedEmailTypesData = [ ...emailTypesData ];
			const emailIndex = updatedEmailTypesData.findIndex(
				( email ) => email.id === emailId
			);
			if ( emailIndex !== -1 ) {
				updatedEmailTypesData[ emailIndex ].enabled = value;
				setEmailTypesData( updatedEmailTypesData );
			}
		},
		[ emailTypesData ]
	);
	const updateEmailEnabledStatus = useCallback(
		async ( emailId: string, value: boolean ) => {
			// Optimistic update of local state to update UI
			updateEmailEnabledStatusInState( emailId, value );

			try {
				// Store the new status via API
				const enabled = value ? 'yes' : 'no';
				const settingsGroup = `email_${ emailId }`;

				// The email settings has to be stored in DB complete.
				// In case the settings are not available in the store, we need to fetch them via API and wait for the resolution to complete.
				if (
					! select( settingsStore ).hasFinishedResolution(
						'getSettings',
						[ settingsGroup ]
					)
				) {
					await select( settingsStore ).getSettings( settingsGroup );
					await new Promise( ( resolve ) => {
						const unsubscribe = subscribe( () => {
							const finished = select(
								settingsStore
							).hasFinishedResolution( 'getSettings', [
								settingsGroup,
							] );
							if ( finished ) {
								unsubscribe();
								resolve( true );
							}
						} );
					} );
				}

				// Now we can fetch the old settings and update the settings
				const currentSettings = await select(
					settingsStore
				).getSettings( settingsGroup );
				const updatedSettings = { ...currentSettings } as {
					[ key: string ]: { [ key: string ]: unknown };
				};
				updatedSettings[ settingsGroup ].enabled = enabled;
				await updateAndPersistSettingsForGroup(
					settingsGroup,
					updatedSettings
				);
			} catch ( error ) {
				// Switch the UI status back on error
				updateEmailEnabledStatusInState( emailId, ! value );
				// Log error to help with debugging email status update failures
				// eslint-disable-next-line no-console
				console.error( error );
			}
		},
		[ emailTypesData, updateAndPersistSettingsForGroup ]
	);

	return {
		emails: renderedEmails,
		total: filteredEmails.length,
		updateEmailEnabledStatus,
	};
};
