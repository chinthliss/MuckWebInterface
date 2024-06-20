// Some shared default values used by stringParsing components.

import {bracketMatching, defaultHighlightStyle, HighlightStyle, syntaxHighlighting} from "@codemirror/language";
import {tags as t} from "@lezer/highlight";
import {drawSelection, EditorView, highlightSpecialChars, keymap} from "@codemirror/view";
import {defaultKeymap, history, historyKeymap} from "@codemirror/commands";
import {highlightSelectionMatches, searchKeymap} from "@codemirror/search";
import {autocompletion, completionKeymap} from "@codemirror/autocomplete";
import {stringParsing} from "codemirror-lang-stringparsing";

const caret = '#ffffff';
const lineHighlight = '#ccccff';

export const stringParsingHighlightStyle = HighlightStyle.define(
    [
        { tag: t.keyword, color: "#7b87b8" },
        { tag: t.controlKeyword, color: "#f8835c" },
        { tag: t.processingInstruction, color: "#8b00ff" },
        { tag: t.arithmeticOperator, color: "#ab87b8" },
        { tag: t.comment, color: "#585858" },
        { tag: t.number, color: "#0b67b8" },
        { tag: t.variableName, color: "#0bb867" },
        { tag: t.compareOperator, color: "#b8b80b" }
    ],
    {all: {color: "#989898"}}
);

export const stringParsingTheme = EditorView.theme({
    '.cm-content': {
        caretColor: caret,
    },
    '.cm-cursor, .cm-dropCursor': {
        borderLeftColor: caret
    },
    '.cm-activeLineGutter': {
        backgroundColor: lineHighlight,
    },
});

export const stringParsingKeymap= keymap.of([
    ...defaultKeymap,
    ...searchKeymap,
    ...historyKeymap,
    ...completionKeymap
]);

export const stringParsingDefaultExtensions = [
    stringParsingKeymap,
    highlightSpecialChars(),
    history(),
    drawSelection(),
    EditorView.lineWrapping,
    bracketMatching(),
    autocompletion(),
    highlightSelectionMatches(),
    syntaxHighlighting(defaultHighlightStyle, {fallback: true}),
    syntaxHighlighting(stringParsingHighlightStyle),
    stringParsing(),
    stringParsingTheme
    ];
