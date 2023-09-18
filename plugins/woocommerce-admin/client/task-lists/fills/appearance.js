/**
 * External dependencies
 */
import React from 'react';
import { WooOnboardingTaskListItem } from '@woocommerce/onboarding';
import { useDispatch } from '@wordpress/data';
import { registerPlugin } from '@wordpress/plugins';
import { getAdminLink } from '@woocommerce/settings';

const useAppearanceClick = () => {
	const { actionTask } = useDispatch( 'wc/admin/onboarding' );
	const onClick = () => {
		actionTask( 'appearance' );
		window.location = getAdminLink( 'themes.php' );
	};

	return { onClick };
};

const AppearanceFill = () => {
	const { onClick } = useAppearanceClick();
	return (
		<WooOnboardingTaskListItem id="appearance">
			{ ( { defaultTaskItem: DefaultTaskItem } ) => (
				<DefaultTaskItem
					// Override task click so it doesn't navigate to a task component.
					onClick={ onClick }
				/>
			) }
		</WooOnboardingTaskListItem>
	);
};

registerPlugin( 'wc-admin-onboarding-task-appearance', {
	scope: 'woocommerce-tasks',
	render: () => <AppearanceFill />,
} );
