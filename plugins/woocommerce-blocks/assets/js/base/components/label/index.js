/**
 * External dependencies
 */
import PropTypes from 'prop-types';
import { Fragment } from 'react';
import classNames from 'classnames';

/**
 * Component used to render an accessible text given a label and/or a
 * screenReaderLabel. The wrapper element and wrapper props can also be
 * specified via props.
 */
const Label = ( {
	label,
	screenReaderLabel,
	wrapperElement,
	wrapperProps,
} ) => {
	let Wrapper;

	if ( ! label && screenReaderLabel ) {
		Wrapper = wrapperElement || 'span';
		wrapperProps = {
			...wrapperProps,
			className: classNames(
				wrapperProps.className,
				'screen-reader-text'
			),
		};

		return <Wrapper { ...wrapperProps }>{ screenReaderLabel }</Wrapper>;
	}

	Wrapper = wrapperElement || Fragment;

	if ( label && screenReaderLabel && label !== screenReaderLabel ) {
		return (
			<Wrapper { ...wrapperProps }>
				<span aria-hidden="true">{ label }</span>
				<span className="screen-reader-text">
					{ screenReaderLabel }
				</span>
			</Wrapper>
		);
	}

	return <Wrapper { ...wrapperProps }>{ label }</Wrapper>;
};

Label.propTypes = {
	label: PropTypes.string,
	screenReaderLabel: PropTypes.string,
	wrapperElement: PropTypes.elementType,
	wrapperProps: PropTypes.object,
};

Label.defaultProps = {
	wrapperProps: {},
};

export default Label;
