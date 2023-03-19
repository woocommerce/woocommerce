/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { PluginCardBody } from '~/marketing/components';
import { RegisteredChannel } from '~/marketing/types';
import { SyncStatus } from './SyncStatus';
import { IssueStatus } from './IssueStatus';
import './RegisteredChannelCardBody.scss';

type RegisteredChannelCardBodyProps = {
	registeredChannel: RegisteredChannel;
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
		<div className="woocommerce-marketing-registered-channel-description">
			{ !! registeredChannel.syncStatus && (
				<>
					<SyncStatus status={ registeredChannel.syncStatus } />
					<div className="woocommerce-marketing-registered-channel-description__separator" />
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
			className="woocommerce-marketing-registered-channel-card-body"
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
