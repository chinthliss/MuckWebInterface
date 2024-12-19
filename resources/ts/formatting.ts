/**
 * Frequently used formatting functions
 */

import {AnsiUp} from "ansi_up";
const ansi_up = new AnsiUp();

/**
 * Takes a string representation of a Carbon object exported from PHP and turns it into something friendlier
 * Type is to support DataTables - if it's for sorting, the conversion to a string isn't done.
 */
export const carbonToString = (carbonString: string | null | undefined, type?: string): string => {
    if (!carbonString) return '--';
    if (type === 'sort') return carbonString;
    return new Date(carbonString).toLocaleString();
};

/**
 * Takes a UTC timestamp exported from the muck and turns it into something friendlier
 */
export const timestampToString = (timestamp: number | null | undefined): string => {
    if (!timestamp) return '--';
    return new Date(timestamp * 1000).toLocaleString();
};

/**
 * Takes a USD value and turns it into something friendlier
 */
export const usdToString = (usd: number | string | null): string => {
    if (!usd) return '--';
    if (typeof usd === 'number') usd = usd.toFixed(2);
    return "$" + usd;
};

/**
 * Converts the first letter of a string to be a capital one
 */
export const capital = (text: string | null | undefined): string => {
    if (!text) return '';
    return text.substring(0, 1).toUpperCase() + text.slice(1);
};

/**
 * Joins an array of strings into one string
 */
export const arrayToList = (arrayToParse: string[], emptyWord: string = ''): string => {
    if (!arrayToParse.length) return emptyWord;
    return arrayToParse.join(', ');
};

/**
 * Joins an array of strings into one string with newlines
 */
export const arrayToStringWithNewlines = (arrayToParse: string[], emptyWord: string = ''): string => {
    if (!arrayToParse.length) return emptyWord;
    return arrayToParse.join('\n');
};

/**
 * Replaces key parts of HTML so that it can be used without being parsed
 */
export const escapeHTML = (text: string): string  => {
    return text.replace(
        /[&<>'"]/g,
        found =>
            ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                "'": '&#39;',
                '"': '&quot;'
            }[found] || found)
    );
}

/**
 * Converts the parsed ANSI in a string into an HTML representation
 * Note that this will escape special characters so should not be used with escapeHTML
 */
export const ansiToHtml = (ansi: string): string => {
    return ansi_up.ansi_to_html(ansi);
}
