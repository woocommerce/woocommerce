/**
 * External dependencies
 */
import { Component } from 'react';

/**
 * HOC that gives a component a unique ID.
 *
 * This is an alternative for withInstanceId from @wordpress/compose to avoid
 * using that dependency on the frontend.
 */
const withComponentId = ( OriginalComponent ) => {
	let instances = 0;

	class WrappedComponent extends Component {
		instanceId = instances++;

		render() {
			return (
				<OriginalComponent
					{ ...this.props }
					componentId={ this.instanceId }
				/>
			);
		}
	}
	WrappedComponent.displayName = 'withComponentId';
	return WrappedComponent;
};

export default withComponentId;
