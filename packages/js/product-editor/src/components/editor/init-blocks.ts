/**
 * Internal dependencies
 */
import { init as initSection } from '../section';
import { init as initImages } from '../images';
import { init as initName } from '../name';
import { init as initSummary } from '../summary';
import { init as initSubmit } from '../submit';
import { init as initProductForm } from '../product-form';
import { init as initEmpty } from '../empty';

export const initBlocks = () => {
	initProductForm();
	initImages();
	initName();
	initSection();
	initSummary();
	initSubmit();
	initEmpty();
};

Object.defineProperty( initBlocks, 'name', {
	value: 'initBlocks',
	writable: false,
} );
