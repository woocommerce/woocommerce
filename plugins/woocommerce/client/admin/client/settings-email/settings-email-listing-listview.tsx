/**
 * External dependencies
 */
import { DataViews, View } from '@wordpress/dataviews/wp';
import { Post, store as coreStore } from '@wordpress/core-data';
import { useSelect } from '@wordpress/data';
import { useState } from '@wordpress/element';
import { edit, external } from '@wordpress/icons';
import { Icon } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { EmailType } from './settings-email-listing-slotfill';
import { useTransactionalEmails } from './settings-email-listing-data';
import { Status } from './settings-email-listing-status';

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

	const { emails, statuses, total } = useTransactionalEmails( emailTypes, view );

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
				return <Status slug={ row.item.status } />;
			},
			elements: statuses,
		},
	];

	const actions = [
		{
			id: 'preview',
			label: __( 'Preview', 'woocommerce' ),
			icon: <Icon icon={ external } />,
			supportsBulk: false,
			callback: ( items: EmailType[] ) => {
				window.open( items[ 0 ].link );
			},
			isPrimary: true,
		},
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
				totalItems: total,
				totalPages: Math.ceil( total / view.perPage ),
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
