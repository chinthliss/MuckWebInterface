<script setup lang="ts">

import {EditorView, highlightActiveLineGutter, lineNumbers, ViewUpdate} from "@codemirror/view"
import {Compartment, EditorState} from "@codemirror/state"
import {stringParsingDefaultExtensions} from "../stringparsing";
import {onMounted, watch} from "vue";

const emit = defineEmits(['input'])

const props = defineProps<{
    viewOnly: boolean,
    multiline?: boolean,
    propName: string,
    propValue?: string,
    label: string
}>();

let view: EditorView;

// This needs a compartment as we'll be toggling it externally via watching when we change form
const readOnlySetting = new Compartment;

const setReadOnly = (newValue: any) => {
    view.dispatch({
        effects: readOnlySetting.reconfigure(EditorState.readOnly.of(newValue))
    });
}

watch(() => props.viewOnly, (newValue, _oldValue) => {
    setReadOnly(newValue);
});

onMounted(() => {
    const extensions = [
        ...stringParsingDefaultExtensions,
        readOnlySetting.of(EditorState.readOnly.of(props.viewOnly)),
        EditorView.updateListener.of((v: ViewUpdate) => {
            if (v.docChanged) {
                emit('input', {
                    id: props.propName,
                    value: v.state.doc.toString()
                });
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
        parent: document.getElementById(props.propName)!
    });
});

</script>

<template>
    <div>
        <label :for="propName" class="form-label fw-bold text-primary">{{ label }}</label>
        <div :id="propName" class="editor"></div>
    </div>
</template>

<style scoped lang="scss">
@use "../../sass/variables" as *;

.editor {
    border: 1px solid $primary;
}

</style>
