<script setup lang="ts">

import {ref, Ref} from "vue";
import Spinner from "./Spinner.vue";

type DedicationListing = {
    name: string,
    cost: number,
    description: string,
    class?: string,
    item?: string,
    noWeb?: boolean,
    home?: string,
    powers: string[],
    forms: string[]
}

const channel = mwiWebsocket.channel('character');
const dedications: Ref<DedicationListing[]> = ref([]);

const dedicationsToLoad: Ref<number | null> = ref(null);
const dedicationsToLoadRemaining: Ref<number> = ref(-1);

channel.on('dedicationList', (data: number) => {
    dedications.value = [];
    dedicationsToLoadRemaining.value = data;
    dedicationsToLoad.value = data;
});


channel.on('dedicationListing', (data: DedicationListing) => {
    // Description processing - replace \n with actual newline character
    data.description = data.description.replace(/\\n/g, '\n');
    dedications.value.push(data);
    dedicationsToLoadRemaining.value--;
});

channel.send('getDedicationList');

</script>

<template>
    <spinner v-if="dedicationsToLoadRemaining"></spinner>
    <div v-else>
        <template v-for="dedication in dedications">
            <div>{{ dedication.name }}</div>
        </template>
    </div>
</template>

<style scoped>

</style>
