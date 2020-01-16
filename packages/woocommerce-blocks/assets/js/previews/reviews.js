/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

const avatarPicture =
	'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQAAAQABAAD//gA7Q1JFQVRPUjogZ2QtanBlZyB2MS4wICh1c2luZyBJSkcgSlBFRyB2NjIpLCBxdWFsaXR5ID0gOTAK/9sAQwADAgIDAgIDAwMDBAMDBAUIBQUEBAUKBwcGCAwKDAwLCgsLDQ4SEA0OEQ4LCxAWEBETFBUVFQwPFxgWFBgSFBUU/9sAQwEDBAQFBAUJBQUJFA0LDRQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQU/8AAEQgAMAAwAwEiAAIRAQMRAf/EAB8AAAEFAQEBAQEBAAAAAAAAAAABAgMEBQYHCAkKC//EALUQAAIBAwMCBAMFBQQEAAABfQECAwAEEQUSITFBBhNRYQcicRQygZGhCCNCscEVUtHwJDNicoIJChYXGBkaJSYnKCkqNDU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6g4SFhoeIiYqSk5SVlpeYmZqio6Slpqeoqaqys7S1tre4ubrCw8TFxsfIycrS09TV1tfY2drh4uPk5ebn6Onq8fLz9PX29/j5+v/EAB8BAAMBAQEBAQEBAQEAAAAAAAABAgMEBQYHCAkKC//EALURAAIBAgQEAwQHBQQEAAECdwABAgMRBAUhMQYSQVEHYXETIjKBCBRCkaGxwQkjM1LwFWJy0QoWJDThJfEXGBkaJicoKSo1Njc4OTpDREVGR0hJSlNUVVZXWFlaY2RlZmdoaWpzdHV2d3h5eoKDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uLj5OXm5+jp6vLz9PX29/j5+v/aAAwDAQACEQMRAD8A+t6KKtaXps2sahBZ24DTTNtXPQepPsBzQBVo/OvddD+H2j6Nbqr2sd7Pj5prhQ2T7A8Cl1z4f6PrNuyrax2c+Plmt1CkH3A4NAHhP50Vb1bTJtG1G4srgASwttOOh9CPYjBqp+VABXX/AAreNfF8If7zROEz/ex/hmuQq1pT3cepW7WAdrxXBiEYyS30oA+kKKp6RPeXOnwyX9strdEfPErBgD9f6UurT3dtp80ljbC7ugPkiZgoJ+tAHkHxWeNvFsgT7ywoHx/e6/yIrj6t6vJdy6lcvfh1vGcmUSDBB+lVKACvbvh/4Qj8PaYlxOgOozrudmHMYPRB/X3+leVeDrBdT8UabbuNyGUMwPcL8xH6V9BUAJRRRQByfxA8IR+IdMe4gjA1GBSyMo5kA6of6e/1rxGvpr/PWvn3xjYLpnijUrdBtQTFlHoGG4D9aAP/2Q==';

export const previewReviews = [
	{
		id: 1,
		date_created: '2019-07-15T17:05:04',
		formatted_date_created: __(
			'July 15, 2019',
			'woocommerce'
		),
		date_created_gmt: '2019-07-15T15:05:04',
		product_id: 0,
		product_name: __( 'WordPress Pennant', 'woocommerce' ),
		product_permalink: '#',
		/* translators: An example person name used for the block previews. */
		reviewer: __( 'Alice', 'woocommerce' ),
		review: `<p>${ __(
			"I bought this product last week and I'm very happy with it.",
			'woocommerce'
		) }</p>\n`,
		reviewer_avatar_urls: {
			'48': avatarPicture,
			'96': avatarPicture,
		},
		rating: 5,
		verified: true,
	},
	{
		id: 2,
		date_created: '2019-07-12T12:39:39',
		formatted_date_created: __(
			'July 12, 2019',
			'woocommerce'
		),
		date_created_gmt: '2019-07-12T10:39:39',
		product_id: 0,
		product_name: __( 'WordPress Pennant', 'woocommerce' ),
		product_permalink: '#',
		/* translators: An example person name used for the block previews. */
		reviewer: __( 'Bob', 'woocommerce' ),
		review: `<p>${ __(
			'This product is awesome, I love it!',
			'woocommerce'
		) }</p>\n`,
		reviewer_avatar_urls: {
			'48': avatarPicture,
			'96': avatarPicture,
		},
		rating: null,
		verified: false,
	},
];
