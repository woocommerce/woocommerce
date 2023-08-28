/**
 * External dependencies
 */
import { registerPlugin } from '@wordpress/plugins';
import { WooOnboardingTaskListItem } from '@woocommerce/onboarding';
import { getAdminLink } from '@woocommerce/settings';

const CustomizeStoreTaskItem = () => (
	<WooOnboardingTaskListItem id="customize-store">
		{ ( {
			defaultTaskItem: DefaultTaskItem,
		}: {
			defaultTaskItem: ( props: { onClick: () => void } ) => JSX.Element;
		} ) => (
			<DefaultTaskItem
				onClick={ () => {
					// We need to use window.location.href instead of navigateTo because we need to initiate a full page refresh to ensure that all dependencies are loaded.
					window.location.href = getAdminLink(
						'admin.php?page=wc-admin&path=%2Fcustomize-store'
					);
				} }
			/>
		) }
	</WooOnboardingTaskListItem>
);

registerPlugin( 'woocommerce-admin-task-customize-store', {
	// @ts-expect-error scope is not defined in the type definition but it is a valid property
	scope: 'woocommerce-tasks',
	render: CustomizeStoreTaskItem,
} );
