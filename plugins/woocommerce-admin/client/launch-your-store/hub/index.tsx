/**
 * External dependencies
 */
import { useMachine } from '@xstate5/react';
import React, { useEffect } from 'react';
import classnames from 'classnames';

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
import { addAction } from '@wordpress/hooks';
import { navigateTo } from '@woocommerce/navigation';

export type LaunchYourStoreComponentProps = {
	sendEventToSidebar: ( arg0: SidebarMachineEvents ) => void;
	sendEventToMainContent: ( arg0: MainContentMachineEvents ) => void;
	className?: string;
};

const LaunchStoreController = () => {
	useFullScreen( [ 'woocommerce-launch-your-store' ] );
	const { xstateV5Inspector: inspect } = useXStateInspect( 'V5' );

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

	useEffect( () => {
		addAction(
			'woocommerce_launch_your_store_task_clicked',
			'woocommerce-admin-lys',
			( taskId: string ) => {
				console.log( 'Task clicked:', taskId );
			}
		);
		addAction(
			'woocommerce_lys_cys_redirect',
			'woocommerce-admin-lys',
			( taskId: string ) => {
				console.log( 'Return to LYS', taskId );
				navigateTo( { url: '/launch-your-store' } );
			}
		);
	}, [] );

	return (
		<div className={ 'launch-your-store-layout__container' }>
			<SidebarContainer
				className={ classnames( {
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
						sendEventToSidebar={ sendToSidebar }
						sendEventToMainContent={ sendToMainContent }
						context={ mainContentState.context }
					/>
				) }
			</MainContentContainer>
		</div>
	);
};
export default LaunchStoreController;
