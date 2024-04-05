/* eslint-disable @typescript-eslint/ban-ts-comment */
/* eslint-disable @woocommerce/dependency-group */
/**
 * External dependencies
 */
import classnames from 'classnames';
import { createInterpolateElement, useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { Link } from '@woocommerce/components';
// @ts-ignore No types for this exist yet.
import SidebarNavigationItem from '@wordpress/edit-site/build-module/components/sidebar-navigation-item';
import {
	Button,
	// @ts-ignore No types for this exist yet.
	__unstableMotion as motion,
	// @ts-ignore No types for this exist yet.
	__experimentalItemGroup as ItemGroup,
	// @ts-ignore No types for this exist yet.
	__experimentalHeading as Heading,
	ToggleControl,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import type { SidebarComponentProps } from '../xstate';
import { SidebarContainer } from './sidebar-container';
import { taskCompleteIcon } from './icons';
import { SiteHub } from '~/customize-store/assembler-hub/site-hub';
import { CompletedTaskItem, IncompleteTaskItem } from '../tasklist';
export const LaunchYourStoreHubSidebar: React.FC< SidebarComponentProps > = (
	props
) => {
	const {
		context: {
			tasklist,
			removeTestOrders: removeTestOrdersContext,
			testOrderCount,
		},
	} = props;

	const sidebarTitle = (
		<Button
			onClick={ () => {
				props.sendEventToSidebar( {
					type: 'POP_BROWSER_STACK', // go back to previous URL
				} );
			} }
		>
			{ __( 'Launch Your Store', 'woocommerce' ) }
		</Button>
	);

	const sidebarDescription = createInterpolateElement(
		__(
			'Ready to start selling? Before you launch your store, make sure you’ve completed these essential tasks. If you’d like to change your store visibility, go to <WCSettingsLink>WooCommerce | Settings | General.</WCSettingsLink>',
			'woocommerce'
		),
		{
			WCSettingsLink: (
				<Link
					onClick={ () => {
						props.sendEventToSidebar( {
							type: 'OPEN_WC_ADMIN_URL',
							url: 'admin.php?page=wc-settings&tab=general',
						} );
					} }
					href=""
				/>
			),
		}
	);

	const hasIncompleteTasks =
		tasklist && ! tasklist.tasks.every( ( task ) => task.isComplete );

	const [ removeTestOrders, setRemoveTestOrder ] = useState(
		removeTestOrdersContext ?? true
	);

	return (
		<div
			className={ classnames(
				'launch-store-sidebar__container',
				props.className
			) }
		>
			<motion.div
				className="edit-site-layout__header-container"
				animate={ 'view' }
			>
				<SiteHub
					as={ motion.div }
					variants={ {
						view: { x: 0 },
					} }
					isTransparent={ false }
					className="edit-site-layout__hub"
				/>
			</motion.div>
			<SidebarContainer
				title={ sidebarTitle }
				description={ sidebarDescription }
			>
				<div className="edit-site-sidebar-navigation-screen-essential-tasks__group-header">
					<Heading level={ 2 }>
						{ __( 'Essential Tasks', 'woocommerce' ) }
					</Heading>
				</div>
				<ItemGroup className="edit-site-sidebar-navigation-screen-essential-tasks__group">
					{ tasklist &&
						hasIncompleteTasks &&
						tasklist.tasks.map( ( task ) =>
							task.isComplete ? (
								<CompletedTaskItem
									task={ task }
									key={ task.id }
								/>
							) : (
								<IncompleteTaskItem
									task={ task }
									key={ task.id }
									onClick={ () => {
										props.sendEventToSidebar( {
											type: 'TASK_CLICKED',
											task,
										} );
									} }
								/>
							)
						) }
					{ tasklist && ! hasIncompleteTasks && (
						<SidebarNavigationItem
							className="all-tasks-complete"
							icon={ taskCompleteIcon }
						>
							{ __(
								'Fantastic job! Your store is ready to go — no pending tasks to complete.',
								'woocommerce'
							) }
						</SidebarNavigationItem>
					) }
				</ItemGroup>
				{ testOrderCount > 0 && (
					<>
						<div className="edit-site-sidebar-navigation-screen-test-data__group-header">
							<Heading level={ 2 }>
								{ __( 'Test data', 'woocommerce' ) }
							</Heading>
						</div>
						<ItemGroup className="edit-site-sidebar-navigation-screen-remove-test-data__group">
							<ToggleControl
								label={ sprintf(
									// translators: %d is the number of test orders
									__(
										'Remove %d test orders',
										'woocommerce'
									),
									testOrderCount
								) }
								checked={ removeTestOrders }
								onChange={ setRemoveTestOrder }
							/>
							<p>
								{ __(
									'Remove test orders and associated data, including analytics and transactions, once your store goes live. ',
									'woocommerce'
								) }
							</p>
						</ItemGroup>
					</>
				) }
				<ItemGroup className="edit-site-sidebar-navigation-screen-launch-store-button__group">
					<Button
						variant="primary"
						onClick={ () => {
							props.sendEventToSidebar( {
								type: 'LAUNCH_STORE',
								removeTestOrders,
							} );
						} }
					>
						{ __( 'Launch Store', 'woocommerce' ) }
					</Button>
				</ItemGroup>
			</SidebarContainer>
		</div>
	);
};
