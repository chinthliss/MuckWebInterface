// Some shared values used by stringparsing.

import {HighlightStyle} from "@codemirror/language";
import {tags as t} from "@lezer/highlight";
import {EditorView} from "@codemirror/view";

export const highlightStyle = HighlightStyle.define(
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

const caret = '#ffffff';
const lineHighlight = '#ccccff';

export const theme = EditorView.theme({
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
