<script setup lang="ts">

import {ref, Ref} from "vue";
import Spinner from "./Spinner.vue";
import {ansiToHtml} from "../formatting";
import {lex} from "../siteutils";

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

const purchaseDedication = (dedication: DedicationListing) => {
    console.log("TODO: Purchase dedication - ", dedication);
}

const switchToDedication = (dedication: DedicationListing) => {
    console.log("TODO: switch to dedication - ", dedication);
}

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
                        <button class="btn btn-primary"
                                @click="switchToDedication(dedication)">
                            <i class="fas fa-person-booth btn-icon-left"></i>Switch to {{ dedication.name }}
                        </button>

                        <span class="float-end">
                            <span v-if="dedicationsKnown && dedicationsKnown.includes(dedication.name)">
                                You own this dedication
                                <br/>Cost: {{ dedication.cost }} {{ lex('accountCurrency') }}
                            </span>
                            <span v-else-if="dedication.noWeb">
                                Not available from web
                                <br/>Cost: {{ dedication.cost }} {{ lex('accountCurrency') }}<br/>
                            </span>
                            <button v-else class="btn btn-primary btn-with-img-icon"
                                    @click="purchaseDedication(dedication)">
                                <span class="btn-icon-accountcurrency btn-icon-left"></span>
                                Purchase Dedication
                                <span class="btn-second-line">{{dedication.cost}} {{ lex('accountcurrency') }}</span>
                            </button>
                        </span>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Line 1 -->
                    <div>
                        <span v-html="ansiToHtml(dedication.description)"></span>
                    </div>
                    <!-- Line 2 -->
                    <div class="row mt-2">
                        <div class="col-12 col-lg-4">
                            <div class="text-primary fw-bold">Powers</div>
                            <div v-for="power in dedication.powers">{{ power }}</div>
                        </div>
                        <div class="col-12 col-lg-4">
                            <div class="text-primary fw-bold">Item</div>
                            {{ dedication.item }}
                        </div>
                        <div class="col-12 col-lg-4">
                            <div class="text-primary fw-bold">Home</div>
                            {{ dedication.home }}
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </div>
</template>

<style scoped>

</style>
