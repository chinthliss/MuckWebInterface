<script setup lang="ts">

import {ref, Ref} from "vue";
import Spinner from "./Spinner.vue";
import {ansiToHtml} from "../formatting";

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

const dedicationsKnown: Ref<string[] | null> = ref(null);
const hasTrainingRespecializer: Ref<boolean | null> = ref(null);

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

channel.on('trainingRespecializer', (data: boolean) => {
    hasTrainingRespecializer.value = data;
});

channel.on('knownDedications', (data: string[]) => {
    dedicationsKnown.value = data;
});

channel.send('bootDedications');

</script>

<template>

    <!-- Training Respecializer notice -->
    <template v-if="hasTrainingRespecializer !== null">
        <div v-if="hasTrainingRespecializer" class="p-2 mb-2 bg-primary text-dark rounded">
            You have the Training Respecializer, so switching your dedication will only cost 1 hero point.
        </div>
        <div v-else class="p-2 mb-2 bg-warning text-dark rounded">
            You don't have the Training Respecializer, so switching your dedication from here costs 25 patrol points.
            With it, it only costs 1 hero point.
        </div>
    </template>

    <!-- List of dedications -->
    <spinner v-if="dedicationsToLoadRemaining"></spinner>
    <div v-else>
        <template v-for="dedication in dedications">
            <div class="card mb-2">
                <div class="card-header">
                    <div class="card-title">
                        {{ dedication.name }}
                        <button class="btn btn-primary float-end">BUY</button>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Line 1 -->
                    <div>
                        <span v-html="ansiToHtml(dedication.description)"></span>
                        <span v-if="dedicationsKnown && dedicationsKnown.includes(dedication.name)"> - Known!</span>
                    </div>
                    <!-- Line 2 -->
                    <div class="row">
                        <div class="col-4">Powers</div>
                        <div class="col-4">Item</div>
                        <div class="col-4">Home</div>
                    </div>
                </div>
            </div>
        </template>
    </div>
</template>

<style scoped>

</style>
