/** @format */
/**
 * External dependencies
 */
import { Component } from '@wordpress/element';
import PropTypes from 'prop-types';
import classNames from 'classnames';
import { BaseControl } from '@wordpress/components';
import { withInstanceId } from '@wordpress/compose';

class TextControlWithAffixes extends Component {
    render() {
        const {
            label,
            value,
            help,
            className,
            instanceId,
            onChange,
            noWrap,
            prefix,
            suffix,
            type = 'text',
            ...props
        } = this.props;

        const id = `inspector-text-control-with-affixes-${ instanceId }`;
        const onChangeValue = ( event ) => onChange( event.target.value );

        return (
            <BaseControl label={ label } id={ id } help={ help } className={ className }>
                <div className={ classNames( 'text-control-with-affixes', { 'no-wrap': noWrap } ) }>
                    { prefix && <span className="text-control-with-affixes__prefix">{ prefix }</span> }

                    <input className="components-text-control__input"
                        type={ type }
                        id={ id }
                        value={ value }
                        onChange={ onChangeValue }
                        aria-describedby={ !! help ? id + '__help' : undefined }
                        { ...props }
                    />

                    { suffix && <span className="text-control-with-affixes__suffix">{ suffix }</span> }
                </div>
            </BaseControl>
        );
    }
}

TextControlWithAffixes.propTypes = {
    noWrap: PropTypes.bool,
    prefix: PropTypes.node,
    suffix: PropTypes.node,
};

export default withInstanceId( TextControlWithAffixes );
