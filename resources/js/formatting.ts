/**
 * Frequently used formatting functions
 */

/**
 * Takes a string representation of a Carbon object exported from PHP and turns it into something friendlier
 */
export const carbonToString = (carbonString: string | null): string => {
    if (!carbonString) return '--';
    return new Date(carbonString).toLocaleString();
};

/**
 * Takes a USD value and turns it into something friendlier
 */
export const usdToString = (usd: number | string | null) => {
    if (!usd) return '--';
    if (typeof usd === 'number') usd = usd.toFixed(2);
    return "$" + usd;
};

/**
 * Converts the first letter of a string to be a capital one
 */
export const capital = (text: string): string => {
    if (!text) return '';
    return text.substring(0, 1).toUpperCase() + text.slice(1);
};

/**
 * Joins an array of strings into one string
 */
export const arrayToList = (arrayToParse: string[], emptyWord: string = '') => {
    if (!arrayToParse.length) return emptyWord;
    return arrayToParse.join(', ');
};
