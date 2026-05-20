/**
 * External dependencies
 */
import React, { useCallback, useEffect } from 'react';
import { __ } from '@wordpress/i18n';
import { Modal } from '@wordpress/components';

/**
 * Internal dependencies
 */
import ModalHeader from './modal-header';
import UploadStep from './steps/upload-step';
import MappingStep from './steps/mapping-step';
import ImportStep from './steps/import-step';
import DoneStep from './steps/done-step';
import type { StepComponentProps } from './steps/types';
import { useImporterState } from '../hooks/use-importer-state';
import type { ImporterStep } from '../hooks/use-importer-state';

interface Props {
	isOpen: boolean;
	onClose: () => void;
}

const STEP_COMPONENTS: Record<
	ImporterStep,
	React.ComponentType< StepComponentProps >
> = {
	upload: UploadStep,
	mapping: MappingStep,
	import: ImportStep,
	done: DoneStep,
};

/**
 * Full-screen wizard shell. Owns the reducer; routes each step to its component.
 */
const FulfillmentsImporterModal: React.FC< Props > = ( {
	isOpen,
	onClose,
} ) => {
	const [ state, dispatch ] = useImporterState();

	const canClose = state.step !== 'import';

	const handleClose = useCallback( () => {
		if ( ! canClose ) {
			return;
		}
		dispatch( { type: 'RESET' } );
		onClose();
	}, [ canClose, dispatch, onClose ] );

	// Reset reducer state when the modal is closed externally so the next open starts fresh.
	useEffect( () => {
		if ( ! isOpen ) {
			dispatch( { type: 'RESET' } );
		}
	}, [ isOpen, dispatch ] );

	if ( ! isOpen ) {
		return null;
	}

	const StepComponent = STEP_COMPONENTS[ state.step ];

	const title = __( 'Import fulfillments', 'woocommerce' );

	return (
		<Modal
			className="woocommerce-fulfillment-importer-modal"
			overlayClassName="woocommerce-fulfillment-importer-overlay"
			onRequestClose={ handleClose }
			shouldCloseOnClickOutside={ canClose }
			shouldCloseOnEsc={ canClose }
			isDismissible={ canClose }
			aria-busy={ ! canClose || state.isBusy }
			// eslint-disable-next-line @typescript-eslint/ban-ts-comment
			// @ts-ignore -- __experimentalHideHeader is supported but not in the typings shipped here.
			__experimentalHideHeader
			title={ title }
			aria-label={ title }
		>
			<div className="woocommerce-fulfillment-importer-modal__layout">
				<ModalHeader
					currentStep={ state.step }
					title={ title }
					onClose={ handleClose }
					canClose={ canClose }
				/>
				<div className="woocommerce-fulfillment-importer-modal__body">
					<StepComponent
						state={ state }
						dispatch={ dispatch }
						onClose={ handleClose }
					/>
				</div>
			</div>
		</Modal>
	);
};

export default FulfillmentsImporterModal;
