/**
 * External dependencies
 */
import { useState, useEffect } from '@wordpress/element';
import { loadExperimentAssignment } from '@woocommerce/explat';

type Layout = 'control' | 'card' | 'stacked';

export const getProductLayoutExperiment = async (): Promise< Layout > => {
	const [ cardAssignment, stackedAssignment ] = await Promise.all( [
		loadExperimentAssignment( `woocommerce_products_task_layout_card_v3` ),
		loadExperimentAssignment(
			`woocommerce_products_task_layout_stacked_v3`
		),
	] );
	// This logic may look flawed as in both looks like they can be assigned treatment at the same time,
	// but in backend we segment the experiments by store country, so it will never be.
	if ( cardAssignment?.variationName === 'treatment' ) {
		return 'card';
	} else if ( stackedAssignment?.variationName === 'treatment' ) {
		return 'stacked';
	}
	return 'control';
};

export const isProductTaskExperimentTreatment =
	async (): Promise< boolean > => {
		return ( await getProductLayoutExperiment() ) !== 'control';
	};

export const useProductTaskExperiment = () => {
	const [ isLoading, setIsLoading ] = useState< boolean >( true );
	const [ experimentLayout, setExperimentLayout ] =
		useState< Layout >( 'control' );

	useEffect( () => {
		getProductLayoutExperiment().then( ( layout ) => {
			setExperimentLayout( layout );
			setIsLoading( false );
		} );
	}, [ setExperimentLayout ] );

	return { isLoading, experimentLayout };
};

export default useProductTaskExperiment;
