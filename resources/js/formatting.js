/**
 * Frequently used formatting functions
 */

export const carbonToString = (carbonString) => {
    if (!carbonString) return '--';
    return new Date(carbonString).toLocaleString();
};

export const capital = (text) => {
    if (typeof (text) !== 'string') text = text.toString();
    if (!text) return '';
    return text && text.substring(0, 1).toUpperCase() + text.slice(1);
};
