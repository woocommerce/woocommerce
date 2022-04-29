/**
 * External dependencies
 */
import { useExperiment } from '@woocommerce/explat';
import { Card, CardBody, Modal } from '@wordpress/components';
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import './tip-of-the-day.scss';
import { HighlightTooltip } from '~/activity-panel/highlight-tooltip';

const EXPERIMENT_NAME = 'explat_test_mothra_tip_of_the_day_focused';
export const TipOfTheDay: React.FC< Record< string, never > > = () => {
	const [ hideModal, setHideModal ] = useState( false );
	const [ isLoadingExperiment, experimentAssignment ] = useExperiment(
		EXPERIMENT_NAME
	);

	if ( isLoadingExperiment ) {
		return null;
	}
	if ( experimentAssignment?.variationName === 'treatment' ) {
		if ( hideModal ) {
			return null;
		}
		return (
			<HighlightTooltip
				delay={ 2000 }
				useAnchor={ true }
				title="Tip of the day"
				content="Click this ellipsis to hide the task list."
				closeButtonText="Got it"
				selector=".woocommerce-task-dashboard__container .woocommerce-ellipsis-menu"
				onClose={ () => setHideModal( true ) }
			/>
		);
	}

	return (
		<Card className="tip-of-the-day">
			<CardBody>
				<h4>Tip Of The Day:</h4>
				You can hide the task list by clicking the Ellipsis icon below.
			</CardBody>
		</Card>
	);
};
