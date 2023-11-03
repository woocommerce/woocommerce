/**
 * External dependencies
 */

import { createElement } from '@wordpress/element';
import TourKit, { TourStepRenderer, Options } from '@automattic/tour-kit';

/**
 * Internal dependencies
 */
import TourKitStep from './components/step';
import type { WooConfig } from './types';

interface Props {
	config: WooConfig;
}

const defaultOptions: Options = {
	effects: {
		spotlight: {
			interactivity: { enabled: true, rootElementSelector: '#wpwrap' },
		},
		arrowIndicator: true,
		liveResize: {
			mutation: true,
			resize: true,
			rootElementSelector: '#wpwrap',
		},
	},
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
