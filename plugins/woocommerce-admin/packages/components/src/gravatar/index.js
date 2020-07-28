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
 * Display a users Gravatar.
 *
 * @param root0
 * @param root0.alt
 * @param root0.title
 * @param root0.size
 * @param root0.user
 * @param root0.className
 * @return {Object} -
 */
const Gravatar = ( { alt, title, size, user, className } ) => {
	const classes = classnames( 'woocommerce-gravatar', className, {
		'is-placeholder': ! user,
	} );

	const getResizedImageURL = ( imageURL ) => {
		const parsedURL = url.parse( imageURL );
		const query = parse( parsedURL.query );

		query.s = size;
		query.d = 'mp';

		parsedURL.search = stringify( query );
		return url.format( parsedURL );
	};

	const getAvatarURLFromEmail = ( email ) => {
		return (
			'https://www.gravatar.com/avatar/' +
			crypto.createHash( 'md5' ).update( email ).digest( 'hex' )
		);
	};

	const altText = alt || ( user && ( user.display_name || user.name ) ) || '';

	let avatarURL = 'https://www.gravatar.com/avatar/0?s=' + size + '&d=mp';
	if ( user ) {
		avatarURL = getResizedImageURL(
			isString( user )
				? getAvatarURLFromEmail( user )
				: user.avatar_URLs[ 96 ]
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
	/**
	 * The address to hash for displaying a Gravatar. Can be an email address or WP-API user object.
	 */
	user: PropTypes.oneOfType( [ PropTypes.object, PropTypes.string ] ),
	/**
	 * Text to display as the image alt attribute.
	 */
	alt: PropTypes.string,
	/**
	 * Text to use for the image's title
	 */
	title: PropTypes.string,
	/**
	 * Default 60. The size of Gravatar to request.
	 */
	size: PropTypes.number,
	/**
	 * Additional CSS classes.
	 */
	className: PropTypes.string,
};

Gravatar.defaultProps = {
	size: 60,
};

export default Gravatar;
