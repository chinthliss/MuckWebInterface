<script lang="ts" setup>
import {EditorView, ViewUpdate} from "@codemirror/view"
import {drawSelection, highlightActiveLineGutter, highlightSpecialChars, keymap, lineNumbers} from "@codemirror/view"
import {bracketMatching, defaultHighlightStyle, syntaxHighlighting} from "@codemirror/language"
import {defaultKeymap, history, historyKeymap} from "@codemirror/commands"
import {highlightSelectionMatches, searchKeymap} from "@codemirror/search"
import {autocompletion, completionKeymap} from "@codemirror/autocomplete"
import {highlightStyle, theme} from "../stringparsing";

import {stringParsing} from "codemirror-lang-stringparsing";
import {onMounted} from "vue";

const emit = defineEmits(['update'])

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

<style scoped lang="scss">
@import 'resources/sass/variables';

#stringparsing-scratchpad {
    border: 1px solid $primary;
    // box-shadow: 0 0 2px 2px $primary;
}

</style>
