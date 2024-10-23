/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';
import { ONBOARDING_STORE_NAME } from '@woocommerce/data';

export const useActiveSetupTasklist = () => {
	const { activeSetuplist } = useSelect( ( select ) => {
		const taskLists = select( ONBOARDING_STORE_NAME ).getTaskLists();

		const visibleSetupList = taskLists.filter(
			( list ) => list.id === 'setup' && list.isVisible
		);

		return {
			activeSetuplist: visibleSetupList.length
				? visibleSetupList[ 0 ].id
				: null,
		};
	} );

	return activeSetuplist;
};
