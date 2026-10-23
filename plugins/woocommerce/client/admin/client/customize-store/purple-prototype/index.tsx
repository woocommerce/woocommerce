/**
 * External dependencies
 */
import '@wordpress/theme/design-tokens.css';

/**
 * Internal dependencies
 */
import { getPurplePrototypeSettings } from './settings';
import { TaskPage } from './task-page';
import { ModalContainer } from './modal-container';
import './style.scss';

/**
 * Prototype of Purple in "Customize your store", behind the `purple-cys-prototype` flag.
 * The container comes from the "Purple prototype" admin bar menu.
 */
const PurpleCysPrototype = () => {
	const settings = getPurplePrototypeSettings();

	return settings.container === 'modal' ? (
		<ModalContainer settings={ settings } />
	) : (
		<TaskPage settings={ settings } />
	);
};

export default PurpleCysPrototype;
