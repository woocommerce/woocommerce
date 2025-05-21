/**
 * Internal dependencies
 */
import { OnboardingState } from './types';
import { WPDataSelector, WPDataSelectors } from '../types';

export const getOnboardingData = ( state: OnboardingState ): OnboardingState =>
	state;

export const isOnboardingDataRequestPending = (
	state: OnboardingState
): boolean => state.isFetching;

export const getOnboardingDataError = ( state: OnboardingState ): unknown =>
	state.errors.getOnboardingData;

export type WooPaymentsOnboardingSelectors = {
	getOnboardingData: WPDataSelector< typeof getOnboardingData >;
	isOnboardingDataRequestPending: WPDataSelector<
		typeof isOnboardingDataRequestPending
	>;
	getOnboardingDataError: WPDataSelector< typeof getOnboardingDataError >;
} & WPDataSelectors;
