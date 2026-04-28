/**
 * External dependencies
 */
import { useState, useMemo, useCallback } from '@wordpress/element';
import { edit, external } from '@wordpress/icons';
import { Icon, Modal, TextControl, Button, Notice } from '@wordpress/components';
import { getAdminLink } from '@woocommerce/settings';
import { __, sprintf } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
// @ts-expect-error - We need to use this /wp see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-dataviews/#dataviews
import { DataViews, View } from '@wordpress/dataviews/wp'; // eslint-disable-line @woocommerce/dependency-group

/**
 * Internal dependencies
 */
import { EmailType } from './settings-email-listing-slotfill';
import { useTransactionalEmails } from './settings-email-listing-data';
import { Status, EMAIL_STATUSES } from './settings-email-listing-status';
import { RecipientsList } from './settings-email-listing-recipients';

type SendTestEmailResponse = {
	send_to: string;
	subject: string;
};

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

	const [ testModalEmail, setTestModalEmail ] = useState< EmailType | null >(
		null
	);
	const [ testSendTo, setTestSendTo ] = useState< string >( '' );
	const [ isSendingTest, setIsSendingTest ] = useState< boolean >( false );
	const [ testNotice, setTestNotice ] = useState<
		{ type: 'success' | 'error'; message: string } | null
	>( null );

	const openTestModal = useCallback( ( email: EmailType ) => {
		setTestModalEmail( email );
		setTestSendTo( window?.wcSettings?.currentUserData?.email ?? '' );
		setTestNotice( null );
	}, [] );

	const closeTestModal = useCallback( () => {
		setTestModalEmail( null );
		setTestNotice( null );
	}, [] );

	const handleSendTestEmail = useCallback( async () => {
		if ( ! testModalEmail ) {
			return;
		}
		setIsSendingTest( true );
		setTestNotice( null );
		try {
			await apiFetch< SendTestEmailResponse >( {
				path: `/wc/v4/settings/emails/${ testModalEmail.id }/test`,
				method: 'POST',
				data: { send_to: testSendTo },
			} );
			setTestNotice( {
				type: 'success',
				message: sprintf(
					/* translators: %s: recipient email address */
					__( 'Test email sent to %s.', 'woocommerce' ),
					testSendTo
				),
			} );
		} catch ( e ) {
			const err = e as { message?: string };
			setTestNotice( {
				type: 'error',
				message:
					err?.message ??
					__(
						'Failed to send test email. Please check your email delivery settings.',
						'woocommerce'
					),
			} );
		} finally {
			setIsSendingTest( false );
		}
	}, [ testModalEmail, testSendTo ] );

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
				supportsBulk: false,
				callback: ( items: EmailType[] ) => {
					openTestModal( items[ 0 ] );
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
		<>
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
			{ testModalEmail && (
				<Modal
					title={ __( 'Send test email', 'woocommerce' ) }
					onRequestClose={ closeTestModal }
					size="medium"
				>
					<p>
						{ sprintf(
							/* translators: %s: email template name */
							__(
								'Send a test version of "%s" to the address below.',
								'woocommerce'
							),
							testModalEmail.title
						) }
					</p>
					{ testNotice && (
						<Notice
							status={ testNotice.type }
							isDismissible={ false }
						>
							{ testNotice.message }
						</Notice>
					) }
					<TextControl
						label={ __( 'Send to', 'woocommerce' ) }
						value={ testSendTo }
						onChange={ setTestSendTo }
						type="email"
						__nextHasNoMarginBottom
					/>
					<div className="woocommerce-email-test-modal__actions">
						<Button
							variant="primary"
							onClick={ handleSendTestEmail }
							isBusy={ isSendingTest }
							disabled={ isSendingTest || ! testSendTo }
						>
							{ __( 'Send test email', 'woocommerce' ) }
						</Button>
						<Button
							variant="tertiary"
							onClick={ closeTestModal }
							disabled={ isSendingTest }
						>
							{ __( 'Cancel', 'woocommerce' ) }
						</Button>
					</div>
				</Modal>
			) }
		</>
	);
};
