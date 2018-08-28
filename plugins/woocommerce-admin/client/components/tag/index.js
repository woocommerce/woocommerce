/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import classnames from 'classnames';
import Gridicon from 'gridicons';
import { IconButton } from '@wordpress/components';
import PropTypes from 'prop-types';
import { withInstanceId } from '@wordpress/compose';

/**
 * Internal dependencies
 */
import './style.scss';

const Tag = ( { id, instanceId, label, remove, removeLabel, screenReaderLabel, className } ) => {
	screenReaderLabel = screenReaderLabel || label;
	const classes = classnames( 'woocommerce-tag', className, {
		'has-remove': !! remove,
	} );
	const labelId = `woocommerce-tag__label-${ instanceId }`;
	return (
		<span className={ classes }>
			<span className="woocommerce-tag__text" id={ labelId }>
				<span className="screen-reader-text">{ screenReaderLabel }</span>
				<span aria-hidden="true">{ label }</span>
			</span>

			{ remove && (
				<IconButton
					className="woocommerce-tag__remove"
					icon={ <Gridicon icon="cross-small" size={ 24 } /> }
					onClick={ remove( id ) }
					label={ removeLabel }
					aria-describedby={ labelId }
				/>
			) }
		</span>
	);
};

Tag.propTypes = {
	id: PropTypes.number.isRequired,
	label: PropTypes.string.isRequired,
	remove: PropTypes.func,
	removeLabel: PropTypes.string,
	screenReaderLabel: PropTypes.string,
};

Tag.defaultProps = {
	removeLabel: __( 'Remove tag', 'wc-admin' ),
};

export default withInstanceId( Tag );
