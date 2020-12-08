/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import PropTypes from 'prop-types';
import classnames from 'classnames';
import { ButtonGroup, Button } from '@wordpress/components';
import { useState, Fragment } from '@wordpress/element';
import { withInstanceId } from '@wordpress/compose';

/**
 * Internal dependencies
 */
import './editor.scss';

const ViewSwitcher = ( {
	className,
	label = __( 'View', 'woocommerce' ),
	views,
	defaultView,
	instanceId,
	render,
} ) => {
	const [ currentView, setCurrentView ] = useState( defaultView );
	const classes = classnames( className, 'wc-block-view-switch-control' );
	const htmlId = 'wc-block-view-switch-control-' + instanceId;

	return (
		<Fragment>
			<div className={ classes }>
				<label
					htmlFor={ htmlId }
					className="wc-block-view-switch-control__label"
				>
					{ label + ': ' }
				</label>
				<ButtonGroup id={ htmlId }>
					{ views.map( ( view ) => (
						<Button
							key={ view.value }
							isPrimary={ currentView === view.value }
							isLarge
							aria-pressed={ currentView === view.value }
							onMouseDown={ () => {
								if ( currentView !== view.value ) {
									setCurrentView( view.value );
								}
							} }
							onClick={ () => {
								if ( currentView !== view.value ) {
									setCurrentView( view.value );
								}
							} }
						>
							{ view.name }
						</Button>
					) ) }
				</ButtonGroup>
			</div>
			{ render( currentView ) }
		</Fragment>
	);
};

ViewSwitcher.propTypes = {
	/**
	 * Custom class name to add to component.
	 */
	className: PropTypes.string,
	/**
	 * List of views.
	 */
	views: PropTypes.arrayOf(
		PropTypes.shape( {
			name: PropTypes.string.isRequired,
			value: PropTypes.string.isRequired,
		} )
	).isRequired,
	/**
	 * The default selected view.
	 */
	defaultView: PropTypes.string.isRequired,
	/**
	 * Render prop for selected views.
	 */
	render: PropTypes.func.isRequired,
	// from withInstanceId
	instanceId: PropTypes.number.isRequired,
};

export default withInstanceId( ViewSwitcher );
