/**
 * External dependencies
 */
import React from 'react';

/**
 * Internal dependencies
 */
import NewFulfillmentForm from '../../fulfillments/new-fulfillment-form';
import { ErrorBoundary } from '~/error-boundary';
import FulfillmentsList from '../../fulfillments/fulfillments-list';
import FulfillmentsDrawerHeader from './fulfillment-drawer-header';
import { FulfillmentDrawerProvider } from '../../../context/drawer-context';
import './fulfillment-drawer.scss';
import FulfillmentDrawerBody from './fulfillment-drawer-body';

interface Props {
	isOpen: boolean;
	hasBackdrop?: boolean;
	onClose: () => void;
	orderId: number | null;
}

const FulfillmentDrawer: React.FC< Props > = ( {
	isOpen,
	hasBackdrop = false,
	onClose,
	orderId,
} ) => {
	return (
		<>
			{ hasBackdrop && (
				<div
					className="woocommerce-fulfillment-drawer__backdrop"
					onClick={ onClose }
					role="presentation"
					style={ { display: isOpen ? 'block' : 'none' } }
				/>
			) }
			<div className="woocommerce-fulfillment-drawer">
				<div
					className={ [
						'woocommerce-fulfillment-drawer__panel',
						isOpen ? 'is-open' : 'is-closed',
					].join( ' ' ) }
				>
					<ErrorBoundary>
						<FulfillmentDrawerProvider orderId={ orderId }>
							<FulfillmentsDrawerHeader onClose={ onClose } />
							<FulfillmentDrawerBody>
								<NewFulfillmentForm />
								<FulfillmentsList />
							</FulfillmentDrawerBody>
						</FulfillmentDrawerProvider>
					</ErrorBoundary>
				</div>
			</div>
		</>
	);
};

export default FulfillmentDrawer;
