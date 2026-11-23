/**
 * Internal dependencies
 */
import { registerProductEntity } from './register-entities';

export {
	isExternalProduct,
	isProductResponseItem,
	useProduct,
} from './deprecated-exports';

registerProductEntity();
