/** @format */
/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import PropTypes from 'prop-types';
import classnames from 'classnames';

/**
 * Internal dependencies
 */
import './style.scss';

const DropdownButton = props => {
	const { labels, isOpen, ...otherProps } = props;
	const buttonClasses = classnames( 'woocommerce-dropdown-button', {
		'is-open': isOpen,
		'is-multi-line': labels.length > 1,
	} );
	return (
		<Button className={ buttonClasses } aria-expanded={ isOpen } { ...otherProps }>
			<div className="woocommerce-dropdown-button__labels">
				{ labels.map( label => <p>{ label }</p> ) }
			</div>
		</Button>
	);
};

DropdownButton.propTypes = {
	labels: PropTypes.array,
	isOpen: PropTypes.bool,
};

export default DropdownButton;
