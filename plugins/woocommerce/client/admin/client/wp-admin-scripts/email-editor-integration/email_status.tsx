import { __, sprintf } from '@wordpress/i18n';
import { PanelRow, Button, Flex, FlexItem, Dropdown, Icon } from '@wordpress/components';
import { useEntityProp } from '@wordpress/core-data';
import { EMAIL_STATUSES } from '../../settings-email/settings-email-listing-status';

export function EmailStatus() {
	const [ woocommerce_email_data ] = useEntityProp(
		'postType',
		'woo_email',
 		'woocommerce_data'
 	);
	const statusValue = woocommerce_email_data?.enabled ? 'enabled' : 'disabled';
	const isManual = woocommerce_email_data?.is_manual;

	const status = EMAIL_STATUSES.find((s) => s.value === statusValue) ?? EMAIL_STATUSES[1];

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
						renderContent={ () => (
							<div style={{ minWidth: 160 }}>
								{EMAIL_STATUSES.map((option) => (
									<Button
										key={option.value}
										variant="tertiary"
										className="editor-post-status__dropdown-item"
										icon={option.icon}
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
