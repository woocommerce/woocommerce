/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { registerPlugin } from '@wordpress/plugins';
import { Fill as NotificationFill } from '@wordpress/components';
import { AbbreviatedCard } from '@woocommerce/components';
import { page } from '@wordpress/icons';
import { Text } from '@woocommerce/experimental';

const MyAbbreviatedNotification = () => {
	return (
		<NotificationFill name="AbbreviatedNotification">
			<AbbreviatedCard
				className="woocommerce-abbreviated-notification"
				icon={ page }
				href={ '#' }
			>
				<Text as="h3">
					{ __(
						'Abbreviated Notification Example',
						'plugin-domain'
					) }
				</Text>
				<Text>
					{ __( 'This is an unread notifications', 'plugin-domain' ) }
				</Text>
			</AbbreviatedCard>
		</NotificationFill>
	);
};

registerPlugin( 'my-abbreviated-notification', {
	render: MyAbbreviatedNotification,
} );
