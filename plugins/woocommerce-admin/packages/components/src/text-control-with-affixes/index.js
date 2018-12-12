/** @format */
/**
 * External dependencies
 */
import { Component } from '@wordpress/element';
import PropTypes from 'prop-types';
import classNames from 'classnames';
import { TextControl } from '@wordpress/components';

class TextControlWithAffixes extends Component {
	render() {
		const { noWrap, prefix, suffix, ...rest } = this.props;

		return (
			<div className={ classNames( 'text-control-with-affixes', { 'no-wrap': noWrap } ) }>
				{ prefix && <span className="text-control-with-affixes__prefix">{ prefix }</span> }

				<TextControl { ...rest } />

				{ suffix && <span className="text-control-with-affixes__suffix">{ suffix }</span> }
			</div>
		);
	}
}

TextControlWithAffixes.propTypes = {
    noWrap: PropTypes.bool,
    prefix: PropTypes.node,
    suffix: PropTypes.node,
};

export default TextControlWithAffixes;
