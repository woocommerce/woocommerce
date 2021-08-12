/**
 * External dependencies
 */
import PropTypes from 'prop-types';
import classnames from 'classnames';
import { Notice } from 'wordpress-components';

/**
 * Internal dependencies
 */
import './style.scss';

const getWooClassName = ( { status = 'default' } ) => {
	switch ( status ) {
		case 'error':
			return 'woocommerce-error';
		case 'success':
			return 'woocommerce-message';
		case 'info':
		case 'warning':
			return 'woocommerce-info';
	}
	return '';
};

const StoreNoticesContainer = ( { className, notices, removeNotice } ) => {
	const regularNotices = notices.filter(
		( notice ) => notice.type !== 'snackbar'
	);

	if ( ! regularNotices.length ) {
		return null;
	}

	const wrapperClass = classnames( className, 'wc-block-components-notices' );

	return (
		<div className={ wrapperClass }>
			{ regularNotices.map( ( props ) => (
				<Notice
					key={ 'store-notice-' + props.id }
					{ ...props }
					className={ classnames(
						'wc-block-components-notices__notice',
						getWooClassName( props )
					) }
					onRemove={ () => {
						if ( props.isDismissible ) {
							removeNotice( props.id );
						}
					} }
				>
					{ props.content }
				</Notice>
			) ) }
		</div>
	);
};

StoreNoticesContainer.propTypes = {
	className: PropTypes.string,
	notices: PropTypes.arrayOf(
		PropTypes.shape( {
			content: PropTypes.string.isRequired,
			id: PropTypes.string.isRequired,
			status: PropTypes.string.isRequired,
			isDismissible: PropTypes.bool,
			type: PropTypes.oneOf( [ 'default', 'snackbar' ] ),
		} )
	),
};

export default StoreNoticesContainer;
