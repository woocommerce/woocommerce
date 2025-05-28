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
	__experimentalHeading as Heading,
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

export const PaymentsSidebar = ( props: SidebarComponentProps ) => {
	const {
		steps: allSteps,
		currentStep,
		justCompletedStepId,
		storeError,
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
			{ __( 'Get paid', 'woocommerce' ) }
		</Button>
	);

	const sidebarDescription = __(
		'Set up WooPayments to start accepting payments in your store.',
		'woocommerce'
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
			<SidebarContainer
				title={ sidebarTitle }
				description={ sidebarDescription }
			>
				{ /* We are using these classes to inherit the styles from the edit your store styling */ }
				<div className="woocommerce-edit-site-sidebar-navigation-screen-essential-tasks__group-header">
					<Heading level={ 2 }>
						{ __( 'Setup your payments', 'woocommerce' ) }
					</Heading>
				</div>
				<ItemGroup className="woocommerce-edit-site-sidebar-navigation-screen-essential-tasks__group">
					{ /* In case of an error, we show the install WooPayments step, otherwise we show the steps */ }
					{ !! storeError && (
						<SidebarNavigationItem
							key={ 'install_woopayments' }
							className={ clsx( 'install_woopayments', {
								'payment-step': true,
							} ) }
							icon={ taskIcons.activePaymentStep }
							disabled={ true }
							showChevron={ false }
						>
							{ sprintf(
								/* translators: %1$s: WooPayments */
								__(
									'Install %1$s',
									'woocommerce'
								),
								'WooPayments'
							)}
						</SidebarNavigationItem>
					) }
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
				</ItemGroup>
			</SidebarContainer>
		</div>
	);
};
