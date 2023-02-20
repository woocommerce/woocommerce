/**
 * Internal dependencies
 */
import { init as initDetailsSection } from '../details-section';
import { init as initImages } from '../images';
import { init as initName } from '../name';
import { init as initSummary } from '../summary';
import { init as initSubmit } from '../submit';
import { init as initProductForm } from '../product-form';

export const initBlocks = () => {
	initProductForm();
	initDetailsSection();
	initImages();
	initName();
	initSummary();
	initSubmit();
};
