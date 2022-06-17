/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';
import { ONBOARDING_STORE_NAME } from '@woocommerce/data';

export const useActiveSetupTasklist = () => {
	const { activeSetuplist } = useSelect( ( select ) => {
		const taskLists = select( ONBOARDING_STORE_NAME ).getTaskLists();

		const visibleSetupList = taskLists
			.filter( ( list ) => list.isVisible )
			.filter( ( list ) =>
				[
					'setup_experiment_1',
					'setup_experiment_2',
					'setup',
				].includes( list.id )
			);

		return {
			activeSetuplist: visibleSetupList.length
				? visibleSetupList[ 0 ].id
				: null,
		};
	} );

	return activeSetuplist;
};
