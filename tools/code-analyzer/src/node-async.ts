/**
 * External dependencies
 */
import { createWriteStream, unlink } from 'fs';
import { get as nodeHttpsGet } from 'https';

/**
 * A promise wrapper to downloads files using HTTPS.
 *
 * @param {string} url The URL to download.
 * @param {string} filePath The filepath of the downloaded file.
 */
export async function download(
	url: string,
	filePath: string
): Promise<boolean> {
	return new Promise((resolve, reject) => {
		const file = createWriteStream(filePath);
		let fileInfo = false;

		const request = nodeHttpsGet(url, (response) => {
			if (response.statusCode !== 200) {
				reject(
					new Error(`Failed to get '${url}' (${response.statusCode})`)
				);
				return;
			}

			fileInfo = true;
			response.pipe(file);
		});

		// The destination stream is ended by the time it's called.
		file.on('finish', () => resolve(fileInfo));

		request.on('error', (err) => {
			unlink(filePath, () => reject(err));
		});

		file.on('error', (err) => {
			unlink(filePath, () => reject(err));
		});

		request.end();
	});
}
