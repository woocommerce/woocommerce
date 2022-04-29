/**
 * External dependencies
 */
import { useExperiment } from '@woocommerce/explat';
import { recordEvent } from '@woocommerce/tracks';
import { Button, Card, CardBody } from '@wordpress/components';
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import './one-click-complete.scss';

const EXPERIMENT_NAME = 'explat_test_mothra_task_list_complete';

export const OneClickComplete: React.FC< Record< string, never > > = () => {
	const [ completed, setCompleted ] = useState( false );
	const [ isLoadingExperiment, experimentAssignment ] = useExperiment(
		EXPERIMENT_NAME
	);

	const triggerComplete = () => {
		recordEvent( 'wcadmin_tasklist_completed' );
		setCompleted( true );
	};

	if ( isLoadingExperiment ) {
		return null;
	}
	if ( experimentAssignment?.variationName === 'treatment' ) {
		return (
			<Card className="one-click-complete">
				<CardBody>
					<h1>Treatment: Please complete the task list:</h1>
					<Button
						variant="primary"
						disabled={ completed }
						onClick={ () => triggerComplete() }
					>
						Complete
					</Button>
				</CardBody>
			</Card>
		);
	}

	return (
		<Card className="one-click-complete">
			<CardBody>
				Control: complete task list:{ ' ' }
				<Button
					disabled={ completed }
					onClick={ () => triggerComplete() }
				>
					Complete
				</Button>
			</CardBody>
		</Card>
	);
};
