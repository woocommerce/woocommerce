/** @format */

/**
 * Internal dependencies
 */
import items from './items';
import imports from './imports';
import notes from './notes';
import onboarding from './onboarding';
import reportItems from './reports/items';
import reportStats from './reports/stats';
import reviews from './reviews';
import settings from './settings';
import user from './user';

function createWcApiSpec() {
	return {
		name: 'wcApi',
		mutations: {
			...items.mutations,
			...notes.mutations,
			...onboarding.mutations,
			...settings.mutations,
			...user.mutations,
		},
		selectors: {
			...imports.selectors,
			...items.selectors,
			...notes.selectors,
			...onboarding.selectors,
			...reportItems.selectors,
			...reportStats.selectors,
			...reviews.selectors,
			...settings.selectors,
			...user.selectors,
		},
		operations: {
			read( resourceNames ) {
				return [
					...imports.operations.read( resourceNames ),
					...items.operations.read( resourceNames ),
					...notes.operations.read( resourceNames ),
					...onboarding.operations.read( resourceNames ),
					...reportItems.operations.read( resourceNames ),
					...reportStats.operations.read( resourceNames ),
					...reviews.operations.read( resourceNames ),
					...settings.operations.read( resourceNames ),
					...user.operations.read( resourceNames ),
				];
			},
			update( resourceNames, data ) {
				return [
					...items.operations.update( resourceNames, data ),
					...notes.operations.update( resourceNames, data ),
					...onboarding.operations.update( resourceNames, data ),
					...settings.operations.update( resourceNames, data ),
					...user.operations.update( resourceNames, data ),
				];
			},
		},
	};
}

export default createWcApiSpec();
