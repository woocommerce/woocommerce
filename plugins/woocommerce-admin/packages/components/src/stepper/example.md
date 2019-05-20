```jsx
import { Stepper } from '@woocommerce/components';

const MyStepper = withState( {
    currentStep: 'first',
    isComplete: false,
} )( ( { currentStep, isComplete, setState } ) => {
    const steps = [
        {
            label: 'First',
            key: 'first',
        },
        {
            label: 'Second',
            key: 'second',
        },
        {
            label: 'Third',
            key: 'third',
        },
        {
            label: 'Fourth',
            key: 'fourth',
        },
    ];
    const currentIndex = steps.findIndex( s => currentStep === s.key );

    if ( isComplete ) {
        steps.forEach( s => s.isComplete = true );
    }

    return (
        <div>
            { isComplete ? (
                <button onClick={ () => setState( { currentStep: 'first', isComplete: false } ) } >
                    Reset
                </button>
            ) : (
                <div>
                    <button
                        onClick={ () => setState( { currentStep: steps[ currentIndex - 1 ]['key'] } ) }
                        disabled={ currentIndex < 1 }
                    >
                        Previous step
                    </button>
                    <button
                        onClick={ () => setState( { currentStep: steps[ currentIndex + 1 ]['key'] } ) }
                        disabled={ currentIndex >= steps.length - 1 }
                    >
                        Next step
                    </button>
                    <button
                        onClick={ () => setState( { isComplete: true } ) }
                        disabled={ currentIndex !== steps.length - 1 }
                    >
                        Complete
                    </button>
                </div>
            ) }

			<Stepper
				steps={ steps }
				currentStep={ currentStep }
			/>

			<Stepper
				direction="vertical"
				steps={ steps }
				currentStep={ currentStep }
			/>
		</div>
	);
} );
```
