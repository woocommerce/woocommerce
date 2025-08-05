/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { select, dispatch } from '@wordpress/data';
import {
	PanelRow,
	Button,
	Flex,
	FlexItem,
	Dropdown,
	RadioControl,
} from '@wordpress/components';
import { closeSmall } from '@wordpress/icons';
import { store as coreDataStore, useEntityProp } from '@wordpress/core-data';

/**
 * Internal dependencies
 */
import { EMAIL_STATUSES } from '../../settings-email/settings-email-listing-status';

interface EmailStatusProps {
	className?: string;
	recordEvent: ( name: string, data?: Record< string, unknown > ) => void;
}

/**
 * EmailStatus component displays and allows changing the status of a transactional email.
 * It shows whether the email is enabled, disabled, or manually sent.
 *
 * @param {EmailStatusProps} props - Component props.
 * @return {JSX.Element} Rendered component.
 */
export function EmailStatus( {
	className,
	recordEvent,
}: EmailStatusProps ): JSX.Element {
	const [ woocommerce_email_data ] = useEntityProp(
		'postType',
		'woo_email',
		'woocommerce_data'
	);

	const isManual = woocommerce_email_data?.is_manual;
	let statusValue = 'enabled';
	if ( isManual ) {
		statusValue = 'manual';
	} else if ( ! woocommerce_email_data?.enabled ) {
		statusValue = 'disabled';
	}

	const status =
		EMAIL_STATUSES.find( ( s ) => s.value === statusValue ) ??
		EMAIL_STATUSES[ 1 ];

	const updateStatus = ( newValue: boolean ): void => {
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
					enabled: newValue,
				},
			}
		);
		recordEvent( 'email_status_changed', {
			status: newValue ? 'active' : 'inactive',
		} );
	};

	const renderDropdownContent = ( {
		onClose,
	}: {
		onClose: () => void;
	} ): JSX.Element => (
		<div style={ { minWidth: 230 } }>
			<Flex
				justify="space-between"
				align="center"
				style={ {
					padding: '8px 0',
				} }
			>
				<h2
					className="block-editor-inspector-popover-header__heading"
					style={ { margin: 0 } }
				>
					{ __( 'Status', 'woocommerce' ) }
				</h2>
				<Button
					size="small"
					className="block-editor-inspector-popover-header__action"
					label={ __( 'Close', 'woocommerce' ) }
					icon={ closeSmall }
					onClick={ onClose }
				/>
			</Flex>
			<RadioControl
				selected={ statusValue }
				options={ EMAIL_STATUSES.filter(
					( option ) => option.value !== 'manual'
				).map( ( option ) => ( {
					label: option.label,
					value: option.value,
					description: option.description,
				} ) ) }
				onChange={ ( value ) => {
					updateStatus( value === 'enabled' );
					onClose();
				} }
				disabled={ isManual }
			/>
		</div>
	);

	return (
		<PanelRow className={ className }>
			<Flex justify="start">
				<FlexItem className="editor-post-panel__row-label">
					{ __( 'Email Status', 'woocommerce' ) }
				</FlexItem>
				<FlexItem>
					<Dropdown
						popoverProps={ {
							placement: 'bottom-start',
							offset: 0,
							shift: true,
						} }
						renderToggle={ ( { isOpen, onToggle } ) => (
							<Button
								variant="tertiary"
								className="editor-post-status__toggle"
								icon={ status.icon }
								size="compact"
								onClick={ onToggle }
								aria-label={ sprintf(
									// translators: %s: Current post status.
									__( 'Change status: %s', 'woocommerce' ),
									status.label
								) }
								aria-expanded={ isOpen }
								disabled={ isManual }
							>
								{ status.label }
							</Button>
						) }
						renderContent={ renderDropdownContent }
					/>
				</FlexItem>
			</Flex>
		</PanelRow>
	);
}
