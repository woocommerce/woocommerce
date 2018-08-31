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

/**
 * This component can be used to show an item styled as a "tag", optionally with an `X` + "remove".
 * Generally this is used in a collection of selected items, see the Search component.
 *
 * @return { object } -
 */
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
	/**
	 * The ID for this item, used in the remove function.
	 */
	id: PropTypes.number.isRequired,
	/**
	 * The name for this item, displayed as the tag's text.
	 */
	label: PropTypes.string.isRequired,
	/**
	 * A function called when the remove X is clicked. If not used, no X icon will display.
	 */
	remove: PropTypes.func,
	/**
	 * The label for removing this item (shown when hovering on X, or read to screen reader users). Defaults to "Remove tag".
	 */
	removeLabel: PropTypes.string,
	/**
	 * A more descriptive label for screen reader users. Defaults to the `name` prop.
	 */
	screenReaderLabel: PropTypes.string,
};

Tag.defaultProps = {
	removeLabel: __( 'Remove tag', 'wc-admin' ),
};

export default withInstanceId( Tag );
