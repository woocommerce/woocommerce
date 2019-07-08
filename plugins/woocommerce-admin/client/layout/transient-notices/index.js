/** @format */
/**
 * External dependencies
 */
import classnames from 'classnames';
import { Component } from '@wordpress/element';
import { compose } from '@wordpress/compose';
import PropTypes from 'prop-types';
import { SnackbarList } from '@wordpress/components';
import { withDispatch } from '@wordpress/data';

/**
 * Internal dependencies
 */
import './style.scss';
import withSelect from 'wc-api/with-select';

class TransientNotices extends Component {
	render() {
		const { className, notices, onRemove } = this.props;
		const classes = classnames(
			'woocommerce-transient-notices',
			'components-notices__snackbar',
			className
		);

		return <SnackbarList notices={ notices } className={ classes } onRemove={ onRemove } />;
	}
}

TransientNotices.propTypes = {
	/**
	 * Additional class name to style the component.
	 */
	className: PropTypes.string,
	/**
	 * Array of notices to be displayed.
	 */
	notices: PropTypes.array,
};

export default compose(
	withSelect( select => {
		const notices = select( 'core/notices' ).getNotices();

		return { notices };
	} ),
	withDispatch( dispatch => ( {
		onRemove: dispatch( 'core/notices' ).removeNotice,
	} ) )
)( TransientNotices );
