/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import PropTypes from 'prop-types';
import classnames from 'classnames';
import { ButtonGroup, Button } from '@wordpress/components';
import { useState, Fragment } from '@wordpress/element';
import withComponentId from '@woocommerce/base-hocs/with-component-id';

/**
 * Internal dependencies
 */
import './editor.scss';

const ViewSwitcher = ( {
	className,
	label = __( 'View', 'woo-gutenberg-products-block' ),
	views,
	defaultView,
	componentId,
	render,
} ) => {
	const [ currentView, setCurrentView ] = useState( defaultView );
	const classes = classnames( className, 'wc-block-view-switch-control' );
	const htmlId = 'wc-block-view-switch-control-' + componentId;

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
							onClick={ () => {
								setCurrentView( view.value );
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
	// from withComponentId
	componentId: PropTypes.number.isRequired,
};

export default withComponentId( ViewSwitcher );
