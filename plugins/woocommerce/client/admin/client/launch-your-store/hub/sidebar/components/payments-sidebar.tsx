/* eslint-disable @typescript-eslint/ban-ts-comment */
/* eslint-disable @woocommerce/dependency-group */
/**
 * External dependencies
 */
import React from 'react';
import { __, sprintf } from '@wordpress/i18n';
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
import { taskIcons, taskCompleteIcon } from './icons';
import { StepPlaceholder } from './step-placeholder';
import { useInstallationContext } from '~/launch-your-store/data/installation-context';

export const PaymentsSidebar = ( props: SidebarComponentProps ) => {
	const { wooPaymentsRecentlyActivated, isWooPaymentsActive } =
		useInstallationContext();

	const {
		steps: allSteps,
		currentStep,
		justCompletedStepId,
		isLoading,
	} = useOnboardingContext();

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

	const InstallWooPaymentsStep = ( {
		isStepComplete,
	}: {
		isStepComplete: boolean;
	} ) => (
		<SidebarNavigationItem
			key="install-woopayments"
			className={ clsx( 'install-woopayments', {
				active: isStepComplete,
				'payment-step': true,
				'payment-step--active': isStepComplete,
				'payment-step--disabled': isStepComplete,
				'is-complete': isStepComplete,
			} ) }
			icon={
				isStepComplete ? taskCompleteIcon : taskIcons.activePaymentStep
			}
			disabled={ true }
			showChevron={ false }
		>
			{
				/* translators: %s: WooPayments */
				sprintf( __( 'Install %s', 'woocommerce' ), 'WooPayments' )
			}
		</SidebarNavigationItem>
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
					{ ! isWooPaymentsActive && (
						<motion.div
							initial={ { opacity: 0, y: 0 } }
							animate={ { opacity: 1, y: 0 } }
							transition={ { duration: 0.7, delay: 0.2 } }
						>
							<InstallWooPaymentsStep isStepComplete={ false } />
						</motion.div>
					) }
					{ isWooPaymentsActive && isLoading && (
						<motion.div
							initial={ { opacity: 0 } }
							animate={ { opacity: 1 } }
							exit={ { opacity: 0 } }
							transition={ { duration: 0.3 } }
						>
							<StepPlaceholder rows={ 3 } />
						</motion.div>
					) }
					{ isWooPaymentsActive && ! isLoading && (
						<motion.div
							initial={ { opacity: 0, y: 0 } }
							animate={ { opacity: 1, y: 0 } }
							transition={ { duration: 0.7, delay: 0.2 } }
						>
							{ wooPaymentsRecentlyActivated && (
								<InstallWooPaymentsStep
									isStepComplete={ true }
								/>
							) }
							{ allSteps.map( ( step ) => {
								const isStepComplete =
									step.id === justCompletedStepId ||
									step.status === 'completed' ||
									currentStepIndex === allSteps.length;
								return (
									<SidebarNavigationItem
										key={ step.id }
										className={ clsx( step.id, {
											active: currentStep?.id === step.id,
											'payment-step': true,
											'payment-step--active':
												currentStep?.id === step.id,
											'payment-step--disabled':
												currentStep?.id !== step.id,
											'is-complete': isStepComplete,
										} ) }
										icon={
											isStepComplete
												? taskCompleteIcon
												: taskIcons.activePaymentStep
										}
										disabled={ true }
										showChevron={ false }
									>
										{ step.label }
									</SidebarNavigationItem>
								);
							} ) }
						</motion.div>
					) }
				</ItemGroup>
			</SidebarContainer>
		</div>
	);
};
