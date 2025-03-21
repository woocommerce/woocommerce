/**
 * External dependencies
 */
import React from 'react';

/**
 * Internal dependencies
 */
import { useStepperContext } from '../stepper';
import { OnboardingSteps } from '../../types';
import { useTrackAbandoned } from '../../utils/tracking';
import strings from '../../strings';

interface Props {
	name: OnboardingSteps;
	showHeading?: boolean;
	children: React.ReactNode;
}

const Step: React.FC< Props > = ( { name, children, showHeading = true } ) => {
	const { trackAbandoned } = useTrackAbandoned();

	const { exit } = useStepperContext();
	const handleExit = () => {
		trackAbandoned( 'exit' );
		exit();
	};

	return (
		<>
			<div className="stepper__wrapper">
				{ showHeading && (
					<>
						<h1 className="stepper__heading">
							{ strings.steps[ name ].heading }
						</h1>
						<h2 className="stepper__subheading">
							{ strings.steps[ name ].subheading }
						</h2>
					</>
				) }
				<div className="stepper__content">{ children }</div>
			</div>
		</>
	);
};

export default Step;
