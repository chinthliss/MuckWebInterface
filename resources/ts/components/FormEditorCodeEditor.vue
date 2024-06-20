<script setup lang="ts">

import {EditorView, highlightActiveLineGutter, lineNumbers, ViewUpdate} from "@codemirror/view"
import {Compartment, EditorState} from "@codemirror/state"
import {stringParsingDefaultExtensions} from "../stringparsing";
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

// This needs a compartment as we'll be toggling it when we change form
const readOnly = new Compartment;

const setReadOnly = (newValue) => {
    view.dispatch({
        effects: readOnly.reconfigure(EditorState.readOnly.of(newValue))
    });
}

onMounted(() => {

    const extensions = [
        ...stringParsingDefaultExtensions,
        readOnly.of(EditorState.readOnly.of(props.viewOnly)),
        EditorView.updateListener.of((v: ViewUpdate) => {
            if (v.docChanged) {
                emit('update', v.state.doc.toString());
            }
        })
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
