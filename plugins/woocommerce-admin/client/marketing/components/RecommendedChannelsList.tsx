/**
 * External dependencies
 */
import { Fragment } from '@wordpress/element';
import { CardDivider } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { SmartPluginCardBody } from '~/marketing/components';
import { RecommendedChannel } from '~/marketing/types';

type RecommendedChannelListPropsType = {
	recommendedChannels: Array< RecommendedChannel >;
};

export const RecommendedChannelsList: React.FC<
	RecommendedChannelListPropsType
> = ( { recommendedChannels } ) => {
	return (
		<>
			{ recommendedChannels.map( ( el, idx ) => {
				return (
					<Fragment key={ el.plugin }>
						<SmartPluginCardBody plugin={ el } />
						{ idx < recommendedChannels.length - 1 && (
							<CardDivider />
						) }
					</Fragment>
				);
			} ) }
		</>
	);
};
