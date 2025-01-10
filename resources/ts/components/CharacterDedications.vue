<script setup lang="ts">

import {ref, Ref} from "vue";
import Spinner from "./Spinner.vue";
import {ansiToHtml} from "../formatting";
import {lex} from "../siteutils";
import ModalMessage from "./ModalMessage.vue";

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

//TODO: Replace wiki references at some point
const wikiRoot = 'https://wiki.flexiblesurvival.com/w/';

const channel = mwiWebsocket.channel('character');
const dedications: Ref<DedicationListing[]> = ref([]);

const dedicationsToLoad: Ref<number | null> = ref(null);
const dedicationsToLoadRemaining: Ref<number> = ref(-1);

const dedicationsKnown: Ref<string[] | null> = ref(null);
const hasTrainingRespecializer: Ref<boolean | null> = ref(null);
const modal: Ref<typeof ModalMessage | null> = ref(null);
const modalText: Ref<string> = ref('');

const purchaseDedication = (dedication: DedicationListing) => {
    console.log("TODO: Purchase dedication - ", dedication);
    modalText.value = 'Not implemented yet.';
    if (modal.value) modal.value.show();
}

const switchToDedication = (dedication: DedicationListing) => {
    console.log("TODO: switch to dedication - ", dedication);
    modalText.value = 'Not implemented yet.';
    if (modal.value) modal.value.show();
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
            <div class="card mb-4">
                <div class="card-header">
                    <div class="card-title row">
                        <!-- Name -->
                        <div class="col-12 col-lg-4">
                            <h4>{{ dedication.name }}</h4>
                        </div>
                        <!-- Switch to -->
                        <div class="col-12 col-lg-4 text-center">
                            <button class="btn btn-primary"
                                    v-if="dedicationsKnown && dedicationsKnown.includes(dedication.name)"
                                    @click="switchToDedication(dedication)"
                            >
                                <i class="fas fa-person-booth btn-icon-left"></i>Switch to {{ dedication.name }}
                            </button>
                        </div>
                        <!-- Purchase / status -->
                        <div class="col-12 col-lg-4 text-center">
                            <span v-if="dedicationsKnown && dedicationsKnown.includes(dedication.name)">
                                You own this dedication
                                <br/>Cost: {{ dedication.cost }} {{ lex('accountCurrency') }}
                            </span>
                            <span v-else-if="dedication.noWeb">
                                Not available from web
                                <br/>Cost: {{ dedication.cost }} {{ lex('accountCurrency') }}<br/>
                            </span>
                            <button v-else class="btn btn-primary btn-with-img-icon"
                                    @click="purchaseDedication(dedication)"
                            >
                                <span class="btn-icon-accountcurrency btn-icon-left"></span>
                                Purchase Dedication
                                <span class="btn-second-line">{{ dedication.cost }} {{ lex('accountcurrency') }}</span>
                            </button>
                        </div>


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
                            <template v-if="dedication.powers.length">
                                <div v-for="power in dedication.powers">
                                    <a :href="wikiRoot + power">{{ power }}</a>
                                </div>
                            </template>
                            <div v-else class="text-muted">No associated powers.</div>
                        </div>
                        <div class="col-12 col-lg-4">
                            <div class="text-primary fw-bold">Associated Item</div>
                            <div v-if="dedication.item">{{ dedication.item }}</div>
                            <div v-else class="text-muted">No associated item.</div>
                        </div>
                        <div class="col-12 col-lg-4">
                            <div class="text-primary fw-bold">Associated Location</div>
                            <div v-if="dedication.home">{{ dedication.home }}</div>
                            <div v-else class="text-muted">No associated location.</div>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </div>

    <ModalMessage ref="modal">{{ modalText }}</ModalMessage>
</template>

<style scoped>

</style>
