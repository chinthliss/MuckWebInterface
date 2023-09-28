<script setup lang="ts">

import {ref, Ref} from "vue";
import Spinner from "./Spinner.vue";
import {characterName} from "../siteutils";

type Form = {
    name: string
}

const forms: Ref<Form[]> = ref([]);
const channel = mwiWebsocket.channel('forms');
const loadingData = ref(true);
const targetsName = ref(characterName());
const targetsForms: Ref<string[]> = ref([]);
const error: Ref<string | null> = ref(null);

const filterByTarget = ref(targetsName !== null);

channel.on('formDatabase', (data: Form[]) => {
    forms.value = data;
    loadingData.value = false;
});

type FormMasteryResponse = {
    who: string,
    forms?: string[],
    error?: string
}

channel.on('formMastery', (data: FormMasteryResponse) => {
    targetsName.value = data.who;
    error.value = data.error || null;
    targetsForms.value = data.forms || [];
});

const getFormMasteryOf = (who: string | null): void  => {
    if (!who) return;
    channel.send('getFormMasteryOf', targetsName.value);
}

// Send requests for data
channel.send('getFormCatalogue');
getFormMasteryOf(targetsName.value);

</script>

<template>
    <div class="container">

        <h1>Form Browser</h1>

        <p>We should have some introductory text here.</p>

        <spinner v-if="loadingData"></spinner>
        <template v-else>
            <div>Controls</div>

            <hr>

            <div>Loaded!</div>
        </template>

    </div>
</template>

<style scoped>

</style>
