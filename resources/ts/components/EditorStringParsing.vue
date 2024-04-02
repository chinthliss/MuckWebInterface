<script lang="ts" setup>
import {EditorView, ViewUpdate} from "@codemirror/view"
import {drawSelection, highlightActiveLineGutter, highlightSpecialChars, keymap, lineNumbers} from "@codemirror/view"
import {bracketMatching, defaultHighlightStyle, HighlightStyle, syntaxHighlighting} from "@codemirror/language"
import {defaultKeymap, history, historyKeymap} from "@codemirror/commands"
import {highlightSelectionMatches, searchKeymap} from "@codemirror/search"
import {autocompletion, completionKeymap} from "@codemirror/autocomplete"

import {stringParsing} from "codemirror-lang-stringparsing";
import {tags} from "@lezer/highlight"
import {onMounted} from "vue";

const emit = defineEmits(['update'])

const highlightStyle = HighlightStyle.define(
    [
        {tag: tags.controlKeyword, color: "#7b87b8"},
        {tag: tags.processingInstruction, color: "#fb87b8"},
        {tag: tags.arithmeticOperator, color: "#ab87b8"},
        {tag: tags.comment, color: "#585858"},
        {tag: tags.number, color: "#0b67b8"},
        {tag: tags.variableName, color: "#0bb867"},
        {tag: tags.compareOperator, color: "#b8b80b"}
    ],
    {all: {color: "#989898"}}
);

const caret = '#ffffff';
const lineHighlight = '#ccccff';

const theme = EditorView.theme({
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

onMounted(() => {
    const scratchpad = new EditorView({
        doc: "Hello World",
        extensions: [
            keymap.of([
                ...defaultKeymap,
                ...searchKeymap,
                ...historyKeymap,
                ...completionKeymap
            ]),
            EditorView.updateListener.of((v:ViewUpdate) => {
                if (v.docChanged) {
                    emit('update', v.state.doc.toString());
                }
            }),
            lineNumbers(),
            highlightActiveLineGutter(),
            highlightSpecialChars(),
            history(),
            drawSelection(),
            syntaxHighlighting(defaultHighlightStyle, {fallback: true}),
            syntaxHighlighting(highlightStyle),
            EditorView.lineWrapping,
            bracketMatching(),
            autocompletion(),
            highlightSelectionMatches(),
            stringParsing(),
            theme
        ],
        parent: document.getElementById('stringparsing-scratchpad')
    });
});
</script>

<template>
    <div id="stringparsing-scratchpad"></div>
</template>

<style scoped>

</style>
