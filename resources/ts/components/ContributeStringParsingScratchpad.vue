<script setup lang="ts">
    import {onMounted} from "vue";
    import {EditorView, basicSetup} from "codemirror";
    import {stringParsing} from "codemirror-lang-stringparsing";


    onMounted(() => {
        const scratchpad = new EditorView({
            doc: "Hello World",
            extensions: [basicSetup, EditorView.lineWrapping, stringParsing()],
            parent: document.getElementById('stringparsing-scratchpad')
        })
    });
</script>

<template>
    <div id="stringparsing-scratchpad"></div>
</template>

<style scoped>

</style>
