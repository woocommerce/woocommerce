/** @format */
/**
 * External dependencies
 */
import classnames from 'classnames';
import { Component } from '@wordpress/element';
import { CSSTransition } from 'react-transition-group';
import { noop } from 'lodash';
import { Notice } from '@wordpress/components';
import PropTypes from 'prop-types';
import { speak } from '@wordpress/a11y';

class TransientNotice extends Component {
	constructor( props ) {
		super( props );

		this.state = {
			visible: false,
			timeout: null,
		};
	}

	componentDidMount() {
		const exitTime = this.props.exitTime;
		const timeout = setTimeout(
			() => {
				this.setState( { visible: false } );
			},
			exitTime,
			name,
			exitTime
		);
		/* eslint-disable react/no-did-mount-set-state */
		this.setState( { visible: true, timeout } );
		/* eslint-enable react/no-did-mount-set-state */
		speak( this.props.message );
	}

	componentWillUnmount() {
		clearTimeout( this.state.timeout );
	}

	render() {
		const { actions, className, isDismissible, message, onRemove, status } = this.props;
		const classes = classnames( 'woocommerce-transient-notice', className );

		return (
			<CSSTransition in={ this.state.visible } timeout={ 300 } classNames="slide">
				<div className={ classes }>
					<Notice
						status={ status }
						isDismissible={ isDismissible }
						onRemove={ onRemove }
						actions={ actions }
					>
						{ message }
					</Notice>
				</div>
			</CSSTransition>
		);
	}
}

TransientNotice.propTypes = {
	/**
	 * Array of action objects.
	 * See https://wordpress.org/gutenberg/handbook/designers-developers/developers/components/notice/
	 */
	actions: PropTypes.array,
	/**
	 * Additional class name to style the component.
	 */
	className: PropTypes.string,
	/**
	 * Determines if the notice dimiss button should be shown.
	 * See https://wordpress.org/gutenberg/handbook/designers-developers/developers/components/notice/
	 */
	isDismissible: PropTypes.bool,
	/**
	 * Function called when dismissing the notice.
	 * See https://wordpress.org/gutenberg/handbook/designers-developers/developers/components/notice/
	 */
	onRemove: PropTypes.func,
	/**
	 * Type of notice to display.
	 * See https://wordpress.org/gutenberg/handbook/designers-developers/developers/components/notice/
	 */
	status: PropTypes.oneOf( [ 'success', 'error', 'warning' ] ),
	/**
	 * Time in milliseconds until exit.
	 */
	exitTime: PropTypes.number,
};

TransientNotice.defaultProps = {
	actions: [],
	className: '',
	exitTime: 7000,
	isDismissible: false,
	onRemove: noop,
	status: 'warning',
};

export default TransientNotice;
