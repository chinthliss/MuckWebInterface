<script setup lang="ts">

import {ref, Ref} from "vue";
import FormEditorFormSelection from "./FormEditorFormSelection.vue";

const presentFormId: Ref<string | null> = ref(null);
const channel = mwiWebsocket.channel('contribute');

const formSelected = (selected: string) => {
    presentFormId.value = selected;
}

</script>

<template>
    <FormEditorFormSelection :start-expanded="presentFormId == null" @update="formSelected" ></FormEditorFormSelection>
    <div>Present form: {{presentFormId}}</div>
</template>

<style scoped>

</style>
