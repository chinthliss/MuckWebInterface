<script setup lang="ts">

import {ref, Ref} from "vue";
import Spinner from "./Spinner.vue";
import {ansiToHtml} from "../formatting";
import {lex} from "../siteutils";
import ModalMessage from "./ModalMessage.vue";
import ModalConfirmation from "./ModalConfirmation.vue";

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
const freeSwitching: Ref<boolean | null> = ref(null);
const purchaseDedication: Ref<string> = ref('');
const purchaseCost: Ref<string> = ref('');
const modal: Ref<typeof ModalMessage | null> = ref(null);
const confirmPurchaseModal: Ref<typeof ModalConfirmation | null> = ref(null);
const modalPrimaryText: Ref<string> = ref('');
const modalSecondaryText: Ref<string[]> = ref([]);
const startBuyDedication = (dedication: DedicationListing) => {
    purchaseDedication.value = dedication.name;
    purchaseCost.value = dedication.cost + ' ' + lex('accountCurrency');
    if (confirmPurchaseModal.value) confirmPurchaseModal.value.show();
}

const buyDedication = () => {
    channel.send('buyDedication', purchaseDedication.value);
}

const switchToDedication = (dedication: DedicationListing, currency: 'patrol' | 'hero') => {
    channel.send('setDedication', {dedication: dedication.name, currency: currency});
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

channel.on('freeSwitching', (data: boolean) => {
    freeSwitching.value = data;
});


channel.on('knownDedications', (data: string[]) => {
    dedicationsKnown.value = data;
});

type BuyOrSetResponse = {
    dedication: string,
    messages: string[],
    error?: string
}
channel.on('buyDedication', (data: BuyOrSetResponse) => {
    if (data.error) {
        modalPrimaryText.value = data.error;
    } else {
        if (dedicationsKnown.value) {
            dedicationsKnown.value.push(data.dedication);
        }
        modalPrimaryText.value = "Dedication Purchased: " + data.dedication;
        modalSecondaryText.value = data.messages;
    }
    if (modal.value) modal.value.show();
});

channel.on('setDedication', (data: BuyOrSetResponse) => {
    if (data.error) {
        modalPrimaryText.value = data.error;
    } else {
        modalPrimaryText.value = "Dedication Changed to: " + data.dedication;
        modalSecondaryText.value = data.messages;
    }
    if (modal.value) modal.value.show();
});


channel.send('bootDedications');

</script>

<template>

    <!-- Training Respecializer notice -->
    <template v-if="hasTrainingRespecializer !== null">
        <div v-if="hasTrainingRespecializer" class="p-2 mb-2 bg-primary text-dark rounded">
            You have the Training Respecializer,
            so you can switch dedications with 5 patrol points or 1 hero point.
        </div>
        <div v-else class="p-2 mb-2 bg-warning text-dark rounded">
            You don't have the Training Respecializer, so switching your dedication from here costs 15 patrol points.
            With it, it only costs 5 patrol points or 1 hero point.
        </div>
    </template>

    <!-- Free purchases notice -->
    <div v-if="freeSwitching" class="p-2 mb-2 bg-primary text-dark rounded">
        Because of your present subscription level, you can switch to any dedication without purchasing it.
        The purchase option is still shown in case you wish to use it.
    </div>

    <!-- List of dedications -->
    <spinner v-if="dedicationsToLoadRemaining"></spinner>
    <div v-else>
        <template v-for="dedication in dedications">
            <div class="card mb-4">
                <div class="card-header">
                    <div class="card-title row">
                        <!-- Name -->
                        <div class="col-12 col-lg-6">
                            <h4>{{ dedication.name }}</h4>
                        </div>
                        <!-- Purchase / status -->
                        <div class="col-12 col-lg-6 text-center">
                            <span v-if="dedicationsKnown && dedicationsKnown.includes(dedication.name)">
                                You own this dedication
                                <br/>Cost: {{ dedication.cost }} {{ lex('accountCurrency') }}
                            </span>
                            <span v-else-if="dedication.noWeb">
                                Not available from web
                                <br/>Cost: {{ dedication.cost }} {{ lex('accountCurrency') }}
                            </span>
                            <button v-else class="btn btn-primary btn-with-img-icon"
                                    @click="startBuyDedication(dedication)"
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
                                    <a :href="wikiRoot + power.replace(' ', '_')">{{ power }}</a>
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
                    <!-- Line 3 - optional, if owned or can free switch-->
                    <div class="row"
                         v-if="(dedicationsKnown && dedicationsKnown.includes(dedication.name)) || freeSwitching"
                    >
                        <!-- Switch to -->
                        <div class="col-12 text-center">
                            <button class="btn btn-primary mt-2"
                                    @click="switchToDedication(dedication, 'patrol')"
                            >
                                <i class="fas fa-person-booth btn-icon-left"></i>
                                Switch to {{ dedication.name }} with patrol points
                            </button>
                            <button class="btn btn-primary mt-2 ms-2"
                                    v-if="hasTrainingRespecializer"
                                    @click="switchToDedication(dedication, 'hero')"
                            >
                                <i class="fas fa-person-booth btn-icon-left"></i>
                                Switch to {{ dedication.name }} with hero points
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </div>

    <ModalMessage ref="modal">
        {{ modalPrimaryText }}
        <div v-for="line in modalSecondaryText">{{ line }}</div>
    </ModalMessage>
    <ModalConfirmation ref="confirmPurchaseModal" @yes="buyDedication"
                       title="Confirm Purchase" yes-label="Buy Dedication" no-label="Cancel"
    >
        <p>Are you sure you wish to purchase the dedication '{{ purchaseDedication }}' for {{ purchaseCost }}?</p>
    </ModalConfirmation>
</template>

<style scoped>

</style>
