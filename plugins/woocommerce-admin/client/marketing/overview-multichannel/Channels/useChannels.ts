/**
 * Internal dependencies
 */
import { useRecommendedChannels } from './useRecommendedChannels';
import { useRegisteredChannels } from './useRegisteredChannels';

export const useChannels = () => {
	const { loading: loadingRegistered, data: dataRegistered } =
		useRegisteredChannels();
	const { loading: loadingRecommended, data: dataRecommended } =
		useRecommendedChannels();

	return {
		loading: loadingRegistered || loadingRecommended,
		data: {
			registeredChannels: dataRegistered,
			recommendedChannels: dataRecommended,
		},
	};
};
