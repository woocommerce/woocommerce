/** @format */
/**
 * External dependencies
 */
import classnames from 'classnames';
import { Component } from '@wordpress/element';
import { compose } from '@wordpress/compose';
import PropTypes from 'prop-types';

/**
 * Internal dependencies
 */
import './style.scss';
import TransientNotice from './transient-notice';
import withSelect from 'wc-api/with-select';

class TransientNotices extends Component {
	render() {
		const { className, notices } = this.props;
		const classes = classnames( 'woocommerce-transient-notices', className );

		return (
			<section className={ classes }>
				{ notices && notices.map( ( notice, i ) => <TransientNotice key={ i } { ...notice } /> ) }
			</section>
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
	withSelect( select => {
		const { getNotices } = select( 'wc-admin' );
		const notices = getNotices();

		return { notices };
	} )
)( TransientNotices );
