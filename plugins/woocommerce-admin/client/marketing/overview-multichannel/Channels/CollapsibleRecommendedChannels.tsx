/**
 * External dependencies
 */
import { Fragment, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { CardBody, CardDivider, Button, Icon } from '@wordpress/components';
import { chevronUp, chevronDown } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import { SmartPluginCardBody } from '~/marketing/components';
import { RecommendedChannel } from './types';
import './Channels.scss';

type RecommendedChannelsType = {
	recommendedChannels: Array< RecommendedChannel >;
};

export const CollapsibleRecommendedChannels: React.FC<
	RecommendedChannelsType
> = ( { recommendedChannels } ) => {
	const [ collapsed, setCollapsed ] = useState( true );

	return (
		<>
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
			{ ! collapsed &&
				recommendedChannels.map( ( el, idx ) => {
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
