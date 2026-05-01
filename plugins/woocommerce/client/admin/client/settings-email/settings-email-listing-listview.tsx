/**
 * External dependencies
 */
import { useState, useMemo } from '@wordpress/element';
import { edit, external } from '@wordpress/icons';
import { Icon } from '@wordpress/components';
import { getAdminLink } from '@woocommerce/settings';
import { __ } from '@wordpress/i18n';
// @ts-expect-error - We need to use this /wp see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-dataviews/#dataviews
import { DataViews, View } from '@wordpress/dataviews/wp'; // eslint-disable-line @woocommerce/dependency-group

/**
 * Internal dependencies
 */
import { EmailType } from './settings-email-listing-slotfill';
import { useTransactionalEmails } from './settings-email-listing-data';
import { Status, EMAIL_STATUSES } from './settings-email-listing-status';
import { RecipientsList } from './settings-email-listing-recipients';
import { UpdatesCell } from './settings-email-listing-update-cell';
import { UpdateAvailableChip } from './settings-email-listing-update-chip';

export const ListView = ( { emailTypes }: { emailTypes: EmailType[] } ) => {
	const [ view, setView ] = useState< View >( {
		type: 'table',
		search: '',
		fields: [ 'recipients', 'status', 'updates' ],
		filters: [],
		page: 1,
		perPage: 20,
		titleField: 'title',
		showTitle: true,
		layout: {},
	} );

	const {
		emails,
		total,
		updateAvailableCount,
		updateEmailEnabledStatus,
		recreateEmailPost,
	} = useTransactionalEmails( emailTypes, view );

	// RSM-140 — true when the "Updates" filter is currently set to "available".
	// Handles both the scalar shape (`value: 'available'`) the chip writes and
	// the array shape that DataView's "+ Add filter" menu may produce.
	const isUpdateFilterActive = view.filters.some( ( filter: View.Filter ) => {
		if ( filter.field !== 'updates' ) {
			return false;
		}
		if ( Array.isArray( filter.value ) ) {
			return ( filter.value as string[] ).includes( 'available' );
		}
		return filter.value === 'available';
	} );

	const toggleUpdateFilter = () => {
		setView( ( current: View ) => {
			const filtersWithoutUpdates = current.filters.filter(
				( filter: View.Filter ) => filter.field !== 'updates'
			);
			if ( isUpdateFilterActive ) {
				return { ...current, filters: filtersWithoutUpdates };
			}
			return {
				...current,
				filters: [
					...filtersWithoutUpdates,
					{
						field: 'updates',
						operator: 'is',
						value: 'available',
					},
				],
			};
		} );
	};

	const fields = useMemo( () => {
		const recipientElements = Array.from(
			emailTypes.reduce( ( acc, email ) => {
				const recipients = [
					...( email.recipients.to
						? email.recipients.to
								.split( ',' )
								.map( ( r ) => r.trim() )
								.filter( Boolean )
						: [] ),
					...( email.recipients.cc
						? email.recipients.cc
								.split( ',' )
								.map( ( r ) => r.trim() )
								.filter( Boolean )
						: [] ),
					...( email.recipients.bcc
						? email.recipients.bcc
								.split( ',' )
								.map( ( r ) => r.trim() )
								.filter( Boolean )
						: [] ),
				];
				recipients.forEach( ( recipient ) => acc.add( recipient ) );
				return acc;
			}, new Set< string >() )
		).map( ( recipient ) => ( { value: recipient, label: recipient } ) );

		return [
			{
				id: 'title',
				label: __( 'Title', 'woocommerce' ),
				enableHiding: false,
				render: ( row: { item: EmailType } ) => {
					return (
						<div className="woocommerce-email-listing-title">
							{ row.item.title }
							<br />
							<span className="woocommerce-email-listing-description">
								{ row.item.description }
							</span>
						</div>
					);
				},
			},
			{
				id: 'recipients',
				label: __( 'Recipient(s)', 'woocommerce' ),
				enableHiding: true,
				filterBy: {
					operators: [ 'isAny' ],
				},
				elements: recipientElements,
				render: ( row: { item: EmailType } ) => {
					return (
						<RecipientsList recipients={ row.item.recipients } />
					);
				},
			},
			{
				id: 'status',
				label: __( 'Status', 'woocommerce' ),
				enableHiding: true,
				filterBy: {
					operators: [ 'isAny' ],
				},
				render: ( row: { item: EmailType } ) => {
					return <Status slug={ row.item.status } />;
				},
				elements: EMAIL_STATUSES,
			},
			{
				id: 'updates',
				label: __( 'Updates', 'woocommerce' ),
				enableHiding: true,
				enableSorting: false,
				getValue: ( { item }: { item: EmailType } ) =>
					item.templateStatus === 'core_updated_customized'
						? 'available'
						: 'none',
				elements: [
					{
						value: 'available',
						label: __( 'Update available', 'woocommerce' ),
					},
					{
						value: 'none',
						label: __( 'Up to date', 'woocommerce' ),
					},
				],
				filterBy: {
					// RSM-140 — `isPrimary` is intentionally omitted: the
					// custom <UpdateAvailableChip> below replaces DataView's
					// auto-rendered chip so the design (sparkle icon, count
					// badge, hide-at-zero) can be matched. The filter is
					// still selectable via the "+ Add filter" menu.
					operators: [ 'is' ],
				},
				render: ( { item }: { item: EmailType } ) => (
					<UpdatesCell post={ item } />
				),
			},
		];
	}, [ emailTypes ] );

	const actions = useMemo(
		() => [
			{
				id: 'edit',
				label: __( 'Edit', 'woocommerce' ),
				icon: <Icon icon={ edit } />,
				supportsBulk: false,
				callback: ( items: EmailType[] ) => {
					const email = items[ 0 ];
					if ( email.post_id ) {
						window.location.href = getAdminLink(
							`post.php?post=${ encodeURIComponent(
								email.post_id
							) }&action=edit`
						);
					} else {
						window.location.href = getAdminLink(
							`admin.php?page=wc-settings&tab=email&section=${ encodeURIComponent(
								email.email_key
							) }`
						);
					}
				},
			},
			{
				id: 'preview',
				label: __( 'Preview', 'woocommerce' ),
				icon: <Icon icon={ external } />,
				supportsBulk: false,
				callback: ( items: EmailType[] ) => {
					window.open( items[ 0 ].link );
				},
				isEligible: ( item: EmailType ) => !! item.post_id,
				isPrimary: true,
			},
			{
				id: 'test',
				label: __( 'Send test email', 'woocommerce' ),
				disabled: true,
				supportsBulk: false,
				callback: () => {
					return true; // TODO: Implement send test email
				},
			},
			{
				id: 'change-status',
				label: ( items: EmailType[] ) =>
					items[ 0 ].status === 'enabled'
						? __( 'Deactivate email', 'woocommerce' )
						: __( 'Activate email', 'woocommerce' ),
				supportsBulk: false,
				isEligible: ( item: EmailType ) =>
					item.status === 'enabled' || item.status === 'disabled',
				callback: ( items: EmailType[] ) => {
					updateEmailEnabledStatus(
						items[ 0 ].id,
						! items[ 0 ].enabled
					);
				},
			},
			{
				id: 'recreate-email-post',
				label: __( 'Recreate email post', 'woocommerce' ),
				disabled: false,
				supportsBulk: false,
				isEligible: ( item: EmailType ) => ! item?.post_id,
				callback: ( items: EmailType[] ) => {
					void recreateEmailPost( items[ 0 ].id );
					return true;
				},
			},
		],
		[ updateEmailEnabledStatus, recreateEmailPost ]
	);

	const form = {
		type: 'panel',
		fields: [ 'title' ],
	};

	return (
		<div className="woocommerce-email-listing">
			<UpdateAvailableChip
				count={ updateAvailableCount }
				active={ isUpdateFilterActive }
				onClick={ toggleUpdateFilter }
			/>
			<DataViews
				view={ view }
				form={ form }
				actions={ actions }
				onChangeView={ setView }
				fields={ fields }
				data={ emails ?? [] }
				paginationInfo={ {
					totalItems: total,
					totalPages: Math.ceil( total / view.perPage ),
				} }
				defaultLayouts={ {
					table: {
						showMedia: false,
					},
				} }
				showLayoutSwitcher={ false }
				getItemId={ ( item: EmailType ) =>
					`${ item.id }_${ item?.email_key || '' }`
				}
			/>
		</div>
	);
};
