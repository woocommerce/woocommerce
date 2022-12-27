/**
 * External dependencies
 */
import { useLocation } from 'react-router-dom';

/**
 * Internal dependencies
 */
import { hashAddChannels } from '~/marketing/overview-multichannel/constants';

export const useIsLocationHashAddChannels = () => {
	const location = useLocation();

	return location.hash === hashAddChannels;
};
