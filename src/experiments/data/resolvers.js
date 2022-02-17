/**
 * Internal dependencies
 */
import { setExperiments } from './actions';
import { EXPERIMENT_NAME_PREFIX } from './constants';

export function* getExperiments() {
	const storageItems = Object.entries({ ...window.localStorage }).filter(
		(item) => {
			if (item[0].indexOf(EXPERIMENT_NAME_PREFIX) === 0) {
				return true;
			}
			return false;
		}
	);

	const experiments = [];
	storageItems.forEach((storageItem) => {
		const [key, value] = storageItem;
		const objectValue = JSON.parse(value);

		const experiment = {
			name: key.replace(EXPERIMENT_NAME_PREFIX, ''),
			variation: objectValue.variationName
				? objectValue.variationName
				: 'control',
		};
		experiments.push(experiment);
	});

	yield setExperiments(experiments);
}
