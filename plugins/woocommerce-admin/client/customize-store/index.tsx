/**
 * Internal dependencies
 */

import { Intro } from './pages/intro';

export const CustomizeStoreController = () => {
	return (
		<>
			<div className={ `woocommerce-customize-store` }>
				<Intro />
			</div>
		</>
	);
};

export default CustomizeStoreController;
