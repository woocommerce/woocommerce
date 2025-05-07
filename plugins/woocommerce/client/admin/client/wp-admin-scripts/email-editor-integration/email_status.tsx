import { __, sprintf } from '@wordpress/i18n';
import { select, dispatch } from '@wordpress/data';
import { PanelRow, Button, Flex, FlexItem, Dropdown } from '@wordpress/components';
import { store as coreDataStore, useEntityProp } from '@wordpress/core-data';
import { EMAIL_STATUSES } from '../../settings-email/settings-email-listing-status';

// todo test it properly I am not sure if it works, we need to update the status in EmailApiController.php
// todo lint
// todo changelog
// todo the UI is a bit wonky, check if we have design prepared for this
// todo add a confirmation modal when disabling an email maybe

export function EmailStatus() {
	const [ woocommerce_email_data ] = useEntityProp(
		'postType',
		'woo_email',
 		'woocommerce_data'
 	);
	const statusValue = woocommerce_email_data?.enabled ? 'enabled' : 'disabled';
	const isManual = woocommerce_email_data?.is_manual;

	const status = EMAIL_STATUSES.find((s) => s.value === statusValue) ?? EMAIL_STATUSES[1];

	const updateStatus = ( newValue: boolean ) => {
		const editedPost = select( coreDataStore ).getEditedEntityRecord(
			'postType',
			'woo_email',
		   window.WooCommerceEmailEditor.current_post_id
	   );

		// @ts-expect-error Property 'woocommerce_data' does not exist on type 'Updatable<Attachment<any>>'.
		const woocommerce_data = editedPost?.woocommerce_data || {};
		void dispatch( coreDataStore ).editEntityRecord(
		   'postType',
		   'woo_email',
		   window.WooCommerceEmailEditor.current_post_id,
		   {
			   woocommerce_data: {
				   ...woocommerce_data,
				   'enabled': newValue,
			   },
		   }
	   );
   };

	return (
		<PanelRow className='editor-post-panel__row'>
			<Flex justify={ 'start' }>
				<FlexItem className="editor-post-panel__row-label">
					{ __( 'Email Status', 'woocommerce' ) }
				</FlexItem>
				<FlexItem>
					<Dropdown
						popoverProps={{ placement: 'bottom' }}
						renderToggle={ ( { isOpen, onToggle } ) => (
							<Button
								variant="tertiary"
								className="editor-post-status__toggle"
								icon={status.icon}
								size="compact"
								onClick={ onToggle }
								aria-label={ sprintf(
									// translators: %s: Current post status.
									__( 'Change status: %s' ),
									status.label
								) }
								aria-expanded={ isOpen }
								disabled={isManual}
							>
								{ status.label }
							</Button>
						)}
						renderContent={ ( { onClose } ) => (
							<div style={{ minWidth: 160 }}>
								{EMAIL_STATUSES.map((option) => (
									<Button
										key={option.value}
										variant="tertiary"
										className="editor-post-status__dropdown-item"
										icon={option.icon}
										onClick={() => {
											updateStatus( option.value === 'enabled' );
											onClose();
										}}
										disabled={
											isManual ||
											(option.value === 'manual' && !isManual) ||
											option.value === statusValue
										}
										style={{ width: '100%', justifyContent: 'flex-start' }}
									>
										{option.label}
									</Button>
								))}
							</div>
						)}
					/>
				</FlexItem>
			</Flex>
		</PanelRow>
	);
}
