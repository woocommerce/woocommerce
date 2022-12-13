/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import GridiconCheckmarkCircle from 'gridicons/dist/checkmark-circle';
import GridiconSync from 'gridicons/dist/sync';
import GridiconNotice from 'gridicons/dist/notice';
import classnames from 'classnames';

/**
 * Internal dependencies
 */
import { PluginCardBody } from '~/marketing/components';
import { InstalledChannel, SyncStatusType } from '~/marketing/types';
import './InstalledChannelCardBody.scss';

type InstalledChannelCardBodyProps = {
	installedChannel: InstalledChannel;
};

type SyncStatusPropsType = {
	status: SyncStatusType;
};

const iconSize = 18;
const className = 'woocommerce-marketing-sync-status';

const SyncStatus: React.FC< SyncStatusPropsType > = ( { status } ) => {
	if ( status === 'failed' ) {
		return (
			<div
				className={ classnames( className, `${ className }__failed` ) }
			>
				<GridiconNotice size={ iconSize } />
				{ __( 'Sync failed', 'woocommerce' ) }
			</div>
		);
	}

	if ( status === 'syncing' ) {
		return (
			<div
				className={ classnames( className, `${ className }__syncing` ) }
			>
				<GridiconSync size={ iconSize } />
				{ __( 'Syncing', 'woocommerce' ) }
			</div>
		);
	}

	return (
		<div className={ classnames( className, `${ className }__synced` ) }>
			<GridiconCheckmarkCircle size={ iconSize } />
			{ __( 'Synced', 'woocommerce' ) }
		</div>
	);
};

export const InstalledChannelCardBody: React.FC<
	InstalledChannelCardBodyProps
> = ( { installedChannel } ) => {
	return (
		<PluginCardBody
			className="woocommerce-marketing-installed-channel-card-body"
			icon={
				<img
					src={ installedChannel.icon }
					alt={ installedChannel.title }
				/>
			}
			name={ installedChannel.title }
			description={
				<div>
					<SyncStatus status={ installedChannel.syncStatus } />
				</div>
			}
			button={
				<Button variant="secondary" href={ installedChannel.manageUrl }>
					{ __( 'Manage', 'woocommerce' ) }
				</Button>
			}
		/>
	);
};
