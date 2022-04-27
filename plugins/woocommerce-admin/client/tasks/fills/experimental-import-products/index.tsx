/**
 * External dependencies
 */
import { WooOnboardingTask } from '@woocommerce/onboarding';
import { registerPlugin } from '@wordpress/plugins';

/**
 * Internal dependencies
 */
import CardList from './cardList';
import { importTypes } from './importTypes';

const Products = () => {
	return (
		<div>
			<h1>Import products</h1>
			<CardList items={ importTypes } />
		</div>
	);
};

registerPlugin( 'wc-admin-onboarding-task-products', {
	// @ts-expect-error 'scope' does exist. @types/wordpress__plugins is outdated.
	scope: 'woocommerce-tasks',
	render: () => (
		// @ts-expect-error WooOnboardingTask is a pure JS component.
		<WooOnboardingTask id="products">
			<Products />
		</WooOnboardingTask>
	),
} );
