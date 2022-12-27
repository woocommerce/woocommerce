/**
 * External dependencies
 */
import { useState, useEffect, useRef } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { CardBody, CardDivider, Button, Icon } from '@wordpress/components';
import { chevronUp, chevronDown } from '@wordpress/icons';
import { useLocation } from 'react-router-dom';

/**
 * Internal dependencies
 */
import { RecommendedChannel } from '~/marketing/types';
import {
	idAddChannels,
	hashAddChannels,
} from '~/marketing/overview-multichannel/constants';
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
	const location = useLocation();

	useEffect( () => {
		if ( buttonRef.current && location.hash === hashAddChannels ) {
			buttonRef.current.focus();
		}
	}, [ location.hash ] );

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
