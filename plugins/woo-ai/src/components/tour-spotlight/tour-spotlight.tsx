/**
 * External dependencies
 */
import Tour, { TourStepRendererProps } from '@automattic/tour-kit';

type TourSpotlightProps = {
	onDismiss: () => void;
	title: string;
	description: string;
	reference: string;
};

// @todo: shouldn't we get these types by importing from the package?

export default function TourSpotlight( {
	onDismiss,
	title,
	description,
	reference,
}: TourSpotlightProps ) {
	// Define a configuration for the tour, passing along a handler for closing.
	// @todo this is definitely wrong, should we re-add the currentStepIndex?
	const config = {
		steps: [
			{
				referenceElements: {
					desktop: reference,
				},
				meta: {
					description: { description },
				},
			},
		],
		renderers: {
			tourStep: ( { currentStepIndex }: TourStepRendererProps ) => {
				return (
					<>
						<h3>{ title }</h3>
						<button onClick={ onDismiss }>Got it</button>
						<p>
							{
								config.steps[ currentStepIndex ].meta
									.description
							}
						</p>
					</>
				);
			},
			tourMinimized: () => <div />,
		},
		closeHandler: () => onDismiss(),
		options: {},
	};

	return <Tour config={ config } />;
}
