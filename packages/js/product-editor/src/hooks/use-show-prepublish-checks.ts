/**
 * External dependencies
 */
import { useSelect, useDispatch } from '@wordpress/data';
import { OPTIONS_STORE_NAME } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import { SHOW_PREPUBLISH_CHECKS_ENABLED_OPTION_NAME } from '../constants';

export function useShowPrepublishChecks() {
	const { updateOptions } = useDispatch( OPTIONS_STORE_NAME );

	const { isResolving, showPrepublishChecks } = useSelect( ( select ) => {
		const { getOption, hasFinishedResolution } =
			select( OPTIONS_STORE_NAME );

		const showPrepublishChecksOption =
			getOption( SHOW_PREPUBLISH_CHECKS_ENABLED_OPTION_NAME ) || 'yes';

		const resolving = ! hasFinishedResolution( 'getOption', [
			SHOW_PREPUBLISH_CHECKS_ENABLED_OPTION_NAME,
		] );

		return {
			isResolving: resolving,
			showPrepublishChecks: showPrepublishChecksOption === 'yes',
		};
	}, [] );

	const togglePrepublishChecks = () => {
		updateOptions( {
			[ SHOW_PREPUBLISH_CHECKS_ENABLED_OPTION_NAME ]: showPrepublishChecks
				? 'no'
				: 'yes',
		} );
	};

	return {
		isResolving,
		showPrepublishChecks,
		togglePrepublishChecks,
	};
}
