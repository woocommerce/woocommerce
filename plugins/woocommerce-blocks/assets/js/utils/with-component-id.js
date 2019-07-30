import { Component } from 'react';

const ids = [];

/**
 * HOC that gives a component a unique ID.
 *
 * This is an alternative for withInstanceId from @wordpress/compose to avoid using that dependency on the frontend.
 */
const withComponentId = ( OriginalComponent ) => {
	return class WrappedComponent extends Component {
		generateUniqueID() {
			const group = WrappedComponent.name;

			if ( ! ids[ group ] ) {
				ids[ group ] = 0;
			}

			ids[ group ]++;

			return ids[ group ];
		}

		render() {
			const componentId = this.generateUniqueID();

			return <OriginalComponent
				{ ...this.props }
				componentId={ componentId }
			/>;
		}
	};
};

export default withComponentId;
