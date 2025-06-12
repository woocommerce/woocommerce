/**
 * External dependencies
 */
import { useMachine } from '@xstate5/react';
import { useEffect } from 'react';
import { useDispatch } from '@wordpress/data';
import { onboardingStore } from '@woocommerce/data';
import clsx from 'clsx';

/**
 * Internal dependencies
 */
import { useFullScreen } from '~/utils';
import { useComponentFromXStateService } from '~/utils/xstate/useComponentFromService';

import './styles.scss';
import {
	SidebarMachineEvents,
	sidebarMachine,
	SidebarComponentProps,
	SidebarContainer,
} from './sidebar/xstate';
import {
	MainContentMachineEvents,
	mainContentMachine,
	MainContentComponentProps,
	MainContentContainer,
} from './main-content/xstate';
import { useXStateInspect } from '~/xstate';
export type LaunchYourStoreComponentProps = {
	sendEventToSidebar: ( arg0: SidebarMachineEvents ) => void;
	sendEventToMainContent: ( arg0: MainContentMachineEvents ) => void;
	className?: string;
};
import { SetUpPaymentsProvider } from '../data/setup-payments-context';

export type LaunchYourStoreQueryParams = {
	sidebar?: 'hub' | 'launch-success';
	content?: 'site-preview' | 'launch-store-success' | 'payments';
};

const LaunchStoreController = () => {
	useFullScreen( [ 'woocommerce-launch-your-store' ] );
	useEffect( () => {
		window.sessionStorage.setItem( 'lysWaiting', 'no' );
	}, [] );
	const { xstateV5Inspector: inspect } = useXStateInspect( 'V5' );
	const { invalidateResolutionForStoreSelector } =
		useDispatch( onboardingStore );

	const [ mainContentState, sendToMainContent, mainContentMachineService ] =
		useMachine( mainContentMachine, {
			inspect,
		} );

	const [ sidebarState, sendToSidebar, sidebarMachineService ] = useMachine(
		sidebarMachine,
		{
			inspect,
			input: {
				mainContentMachineRef: mainContentMachineService,
			},
		}
	);

	const isSidebarVisible = ! sidebarState.hasTag( 'fullscreen' );

	const [ CurrentSidebarComponent ] =
		useComponentFromXStateService< SidebarComponentProps >(
			sidebarMachineService
		);

	const [ CurrentMainContentComponent ] =
		useComponentFromXStateService< MainContentComponentProps >(
			mainContentMachineService
		);

	const handlePaymentsClose = () => {
		// Clear session flag to prevent redirect back to payments setup
		// after exiting the flow and returning to the WC Admin home.
		window.sessionStorage.setItem( 'lysWaiting', 'no' );

		// Invalidate the task lists to ensure they are refreshed
		// when the user returns to the main flow.
		invalidateResolutionForStoreSelector( 'getTaskLists' );
		invalidateResolutionForStoreSelector( 'getTaskListsByIds' );

		// Navigate back to the main flow
		sendToSidebar( { type: 'RETURN_FROM_PAYMENTS' } );
	};

	return (
		<div className={ 'launch-your-store-layout__container' }>
			<SetUpPaymentsProvider closeModal={ handlePaymentsClose }>
				<SidebarContainer
					className={ clsx( {
						'is-sidebar-hidden': ! isSidebarVisible,
					} ) }
				>
					{ CurrentSidebarComponent && (
						<CurrentSidebarComponent
							sendEventToSidebar={ sendToSidebar }
							sendEventToMainContent={ sendToMainContent }
							context={ sidebarState.context }
						/>
					) }
				</SidebarContainer>
				<MainContentContainer>
					{ CurrentMainContentComponent && (
						<CurrentMainContentComponent
							key={ mainContentState.value.toString() }
							sendEventToSidebar={ sendToSidebar }
							sendEventToMainContent={ sendToMainContent }
							context={ mainContentState.context }
						/>
					) }
				</MainContentContainer>
			</SetUpPaymentsProvider>
		</div>
	);
};
export default LaunchStoreController;
