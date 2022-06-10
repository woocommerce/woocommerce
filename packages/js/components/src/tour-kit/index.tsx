/**
 * External dependencies
 */

import { createElement } from '@wordpress/element';
import TourKit, { TourStepRenderer } from '@automattic/tour-kit';

/**
 * Internal dependencies
 */
import TourKitStep from './components/step';
import type { WooConfig } from './types';

interface Props {
	config: WooConfig;
}

const defaultOptions = {
	effects: { spotlight: {}, arrowIndicator: true },
};

const WooTourKit: React.FunctionComponent< Props > = ( { config } ) => {
	return (
		<TourKit
			__temp__className={ 'woocommerce-tour-kit' }
			config={ {
				options: {
					...defaultOptions,
					...config.options,
				},
				...config,
				renderers: {
					tourStep: TourKitStep as TourStepRenderer,
					// Disable minimize feature for woo tour kit.
					tourMinimized: () => null,
				},
			} }
		/>
	);
};

export default WooTourKit;
