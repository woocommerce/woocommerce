/**
 * External dependencies
 */
import { Fragment } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { CardDivider } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { SmartPluginCardBody } from '~/marketing/components';
import { RecommendedChannel } from '~/marketing/data-multichannel/types';
import './Channels.scss';

type RecommendedChannelListPropsType = {
	recommendedChannels: Array< RecommendedChannel >;
	onInstalledAndActivated?: () => void;
};

export const RecommendedChannelsList: React.FC<
	RecommendedChannelListPropsType
> = ( { recommendedChannels, onInstalledAndActivated } ) => {
	return (
		<>
			{ recommendedChannels.map( ( el, idx ) => {
				return (
					<Fragment key={ el.plugin }>
						<SmartPluginCardBody
							plugin={ el }
							onInstalledAndActivated={ onInstalledAndActivated }
						/>
						{ idx < recommendedChannels.length - 1 && (
							<CardDivider />
						) }
					</Fragment>
				);
			} ) }
		</>
	);
};
