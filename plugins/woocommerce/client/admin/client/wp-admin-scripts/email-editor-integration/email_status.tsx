import { __, sprintf } from '@wordpress/i18n';
import { PanelRow, Button, Flex, FlexItem } from '@wordpress/components';
import { useState } from '@wordpress/element';
import { EMAIL_STATUSES } from '../../settings-email/settings-email-listing-status';

// TODO classes and styles

export function EmailStatus() {
	const isOpen = false; // TODO
	const [statusValue] = useState('enabled');
	const status = EMAIL_STATUSES.find((s) => s.value === statusValue) ?? EMAIL_STATUSES[1];
	function onToggle() {
		// TODO
	}

	return (
		<PanelRow
			className='editor-post-panel__row'
		>
			<Flex justify={ 'start' }>
				<FlexItem className="editor-post-panel__row-label">
					{ __( 'Email Status', 'woocommerce' ) }
				</FlexItem>
				<FlexItem>
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
				</FlexItem>
			</Flex>
		</PanelRow>
	);
}
