import { base_host, base_url } from './config.js';

/* set common headers */
const htmlRequestHeader = {
    accept:
      'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9'
  };

const jsonRequestHeader = {
    accept: 'application/json, text/javascript, */*; q=0.01'
  };

const allRequestHeader = {
    accept: '*//*'
  };

const commonRequestHeaders = {
    //'accept-encoding': 'gzip, deflate, br',
    'accept-language': 'en-US,en;q=0.9'
  };
  
const commonGetRequestHeaders = {
    connection: 'keep-alive',
    host: `${base_host}`,
    'sec-fetch-dest': 'document',
    'sec-fetch-mode': 'navigate',
    'sec-fetch-site': 'same-origin',
    'sec-fetch-user': '?1',
    'upgrade-insecure-requests': '1'
  };
  
const commonPostRequestHeaders = {
    //'content-type':
    //          'application/x-www-form-urlencoded;type=content-type;mimeType=application/x-www-form-urlencoded',
    origin: `${base_url}`,
    'sec-fetch-dest': 'empty',
    'sec-fetch-mode': 'cors',
    'sec-fetch-site': 'same-origin',
    'x-requested-with': 'XMLHttpRequest'
  };
  
const commonNonStandardHeaders = {
    'sec-ch-ua':
      '" Not;A Brand";v="99", "Google Chrome";v="91", "Chromium";v="91"',
    'sec-ch-ua-mobile': '?0'
  };

export {
    htmlRequestHeader,
    jsonRequestHeader,
    allRequestHeader,
    commonRequestHeaders,
    commonGetRequestHeaders,
    commonPostRequestHeaders,
    commonNonStandardHeaders,
};
