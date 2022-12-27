/**
 * External dependencies
 */
import { useState, useEffect, useRef } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { CardBody, CardDivider, Button, Icon } from '@wordpress/components';
import { chevronUp, chevronDown } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import { useIsLocationHashAddChannels } from '~/marketing/hooks';
import { RecommendedChannel } from '~/marketing/types';
import { idAddChannels } from '~/marketing/overview-multichannel/constants';
import { RecommendedChannelsList } from './RecommendedChannelsList';
import './RecommendedChannels.scss';

type RecommendedChannelsType = {
	recommendedChannels: Array< RecommendedChannel >;
};

export const RecommendedChannels: React.FC< RecommendedChannelsType > = ( {
	recommendedChannels,
} ) => {
	const [ collapsed, setCollapsed ] = useState( true );
	const buttonRef = useRef< HTMLAnchorElement >( null );
	const isLocationHashAddChannels = useIsLocationHashAddChannels();

	useEffect( () => {
		if ( buttonRef.current && isLocationHashAddChannels ) {
			buttonRef.current.focus();
		}
	}, [ isLocationHashAddChannels ] );

	return (
		<div className="woocommerce-marketing-recommended-channels">
			<CardDivider />
			<CardBody>
				<Button
					ref={ buttonRef }
					id={ idAddChannels }
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
