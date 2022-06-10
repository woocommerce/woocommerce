# ExPlat

This packages includes a component and utility functions that can be used to run A/B Tests in WooCommerce dashboard and reports pages.

## Installation

Install the module

```bash
pnpm install @woocommerce/explat --save
```

This package assumes that your code will run in an ES2015+ environment. If you're using an environment that has limited or no support for ES2015+ such as lower versions of IE then using core-js or @babel/polyfill will add support for these methods. Learn more about it in Babel docs.

## Usage with Experiment Component

```js
import { Experiment } from '@woocommerce/explat';

const DefaultExperience = <div>Hello World</div>;

const TreatmentExperience = <div>Hello WooCommerce!</div>;

const LoadingExperience = <div>⏰</div>;

<Experiment
	name="woocommerce_example_experiment"
	defaultExperience={ DefaultExperience }
	treatmentExperience={ TreatmentExperience }
	loadingExperience={ LoadingExperience }
/>;

// Get the experiment assignment with authentication as a WPCOM user.
import { ExperimentWithAuth } from '@woocommerce/explat';

<ExperimentWithAuth
	name="woocommerce_example_experiment"
	defaultExperience={ DefaultExperience }
	treatmentExperience={ TreatmentExperience }
	loadingExperience={ LoadingExperience }
/>;
```

## Usage with useExperiment

```js
import { useExperiment } from '@woocommerce/explat';

const DefaultExperience = <div>Hello World</div>;

const TreatmentExperience = <div>Hello WooCommerce!</div>;

const [ isLoadingExperiment, experimentAssignment ] = useExperiment('experiment-name');

if ( ! isLoadingExperiment && experimentAssignment?.variationName === 'treatment' ) {
	return <TreatmentExperience />
}

return <DefaultExperience />

````

