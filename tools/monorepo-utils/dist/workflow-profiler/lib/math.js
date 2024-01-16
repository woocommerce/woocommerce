"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.calculate90thPercentile = exports.calculateMedian = exports.calculateMean = void 0;
/**
 * Calculate the mean value of an array of numbers.
 *
 * @param {Array} numbers Array of numbers
 * @return {number} Mean value
 */
const calculateMean = (numbers) => {
    if (numbers.length === 0) {
        return 0;
    }
    const sum = numbers.reduce(function (a, b) {
        return a + b;
    }, 0);
    return sum / numbers.length;
};
exports.calculateMean = calculateMean;
/**
 * Calculate the median value of an array of numbers.
 *
 * @param {Array} numbers Array of numbers
 * @return {number} Median value
 */
const calculateMedian = (numbers) => {
    const numbersCopy = [...numbers];
    if (numbersCopy.length === 0) {
        return 0;
    }
    // Sort the numbersCopy in ascending order
    numbersCopy.sort(function (a, b) {
        return a - b;
    });
    const middleIndex = Math.floor(numbersCopy.length / 2);
    if (numbersCopy.length % 2 === 0) {
        // If the array length is even, return the average of the two middle values
        return ((numbersCopy[middleIndex - 1] + numbersCopy[middleIndex]) / 2);
    }
    // If the array length is odd, return the middle value
    return numbersCopy[middleIndex];
};
exports.calculateMedian = calculateMedian;
/**
 * Get the 90th percentile value of an array of numbers.
 *
 * @param {Array} numbers Array of numbers
 * @return {number} 90th percentile value
 */
const calculate90thPercentile = (numbers) => {
    const numbersCopy = [...numbers];
    // Sorting the numbers in ascending order
    const sortedNumbers = numbersCopy.sort((a, b) => a - b);
    // Calculating the index for the 90th percentile
    const index = Math.ceil(sortedNumbers.length * 0.9) - 1;
    // Returning the 90th percentile value
    return sortedNumbers[index];
};
exports.calculate90thPercentile = calculate90thPercentile;
