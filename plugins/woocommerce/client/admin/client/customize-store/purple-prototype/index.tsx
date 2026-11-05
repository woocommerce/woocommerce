/**
 * External dependencies
 */
import '@wordpress/theme/design-tokens.css';

/**
 * Internal dependencies
 */
import { getPurplePrototypeSettings } from './settings';
import { TaskPage } from './task-page';
import './style.scss';

/**
 * Prototype of Purple in "Customize your store", behind the `purple-cys-prototype` flag.
 */
const PurpleCysPrototype = () => (
	<TaskPage settings={ getPurplePrototypeSettings() } />
);

export default PurpleCysPrototype;
