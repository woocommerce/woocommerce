/** @format */
/**
 * External dependencies
 */
import classnames from 'classnames';
import { parse, stringify } from 'qs';
import PropTypes from 'prop-types';
import url from 'url';
import { isString } from 'lodash';
import crypto from 'crypto';

/**
 * Internal dependencies
 */
import './style.scss';

const Gravatar = ( { alt, title, size, user, className } ) => {
	const classes = classnames( 'woocommerce-gravatar', className, {
		'is-placeholder': ! user,
	} );

	const getResizedImageURL = imageURL => {
		const parsedURL = url.parse( imageURL );
		const query = parse( parsedURL.query );

		query.s = size;
		query.d = 'mp';

		parsedURL.search = stringify( query );
		return url.format( parsedURL );
	};

	const getAvatarURLFromEmail = email => {
		return (
			'https://www.gravatar.com/avatar/' +
			crypto
				.createHash( 'md5' )
				.update( email )
				.digest( 'hex' )
		);
	};

	const altText = alt || ( user && ( user.display_name || user.name ) ) || '';

	let avatarURL = 'https://www.gravatar.com/avatar/0?s=' + size + '&d=mp';
	if ( user ) {
		avatarURL = getResizedImageURL(
			isString( user ) ? getAvatarURLFromEmail( user ) : user.avatar_URLs[ 96 ]
		);
	}

	return (
		<img
			alt={ altText }
			title={ title }
			className={ classes }
			src={ avatarURL }
			width={ size }
			height={ size }
		/>
	);
};

Gravatar.propTypes = {
	user: PropTypes.oneOfType( [ PropTypes.object, PropTypes.string ] ),
	alt: PropTypes.string,
	title: PropTypes.string,
	size: PropTypes.number,
	className: PropTypes.string,
};

Gravatar.defaultProps = {
	size: 60,
};

export default Gravatar;
