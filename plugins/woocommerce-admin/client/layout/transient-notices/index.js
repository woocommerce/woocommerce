/**
 * External dependencies
 */
import classnames from 'classnames';
import { Component } from '@wordpress/element';
import { compose } from '@wordpress/compose';
import PropTypes from 'prop-types';
import { withDispatch, withSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import SnackbarList from './snackbar/list';
import './style.scss';

class TransientNotices extends Component {
	render() {
		const { className, notices, onRemove, onRemove2 } = this.props;
		const classes = classnames(
			'woocommerce-transient-notices',
			'components-notices__snackbar',
			className
		);

		return (
			<SnackbarList
				notices={ notices }
				className={ classes }
				onRemove={ onRemove }
				onRemove2={ onRemove2 }
			/>
		);
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
	withSelect( ( select ) => {
		// NOTE: This uses core/notices2, if this file is copied back upstream
		// to Gutenberg this needs to be changed back to just core/notices.
		const notices = select( 'core/notices' ).getNotices();
		const notices2 = select( 'core/notices2' ).getNotices();

		return { notices: notices.concat( notices2 ) };
	} ),
	withDispatch( ( dispatch ) => ( {
		// NOTE: This uses core/notices2, if this file is copied back upstream
		// to Gutenberg this needs to be changed back to core/notices.
		onRemove: dispatch( 'core/notices' ).removeNotice,
		onRemove2: dispatch( 'core/notices2' ).removeNotice,
	} ) )
)( TransientNotices );
