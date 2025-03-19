/**
 * External dependencies
 */
import { DataViews, View } from '@wordpress/dataviews/wp';
import { Post, store as coreStore } from '@wordpress/core-data';
import { useSelect } from '@wordpress/data';
import { useState } from '@wordpress/element';
import { edit } from '@wordpress/icons';
import { Icon } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { EmailType } from './settings-email-listing-slotfill';
import { useTransactionalEmails } from './settings-email-listing-data';

export const ListView = ( { emailTypes }: { emailTypes: EmailType[] } ) => {
	const [ view, setView ] = useState< View >( {
		type: 'table',
		search: '',
		fields: [ 'recipients', 'status' ],
		filters: [],
		page: 1,
		perPage: 20,
		titleField: 'title',
		showTitle: true,
		layout: {},
	} );

	const allStatuses = useSelect( ( select ) => {
		return select( coreStore ).getEntityRecords( 'root', 'status' ) || [];
	}, [] );

	const { emails, statuses } = useTransactionalEmails( emailTypes, view );
	const totalRecords = emails.length;

	const fields = [
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
			id: 'id',
			label: 'Id',
			enableHiding: false,
		},
		{
			id: 'recipients',
			label: __( 'Recipient(s)', 'woocommerce' ),
			enableHiding: false,
			render: ( row: { item: EmailType } ) => {
				return row.item.recipients || __( 'Customers', 'woocommerce' );
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
				return row.item.status;
			},
			elements: [
				{ value: 'draft', label: 'Draft' },
				{ value: 'sent', label: 'Sent' },
				{ value: 'active', label: 'Active' },
			],
		},
	];

	const actions = [
		{
			id: 'edit',
			label: __( 'Edit', 'woocommerce' ),
			icon: <Icon icon={ edit } />,
			supportsBulk: false,
			callback: ( items: EmailType[] ) => {
				window.location.href = `/wp-admin/post.php?post=${ items[ 0 ].post_id }&action=edit`;
			},
			isPrimary: true,
		},
	];

	const form = {
		type: 'panel',
		fields: [ 'title' ],
	};

	return (
		<DataViews
			view={ view }
			form={ form }
			actions={ actions }
			onChangeView={ setView }
			fields={ fields }
			data={ emails ?? [] }
			paginationInfo={ {
				totalItems: totalRecords,
				totalPages: Math.ceil( totalRecords / view.perPage ),
			} }
			defaultLayouts={ {
				table: {
					showMedia: false,
				},
			} }
			showLayoutSwitcher={ false }
			getItemId={ ( item: Post ) => item.id }
		/>
	);
};
