/**
 * External dependencies
 */
import { useState, useEffect } from '@wordpress/element';
import { loadExperimentAssignment } from '@woocommerce/explat';
import { addFilter } from '@wordpress/hooks';
import moment from 'moment';

export const useHeadercardExperimentHook = (
	installTimestampHasResolved,
	installTimestamp
) => {
	const [ isLoadingExperimentAssignment, setIsLoadingExperimentAssignment ] =
		useState( true );
	const [
		isLoadingTwoColExperimentAssignment,
		setIsLoadingTwoColExperimentAssignment,
	] = useState( true );
	const [ experimentAssignment, setExperimentAssignment ] = useState( null );
	const [ twoColExperimentAssignment, setTwoColExperimentAssignment ] =
		useState( null );

	useEffect( () => {
		if ( installTimestampHasResolved && installTimestamp ) {
			addFilter(
				'woocommerce_explat_request_args',
				'woocommerce-admin',
				function ( args ) {
					if (
						args.experiment_name?.indexOf(
							'woocommerce_tasklist_progression_headercard_'
						) > -1
					) {
						args.woo_wcadmin_install_timestamp = installTimestamp;
					}
					return args;
				}
			);

			const momentDate = moment().utc();
			const year = momentDate.format( 'YYYY' );
			const month = momentDate.format( 'MM' );
			loadExperimentAssignment(
				`woocommerce_tasklist_progression_headercard_${ year }_${ month }`
			).then( ( assignment ) => {
				setExperimentAssignment( assignment );
				setIsLoadingExperimentAssignment( false );
			} );
			loadExperimentAssignment(
				`woocommerce_tasklist_progression_headercard_2col_${ year }_${ month }`
			).then( ( assignment ) => {
				setTwoColExperimentAssignment( assignment );
				setIsLoadingTwoColExperimentAssignment( false );
			} );
		} else if ( installTimestampHasResolved ) {
			// Cases when install timestamp is resolved but it's null. Should be impossible.
			// Set loading to false so that we don't wait indefinitely.
			setIsLoadingExperimentAssignment( false );
			setIsLoadingTwoColExperimentAssignment( false );
		}
	}, [ installTimestampHasResolved, installTimestamp ] );
	return {
		isLoadingExperimentAssignment,
		isLoadingTwoColExperimentAssignment,
		experimentAssignment,
		twoColExperimentAssignment,
	};
};
