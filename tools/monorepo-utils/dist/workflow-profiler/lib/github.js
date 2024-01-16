"use strict";
var __awaiter = (this && this.__awaiter) || function (thisArg, _arguments, P, generator) {
    function adopt(value) { return value instanceof P ? value : new P(function (resolve) { resolve(value); }); }
    return new (P || (P = Promise))(function (resolve, reject) {
        function fulfilled(value) { try { step(generator.next(value)); } catch (e) { reject(e); } }
        function rejected(value) { try { step(generator["throw"](value)); } catch (e) { reject(e); } }
        function step(result) { result.done ? resolve(result.value) : adopt(result.value).then(fulfilled, rejected); }
        step((generator = generator.apply(thisArg, _arguments || [])).next());
    });
};
Object.defineProperty(exports, "__esModule", { value: true });
exports.requestPaginatedData = void 0;
/**
 * Internal dependencies
 */
const api_1 = require("../../core/github/api");
/**
 * Helper method for getting data from GitHub REST API in paginated format.
 *
 * This function is used to process multiple pages of GitHub data by keeping track of running totals.
 * The requirements `totals` are properties `count` and `total_number`. A processing function `processPage` is also passed to handle each page's data by updating the `totals` object.
 *
 * @param {Object}   totals         An object for keeping track of the total data.
 * @param {string}   endpoint       API endpoint
 * @param {Object}   requestOptions API request options
 * @param {Function} processPage    A function to handle returned data and update totals
 * @param            page           Page number to start from
 * @param            per_page       Number of items per page
 * @return {Object}                The updated totals object
 */
const requestPaginatedData = (totals, endpoint, requestOptions, processPage, page = 1, per_page = 50) => __awaiter(void 0, void 0, void 0, function* () {
    const { data } = yield (0, api_1.octokitWithAuth)().request(endpoint, Object.assign(Object.assign({}, requestOptions), { page,
        per_page }));
    let resultingTotals = processPage(data, totals);
    const { total_count } = data;
    if (total_count > resultingTotals.count_items_processed) {
        resultingTotals = yield (0, exports.requestPaginatedData)(resultingTotals, endpoint, requestOptions, processPage, page + 1);
    }
    return resultingTotals;
});
exports.requestPaginatedData = requestPaginatedData;
