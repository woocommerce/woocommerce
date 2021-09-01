/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { EllipsisMenu } from '@woocommerce/components';
import { ONBOARDING_STORE_NAME } from '@woocommerce/data';
import { useDispatch } from '@wordpress/data';

export type TaskListMenuProps = {
	id: string;
};

export const TaskListMenu: React.FC< TaskListMenuProps > = ( { id } ) => {
	const { hideTaskList } = useDispatch( ONBOARDING_STORE_NAME );

	return (
		<div className="woocommerce-card__menu woocommerce-card__header-item">
			<EllipsisMenu
				label={ __( 'Task List Options', 'woocommerce-admin' ) }
				renderContent={ () => (
					<div className="woocommerce-task-card__section-controls">
						<Button onClick={ () => hideTaskList( id ) }>
							{ __( 'Hide this', 'woocommerce-admin' ) }
						</Button>
					</div>
				) }
			/>
		</div>
	);
};
