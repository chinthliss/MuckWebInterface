<script lang="ts" setup>
import {EditorView, highlightActiveLineGutter, lineNumbers, ViewUpdate} from "@codemirror/view"
import {stringParsingDefaultExtensions} from "../stringparsing";
import {onMounted} from "vue";

const emit = defineEmits(['update'])

onMounted(() => {
    new EditorView({
        doc: "Hello World",
        extensions: [
            ...stringParsingDefaultExtensions,

            lineNumbers(),
            highlightActiveLineGutter(),

            EditorView.updateListener.of((v: ViewUpdate) => {
                if (v.docChanged) {
                    emit('update', v.state.doc.toString());
                }
            })
        ],
        parent: document.getElementById('stringparsing-scratchpad')!
    });
});
</script>

<template>
    <div id="stringparsing-scratchpad"></div>
</template>

<style scoped lang="scss">
@use 'resources/sass/variables' as *;

#stringparsing-scratchpad {
    border: 1px solid $primary;
    // box-shadow: 0 0 2px 2px $primary;
}

</style>
