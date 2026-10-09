/**
 * External dependencies
 */
import { useState, useMemo } from '@wordpress/element';
import { dispatch } from '@wordpress/data';
import { pencil, external } from '@wordpress/icons';
import { Icon } from '@wordpress/components';
import { getAdminLink } from '@woocommerce/settings';
import { __ } from '@wordpress/i18n';
// @ts-expect-error - We need to use this /wp see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-dataviews/#dataviews
import { DataViews, View } from '@wordpress/dataviews/wp';

/**
 * Internal dependencies
 */
import { EmailType } from './settings-email-listing-slotfill';
import { useTransactionalEmails } from './settings-email-listing-data';
import { shouldShowReviewUpdate } from './settings-email-listing-update-state';
import { Status, EMAIL_STATUSES } from './settings-email-listing-status';
import { RecipientsList } from './settings-email-listing-recipients';
import { UpdatesCell } from './settings-email-listing-update-cell';
import {
	SendTestEmailForm,
	useSendTestEmail,
} from './settings-email-send-test';

/**
 * Send-test form for a listing row. A published post renders the saved
 * content; any other state (no post, unpublished scratchpad) sends by email
 * type and the server renders the file template — in both cases the test
 * email matches what customers receive.
 */
const SendTestEmailModalContent = ( {
	email,
	onClose,
}: {
	email: EmailType;
	onClose: () => void;
} ) => {
	const parsedPostId = parseInt( email.post_id, 10 );
	const postId =
		email.postStatus === 'publish' && Number.isFinite( parsedPostId )
			? parsedPostId
			: null;

	const {
		email: recipient,
		setEmail,
		isSending,
		notice,
		noticeType,
		sendEmail,
	} = useSendTestEmail(
		{
			endpoint: 'editor',
			postId,
			emailType: email.email_class_name,
			emailTypeId: email.id,
		},
		'email_listing'
	);

	return (
		<SendTestEmailForm
			email={ recipient }
			onEmailChange={ setEmail }
			isSending={ isSending }
			notice={ notice }
			noticeType={ noticeType }
			onSend={ sendEmail }
			onCancel={ onClose }
		/>
	);
};

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

	const { emails, total, updateEmailEnabledStatus, recreateEmailPost } =
		useTransactionalEmails( emailTypes, view );

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
					shouldShowReviewUpdate( item ) ? 'available' : 'none',
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
					operators: [ 'is' ],
					isPrimary: true,
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
				icon: <Icon icon={ pencil } />,
				supportsBulk: false,
				callback: async ( items: EmailType[] ) => {
					const email = items[ 0 ];
					if ( email.post_id ) {
						window.location.href = getAdminLink(
							`post.php?post=${ encodeURIComponent(
								email.post_id
							) }&action=edit`
						);
						return;
					}
					// Lazily create the post (a draft with the file
					// template content) and open it in the editor.
					const response = await recreateEmailPost( email.id );
					if ( response?.post_id ) {
						window.location.href = getAdminLink(
							`post.php?post=${ encodeURIComponent(
								response.post_id
							) }&action=edit`
						);
						return;
					}
					void dispatch( 'core/notices' ).createErrorNotice(
						__(
							'Could not prepare the email for editing. Please try again.',
							'woocommerce'
						)
					);
				},
			},
			{
				id: 'preview',
				label: __( 'Preview', 'woocommerce' ),
				icon: <Icon icon={ external } />,
				supportsBulk: false,
				// A published post previews through its permalink (the saved
				// content customers receive). Any other state — no post, or an
				// unpublished draft — previews the file template via the admin
				// preview page, matching what customers receive until the
				// email is saved.
				callback: ( items: EmailType[] ) => {
					const email = items[ 0 ];
					const isPublished =
						!! email.post_id && email.postStatus === 'publish';
					const previewUrl = isPublished
						? email.link
						: email.file_template_preview_url;
					if ( previewUrl ) {
						window.open( previewUrl );
					}
				},
				isEligible: ( item: EmailType ) =>
					( !! item.post_id &&
						item.postStatus === 'publish' &&
						!! item.link ) ||
					!! item.file_template_preview_url,
				isPrimary: true,
			},
			{
				id: 'test',
				label: __( 'Send test email', 'woocommerce' ),
				supportsBulk: false,
				modalHeader: __( 'Send a test email', 'woocommerce' ),
				RenderModal: ( {
					items,
					closeModal,
				}: {
					items: EmailType[];
					closeModal?: () => void;
				} ) => (
					<SendTestEmailModalContent
						email={ items[ 0 ] }
						onClose={ closeModal ?? ( () => {} ) }
					/>
				),
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
					void updateEmailEnabledStatus(
						items[ 0 ].id,
						! items[ 0 ].enabled
					);
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
	);
};
