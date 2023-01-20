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
import { RegisteredChannel, SyncStatusType } from '~/marketing/types';
import './RegisteredChannelCardBody.scss';

type RegisteredChannelCardBodyProps = {
	registeredChannel: RegisteredChannel;
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
	registeredChannel: RegisteredChannel;
};

const issueStatusClassName = 'woocommerce-marketing-issue-status';

const IssueStatus: React.FC< IssueStatusPropsType > = ( {
	registeredChannel,
} ) => {
	if ( registeredChannel.issueType === 'error' ) {
		return (
			<div
				className={ classnames(
					issueStatusClassName,
					`${ issueStatusClassName }__error`
				) }
			>
				<GridiconNotice size={ iconSize } />
				{ registeredChannel.issueText }
			</div>
		);
	}

	if ( registeredChannel.issueType === 'warning' ) {
		return (
			<div
				className={ classnames(
					issueStatusClassName,
					`${ issueStatusClassName }__warning`
				) }
			>
				<GridiconNotice size={ iconSize } />
				{ registeredChannel.issueText }
			</div>
		);
	}

	return (
		<div className={ issueStatusClassName }>
			{ registeredChannel.issueText }
		</div>
	);
};

export const RegisteredChannelCardBody: React.FC<
	RegisteredChannelCardBodyProps
> = ( { registeredChannel } ) => {
	/**
	 * The description section in the channel card.
	 *
	 * If setup is not completed, this would be the channel description.
	 *
	 * If setup is completed, this would be an element with sync status and issue status.
	 */
	const description = ! registeredChannel.isSetupCompleted ? (
		registeredChannel.description
	) : (
		<div className="woocommerce-marketing-installed-channel-description">
			{ registeredChannel.syncStatus && (
				<>
					<SyncStatus status={ registeredChannel.syncStatus } />
					<div className="woocommerce-marketing-installed-channel-description__separator" />
				</>
			) }
			<IssueStatus registeredChannel={ registeredChannel } />
		</div>
	);

	/**
	 * The action button in the channel card.
	 *
	 * If setup is not completed, this would be a "Finish setup" primary button.
	 *
	 * If setup is completed, this would be a "Manage" secondary button.
	 */
	const button = ! registeredChannel.isSetupCompleted ? (
		<Button variant="primary" href={ registeredChannel.setupUrl }>
			{ __( 'Finish setup', 'woocommerce' ) }
		</Button>
	) : (
		<Button variant="secondary" href={ registeredChannel.manageUrl }>
			{ __( 'Manage', 'woocommerce' ) }
		</Button>
	);

	return (
		<PluginCardBody
			className="woocommerce-marketing-installed-channel-card-body"
			icon={
				<img
					src={ registeredChannel.icon }
					alt={ registeredChannel.title }
				/>
			}
			name={ registeredChannel.title }
			description={ description }
			button={ button }
		/>
	);
};
