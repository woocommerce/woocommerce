/**
 * External dependencies
 */
import { debounce, isArray, uniqueId } from 'lodash';
import { Component } from '@wordpress/element';
import { addAction, removeAction } from '@wordpress/hooks';

const ANIMATION_FRAME_PERIOD = 16;

/**
 * Creates a higher-order component which adds filtering capability to the
 * wrapped component. Filters get applied when the original component is about
 * to be mounted. When a filter is added or removed that matches the hook name,
 * the wrapped component re-renders.
 *
 * @param {string|Array} hookName Hook names exposed to be used by filters.
 *
 * @return {Function} Higher-order component factory.
 */
export default function useFilters( hookName ) {
	const hookNames = isArray( hookName ) ? hookName : [ hookName ];

	return function ( OriginalComponent ) {
		return class FilteredComponent extends Component {
			constructor( props ) {
				super( props );

				this.onHooksUpdated = this.onHooksUpdated.bind( this );
				this.namespace = uniqueId( 'wc-admin/use-filters/component-' );
				this.throttledForceUpdate = debounce( () => {
					this.forceUpdate();
				}, ANIMATION_FRAME_PERIOD );

				addAction( 'hookRemoved', this.namespace, this.onHooksUpdated );
				addAction( 'hookAdded', this.namespace, this.onHooksUpdated );
			}

			componentWillUnmount() {
				this.throttledForceUpdate.cancel();
				removeAction( 'hookRemoved', this.namespace );
				removeAction( 'hookAdded', this.namespace );
			}

			/**
			 * When a filter is added or removed for any matching hook name, the wrapped component should re-render.
			 *
			 * @param {string} updatedHookName  Name of the hook that was updated.
			 */
			onHooksUpdated( updatedHookName ) {
				if ( hookNames.indexOf( updatedHookName ) !== -1 ) {
					this.throttledForceUpdate();
				}
			}

			render() {
				return <OriginalComponent { ...this.props } />;
			}
		};
	};
}
