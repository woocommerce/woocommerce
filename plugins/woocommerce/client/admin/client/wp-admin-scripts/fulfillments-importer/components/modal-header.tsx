/**
 * External dependencies
 */
import React from 'react';
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';

/**
 * Internal dependencies
 */
import type { ImporterStep } from '../hooks/use-importer-state';

interface ModalHeaderProps {
	currentStep: ImporterStep;
	title: string;
	onClose: () => void;
	canClose: boolean;
}

/**
 * Slim wizard header: title on the left, a text action on the right. The
 * action reads "Close import" once results are shown, "Cancel" before, and
 * disappears while the import is running so a half-finished import is not
 * easy to walk away from by accident.
 */
const ModalHeader: React.FC< ModalHeaderProps > = ( {
	currentStep,
	title,
	onClose,
	canClose,
} ) => (
	<header className="woocommerce-fulfillment-importer-modal__header">
		<span className="woocommerce-fulfillment-importer-modal__title">
			{ title }
		</span>
		{ canClose ? (
			<Button
				variant="link"
				className="woocommerce-fulfillment-importer-modal__header-action"
				onClick={ onClose }
			>
				{ currentStep === 'done'
					? __( 'Close import', 'woocommerce' )
					: __( 'Cancel', 'woocommerce' ) }
			</Button>
		) : null }
	</header>
);

export default ModalHeader;
