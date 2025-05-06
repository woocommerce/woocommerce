import { __, sprintf } from '@wordpress/i18n';
import { PanelRow, Button, Flex, FlexItem, Dropdown, Icon } from '@wordpress/components';
import { useState } from '@wordpress/element';
import { EMAIL_STATUSES } from '../../settings-email/settings-email-listing-status';

// TODO classes and styles

export function EmailStatus() {
	const [statusValue] = useState('enabled');
	const status = EMAIL_STATUSES.find((s) => s.value === statusValue) ?? EMAIL_STATUSES[1];

	return (
		<PanelRow className='editor-post-panel__row'>
			<Flex justify={ 'start' }>
				<FlexItem className="editor-post-panel__row-label">
					{ __( 'Email Status', 'woocommerce' ) }
				</FlexItem>
				<FlexItem>
					<Dropdown
						position="bottom left"
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
										disabled={option.value === statusValue}
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
