/**
 * Internal dependencies
 */
import './inbox.scss';
import NotesPanel from '~/inbox-panel';
import { AbbreviatedNotificationsPanel } from './abbreviated-notifications-panel';
import { EmbeddedConnectNotificationBanner } from './embedded-connect-notification-banner';

export const InboxPanel = ( {
	hasAbbreviatedNotifications,
	thingsToDoNextCount,
} ) => {
	const handleLoaderStart = ( loadStart ) => {
		// Handle loading start if needed
	};

	const handleLoadError = ( error ) => {
		// Handle load error if needed
		console.error( 'WooCommerce Payments banner load error:', error );
	};

	const handleNotificationsChange = ( notifications ) => {
		// Handle notifications change if needed
	};

	return (
		<div className="woocommerce-notification-panels">
			<EmbeddedConnectNotificationBanner
				onLoaderStart={ handleLoaderStart }
				onLoadError={ handleLoadError }
				onNotificationsChange={ handleNotificationsChange }
				useSlotFill={ false }
			/>
			{ hasAbbreviatedNotifications && (
				<AbbreviatedNotificationsPanel
					thingsToDoNextCount={ thingsToDoNextCount }
				/>
			) }
			<NotesPanel showHeader={ false } />
		</div>
	);
};

export default InboxPanel;
