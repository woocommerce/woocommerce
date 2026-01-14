/**
 * External dependencies
 */
import { getAdminLink } from '@woocommerce/settings';
import { __ } from '@wordpress/i18n';
import { useSettings, useUserPreferences } from '@woocommerce/data';
import { createInterpolateElement } from '@wordpress/element';
import { Button } from '@wordpress/components';
import { recordEvent } from '@woocommerce/tracks';

const SCHEDULED_IMPORT_OPTION = 'woocommerce_analytics_scheduled_import';

export default function ScheduledUpdatesPromotionNotice() {
	// Get settings to check option value (hooks must be called before early returns)
	const settings = useSettings( 'wc_admin', [ 'wcAdminSettings' ] );
	const wcAdminSettings = (
		settings as { wcAdminSettings?: Record< string, string > }
	 )?.wcAdminSettings;

	const { updateUserPreferences, ...userData } = useUserPreferences();

	// Check if feature flag is enabled
	if ( ! window.wcAdminFeatures?.[ 'analytics-scheduled-import' ] ) {
		return null;
	}

	const optionValue = wcAdminSettings?.[ SCHEDULED_IMPORT_OPTION ];
	// No need to show notice if option is already set.
	if ( optionValue === 'yes' || optionValue === 'no' ) {
		return null;
	}

	const isDismissed =
		userData?.scheduled_updates_promotion_notice_dismissed === 'yes';

	if ( isDismissed ) {
		return null;
	}

	const onDismiss = () => {
		updateUserPreferences( {
			scheduled_updates_promotion_notice_dismissed: 'yes',
		} );
		recordEvent( 'scheduled_updates_promotion_notice_dismissed' );
	};

	return (
		<div className="notice notice-info is-dismissible">
			<Button
				variant="tertiary"
				aria-label={ __( 'Dismiss this notice.', 'woocommerce' ) }
				className="woocommerce-message-close notice-dismiss"
				onClick={ onDismiss }
			/>

			<p>
				{ createInterpolateElement(
					/* translators: <a> is a link to the analytics settings page. */
					__(
						'Analytics now supports scheduled updates, providing improved performance. Enable it in <a>Settings</a>.',
						'woocommerce'
					),
					{
						a: (
							<a
								href={ getAdminLink(
									'admin.php?page=wc-admin&path=/analytics/settings'
								) }
								aria-label={ __(
									'Analytics settings',
									'woocommerce'
								) }
							/>
						),
					}
				) }
			</p>
		</div>
	);
}
