<script setup lang="ts">

import {
    drawSelection,
    EditorView,
    highlightActiveLineGutter,
    highlightSpecialChars,
    keymap,
    lineNumbers,
    ViewUpdate
} from "@codemirror/view"
import {EditorState, Compartment} from "@codemirror/state"
import {bracketMatching, defaultHighlightStyle, syntaxHighlighting} from "@codemirror/language"
import {defaultKeymap, history, historyKeymap} from "@codemirror/commands"
import {highlightSelectionMatches, searchKeymap} from "@codemirror/search"
import {autocompletion, completionKeymap} from "@codemirror/autocomplete"
import {highlightStyle, theme} from "../stringparsing";

import {stringParsing} from "codemirror-lang-stringparsing";
import {onMounted} from "vue";

const emit = defineEmits(['update'])

const props = defineProps<{
    viewOnly: boolean,
    multiline?: boolean,
    propName: string,
    propValue?: string,
    label: string
}>();

let view: EditorView;

const readOnly = new Compartment;

const setReadOnly = (newValue) => {
    view.dispatch({
        effects: readOnly.reconfigure(EditorState.readOnly.of(newValue))
    });
}

onMounted(() => {

    const extensions = [
        keymap.of([
            ...defaultKeymap,
            ...searchKeymap,
            ...historyKeymap,
            ...completionKeymap
        ]),
        EditorView.updateListener.of((v: ViewUpdate) => {
            if (v.docChanged) {
                emit('update', v.state.doc.toString());
            }
        }),
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
        theme,
        readOnly.of(EditorState.readOnly.of(props.viewOnly))
    ];

    if (props.multiline) {
        extensions.push(lineNumbers());
        extensions.push(highlightActiveLineGutter());
    } else {
        // Taken from the CodeMirror 'try' page.
        // Transaction filter that drops any changes that would make the document multiline
        extensions.push(EditorState.transactionFilter.of(tr => {
            return tr.newDoc.lines > 1 ? [] : [tr]
        }));
    }

    view = new EditorView({
        doc: props.propValue,
        extensions: extensions,
        parent: document.getElementById(props.propName)
    });

});

</script>

<template>
    <div>
        <label :for="propName" class="form-label">{{ label }}</label>
        <div :id="propName" class="editor"></div>
    </div>
</template>

<style scoped lang="scss">
@import 'resources/sass/variables';

.editor {
    border: 1px solid $primary;
}

</style>
