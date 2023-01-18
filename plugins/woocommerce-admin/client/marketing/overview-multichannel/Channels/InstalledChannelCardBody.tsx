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

type IssueStatusPropsType = {
	installedChannel: InstalledChannel;
};

const issueStatusClassName = 'woocommerce-marketing-issue-status';

const IssueStatus: React.FC< IssueStatusPropsType > = ( {
	installedChannel,
} ) => {
	if ( installedChannel.issueType === 'error' ) {
		return (
			<div
				className={ classnames(
					issueStatusClassName,
					`${ issueStatusClassName }__error`
				) }
			>
				<GridiconNotice size={ iconSize } />
				{ installedChannel.issueText }
			</div>
		);
	}

	if ( installedChannel.issueType === 'warning' ) {
		return (
			<div
				className={ classnames(
					issueStatusClassName,
					`${ issueStatusClassName }__warning`
				) }
			>
				<GridiconNotice size={ iconSize } />
				{ installedChannel.issueText }
			</div>
		);
	}

	return (
		<div className={ issueStatusClassName }>
			{ installedChannel.issueText }
		</div>
	);
};

export const InstalledChannelCardBody: React.FC<
	InstalledChannelCardBodyProps
> = ( { installedChannel } ) => {
	/**
	 * The description section in the channel card.
	 *
	 * If setup is not completed, this would be the channel description.
	 *
	 * If setup is completed, this would be an element with sync status and issue status.
	 */
	const description = ! installedChannel.isSetupCompleted ? (
		installedChannel.description
	) : (
		<div className="woocommerce-marketing-installed-channel-description">
			{ installedChannel.syncStatus && (
				<>
					<SyncStatus status={ installedChannel.syncStatus } />
					<div className="woocommerce-marketing-installed-channel-description__separator" />
				</>
			) }
			<IssueStatus installedChannel={ installedChannel } />
		</div>
	);

	/**
	 * The action button in the channel card.
	 *
	 * If setup is not completed, this would be a "Finish setup" primary button.
	 *
	 * If setup is completed, this would be a "Manage" secondary button.
	 */
	const button = ! installedChannel.isSetupCompleted ? (
		<Button variant="primary" href={ installedChannel.setupUrl }>
			{ __( 'Finish setup', 'woocommerce' ) }
		</Button>
	) : (
		<Button variant="secondary" href={ installedChannel.manageUrl }>
			{ __( 'Manage', 'woocommerce' ) }
		</Button>
	);

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
			description={ description }
			button={ button }
		/>
	);
};
