import { Browser, Page } from 'puppeteer';

declare global {
	const page: Page;
	const browser: Browser;
	const browserName: string;
}
