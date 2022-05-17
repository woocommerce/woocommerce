/**
 * External dependencies
 */
import { createElement, useEffect } from '@wordpress/element';
import { recordEvent } from '@woocommerce/tracks';
import { Slot, Fill } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { getProductLayoutExperiment } from './use-product-layout-experiment';

export const trackView = async ( taskId ) => {
	const activePlugins = wp.data
		.select( 'wc/admin/plugins' )
		.getActivePlugins();

	const installedPlugins = wp.data
		.select( 'wc/admin/plugins' )
		.getInstalledPlugins();

	const isJetpackConnected = wp.data
		.select( 'wc/admin/plugins' )
		.isJetpackConnected();

	recordEvent( 'task_view', {
		task_name: taskId,
		variant: await getProductLayoutExperiment(),
		wcs_installed: installedPlugins.includes( 'woocommerce-services' ),
		wcs_active: activePlugins.includes( 'woocommerce-services' ),
		jetpack_installed: installedPlugins.includes( 'jetpack' ),
		jetpack_active: activePlugins.includes( 'jetpack' ),
		jetpack_connected: isJetpackConnected,
	} );
};

/**
 * A Fill for adding Onboarding tasks.
 *
 * @slotFill WooOnboardingTask
 * @scope woocommerce-tasks
 * @param {Object} props    React props.
 * @param {string} props.id Task id.
 */
const WooOnboardingTask = ( { id, ...props } ) => {
	return <Fill name={ 'woocommerce_onboarding_task_' + id } { ...props } />;
};

WooOnboardingTask.Slot = ( { id, fillProps } ) => {
	// The Slot is a React component and this hook works as expected.
	// eslint-disable-next-line react-hooks/rules-of-hooks
	useEffect( () => {
		trackView( id );
	}, [ id ] );

	return (
		<Slot
			name={ 'woocommerce_onboarding_task_' + id }
			fillProps={ fillProps }
		/>
	);
};

export { WooOnboardingTask };
