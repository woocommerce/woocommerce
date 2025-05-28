/* eslint-disable @typescript-eslint/ban-ts-comment */
/* eslint-disable @woocommerce/dependency-group */
/**
 * External dependencies
 */
import React from 'react';
import { __ } from '@wordpress/i18n';
// @ts-ignore No types for this exist yet.
import SidebarNavigationItem from '@wordpress/edit-site/build-module/components/sidebar-navigation-item';
import clsx from 'clsx';
import {
	Button,
	// @ts-ignore No types for this exist yet.
	__experimentalItemGroup as ItemGroup,
	// @ts-ignore No types for this exist yet.
	__unstableMotion as motion,
} from '@wordpress/components';
import { useOnboardingContext } from '~/settings-payments/onboarding/providers/woopayments/data/onboarding-context';

/**
 * Internal dependencies
 */
import type { SidebarComponentProps } from '../xstate';
import { SidebarContainer } from './sidebar-container';
import { SiteHub } from '~/customize-store/assembler-hub/site-hub';
import { taskIcons } from './icons';
import { StepPlaceholder } from './step-placeholder';

export const PaymentsSidebar = ( props: SidebarComponentProps ) => {
	const {
		steps: allSteps,
		currentStep,
		justCompletedStepId,
		isLoading,
	} = useOnboardingContext();

	// Store the initial uncompleted step IDs on first render
	const initialUncompletedStepIds = React.useRef< string[] | null >( null );

	// Only set the initial uncompleted step IDs if there are backend steps.
	if (
		initialUncompletedStepIds.current === null &&
		allSteps?.some( ( step ) => step.type === 'backend' )
	) {
		initialUncompletedStepIds.current = allSteps
			.filter( ( step ) => step.status !== 'completed' )
			.map( ( step ) => step.id );
	}

	// Only show steps that were uncompleted on first render
	const stepsToDisplay = allSteps.filter( ( step ) =>
		initialUncompletedStepIds.current?.includes( step.id )
	);

	const currentStepIndex = allSteps.findIndex(
		( step ) => step.id === currentStep?.id
	);

	const sidebarTitle = (
		<Button
			onClick={ () => {
				props.sendEventToSidebar( {
					type: 'RETURN_FROM_PAYMENTS',
				} );
			} }
		>
			{ __( 'Set up WooPayments', 'woocommerce' ) }
		</Button>
	);

	return (
		<div
			className={ clsx(
				'launch-store-sidebar__container',
				props.className
			) }
		>
			<motion.div
				className="woocommerce-edit-site-layout__header-container"
				animate={ 'view' }
			>
				<SiteHub
					variants={ {
						view: { x: 0 },
					} }
					isTransparent={ false }
					className="woocommerce-edit-site-layout__hub"
				/>
			</motion.div>
			<SidebarContainer title={ sidebarTitle }>
				{ /* We are using these classes to inherit the styles from the edit your store styling */ }
				<ItemGroup className="woocommerce-edit-site-sidebar-navigation-screen-essential-tasks__group">
					{ isLoading && (
						<motion.div
							initial={ { opacity: 0 } }
							animate={ { opacity: 1 } }
							exit={ { opacity: 0 } }
							transition={ { duration: 0.3 } }
						>
							<StepPlaceholder rows={ 3 } />
						</motion.div>
					) }
					{ ! isLoading && (
						<motion.div
							initial={ { opacity: 0, y: 10 } }
							animate={ { opacity: 1, y: 0 } }
							transition={ { duration: 0.4, delay: 0.1 } }
						>
							{ stepsToDisplay.map( ( step ) => (
								<SidebarNavigationItem
									key={ step.id }
									className={ clsx( step.id, {
										active: currentStep?.id === step.id,
										'payment-step': true,
										'payment-step--active':
											currentStep?.id === step.id,
										'payment-step--disabled':
											currentStep?.id !== step.id,
									} ) }
									icon={
										step.id === justCompletedStepId ||
										step.status === 'completed' ||
										currentStepIndex === allSteps.length
											? taskIcons.completedPaymentStep
											: taskIcons.activePaymentStep
									}
									disabled={ true }
									showChevron={ false }
								>
									{ step.label }
								</SidebarNavigationItem>
							) ) }
						</motion.div>
					) }
				</ItemGroup>
			</SidebarContainer>
		</div>
	);
};
