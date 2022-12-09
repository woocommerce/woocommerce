/**
 * External dependencies
 */
import { CardBody } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { InstalledChannel } from '~/marketing/types';
import './InstalledChannelCardBody.scss';

type InstalledChannelCardBodyProps = {
	installedChannel: InstalledChannel;
};

export const InstalledChannelCardBody: React.FC<
	InstalledChannelCardBodyProps
> = ( { installedChannel } ) => {
	return (
		<CardBody className="woocommerce-marketing-installed-channel-card-body">
			InstalledChannelCardBody
		</CardBody>
	);
};
