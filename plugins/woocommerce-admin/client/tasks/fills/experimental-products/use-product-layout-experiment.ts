/**
 * External dependencies
 */
import { useState, useEffect } from '@wordpress/element';

type Layout = 'control' | 'card' | 'stacked';

export const getProductLayoutExperiment = async (): Promise< Layout > => {
	// Deploy the stacked layout. Todo: cleanup the experiment code.
	return 'stacked';
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
