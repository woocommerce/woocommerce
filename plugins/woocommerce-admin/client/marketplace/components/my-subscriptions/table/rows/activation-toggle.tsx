/**
 * External dependencies
 */
import { ToggleControl } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { Subscription } from '../../types';
import Install from '../../install';

interface ActivationToggleProps {
	subscription: Subscription;
}

export default function ActivationToggle( props: ActivationToggleProps ) {
	if (
		props.subscription.local.installed === false &&
		props.subscription.maxed === false
	) {
		return <Install subscription={ props.subscription } />;
	}

	return <ToggleControl checked={ props.subscription.active } />;
}
