/**
 * External dependencies
 */
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { CardBody, CardDivider, Button, Icon } from '@wordpress/components';
import { chevronUp, chevronDown } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import { RecommendedChannel } from '~/marketing/types';
import { RecommendedChannelsList } from './RecommendedChannelsList';
import './CollapsibleRecommendedChannels.scss';

type RecommendedChannelsType = {
	recommendedChannels: Array< RecommendedChannel >;
};

export const CollapsibleRecommendedChannels: React.FC<
	RecommendedChannelsType
> = ( { recommendedChannels } ) => {
	const [ collapsed, setCollapsed ] = useState( true );

	return (
		<div className="woocommerce-marketing-recommended-channels">
			<CardDivider />
			<CardBody>
				<Button
					variant="link"
					onClick={ () => setCollapsed( ! collapsed ) }
				>
					{ __( 'Add channels', 'woocommerce' ) }
					<Icon
						icon={ collapsed ? chevronDown : chevronUp }
						size={ 24 }
					/>
				</Button>
			</CardBody>
			{ ! collapsed && (
				<RecommendedChannelsList
					recommendedChannels={ recommendedChannels }
				/>
			) }
		</div>
	);
};
