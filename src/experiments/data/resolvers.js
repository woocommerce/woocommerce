/**
 * Internal dependencies
 */
import { setExperiments } from './actions';
import { EXPERIMENT_NAME_PREFIX } from './constants';

export function* getExperiments() {
	const storageItems = Object.entries({ ...window.localStorage }).filter(
		(item) => {
			return item[0].indexOf(EXPERIMENT_NAME_PREFIX) === 0;
		}
	);

	const experiments = storageItems.map((storageItem) => {
		const [key, value] = storageItem;
		const objectValue = JSON.parse(value);
		return {
			name: key.replace(EXPERIMENT_NAME_PREFIX, ''),
			variation: objectValue.variationName || 'control',
		};
	});

	yield setExperiments(experiments);
}
