export type UploadImage = {
	id?: number;
} & Record< string, string >;

export interface Image {
	id: number;
	src: string;
	name: string;
	alt: string;
}

/**
 * Converts an uploaded image into an Image object.
 */
export function mapUploadImageToImage( upload: UploadImage ): Image | null {
	if ( ! upload.id ) return null;
	return {
		id: upload.id,
		name: upload.title,
		src: upload.url,
		alt: upload.alt,
	};
}
