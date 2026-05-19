/**
 * External dependencies
 */
import React from 'react';
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { closeSmall, chevronLeft } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import Stepper from './stepper';
import type { ImporterStep } from '../hooks/use-importer-state';

interface ModalHeaderProps {
	currentStep: ImporterStep;
	title: string;
	onBack?: () => void;
	onClose: () => void;
	canClose: boolean;
}

const ModalHeader: React.FC< ModalHeaderProps > = ( {
	currentStep,
	title,
	onBack,
	onClose,
	canClose,
} ) => (
	<header className="woocommerce-fulfillment-importer-modal__header">
		<div className="woocommerce-fulfillment-importer-modal__header-row">
			<div className="woocommerce-fulfillment-importer-modal__header-leading">
				{ onBack && (
					<Button
						icon={ chevronLeft }
						label={ __( 'Back', 'woocommerce' ) }
						onClick={ onBack }
					/>
				) }
				<h1 className="woocommerce-fulfillment-importer-modal__title">
					{ title }
				</h1>
			</div>
			<Button
				icon={ closeSmall }
				label={ __( 'Close', 'woocommerce' ) }
				onClick={ onClose }
				disabled={ ! canClose }
				aria-disabled={ ! canClose }
			/>
		</div>
		<Stepper currentStep={ currentStep } />
	</header>
);

export default ModalHeader;
