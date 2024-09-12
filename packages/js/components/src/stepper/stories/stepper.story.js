/**
 * External dependencies
 */
import { Stepper } from '@woocommerce/components';
import { useState } from '@wordpress/element';

const BasicExamples = () => {
	const [ state, setState ] = useState( {
		currentStep: 'first',
		isComplete: false,
		isPending: false,
	} );
	const { currentStep, isComplete, isPending } = state;

	const goToStep = ( key ) => {
		setState( { currentStep: key } );
	};

	const steps = [
		{
			key: 'first',
			label: 'First',
			description: 'Step item description',
			content: <div>First step content.</div>,
			onClick: goToStep,
		},
		{
			key: 'second',
			label: 'Second',
			description: 'Step item description',
			content: <div>Second step content.</div>,
			onClick: goToStep,
		},
		{
			label: 'Third',
			key: 'third',
			description: 'Step item description',
			content: <div>Third step content.</div>,
			onClick: goToStep,
		},
		{
			label: 'Fourth',
			key: 'fourth',
			description: 'Step item description',
			content: <div>Fourth step content.</div>,
			onClick: goToStep,
		},
	];

	const currentIndex = steps.findIndex( ( s ) => currentStep === s.key );

	if ( isComplete ) {
		steps.forEach( ( s ) => ( s.isComplete = true ) );
	}

	return (
		<div>
			{ isComplete ? (
				<button
					onClick={ () =>
						setState( {
							...state,
							currentStep: 'first',
							isComplete: false,
						} )
					}
				>
					Reset
				</button>
			) : (
				<div>
					<button
						onClick={ () =>
							setState( {
								...state,
								currentStep: steps[ currentIndex - 1 ].key,
							} )
						}
						disabled={ currentIndex < 1 }
					>
						Previous step
					</button>
					<button
						onClick={ () =>
							setState( {
								...state,
								currentStep: steps[ currentIndex + 1 ].key,
							} )
						}
						disabled={ currentIndex >= steps.length - 1 }
					>
						Next step
					</button>
					<button
						onClick={ () =>
							setState( { ...state, isComplete: true } )
						}
						disabled={ currentIndex !== steps.length - 1 }
					>
						Complete
					</button>
					<button
						onClick={ () =>
							setState( { ...state, isPending: ! isPending } )
						}
					>
						Toggle Spinner
					</button>
				</div>
			) }

			<Stepper
				steps={ steps }
				currentStep={ currentStep }
				isPending={ isPending }
			/>

			<br />

			<Stepper
				isPending={ isPending }
				isVertical={ true }
				steps={ steps }
				currentStep={ currentStep }
			/>
		</div>
	);
};

export const Examples = () => <BasicExamples />;

export default {
	title: 'WooCommerce Admin/components/Stepper',
	component: Stepper,
};
