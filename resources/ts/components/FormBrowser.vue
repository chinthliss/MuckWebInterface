<script setup lang="ts">

import {ref, Ref, computed} from "vue";
import DataTable from 'primevue/datatable';
import Column from "primevue/column";
import {capital} from "../formatting";
import Spinner from "./Spinner.vue";
import ModalConfirmation from "./ModalConfirmation.vue";

const props = defineProps<{
    startingPlayerName?: string,
    staff?: boolean
}>();

type Form = {
    name: string,
    staffonly?: number,
    hidden?: number,
    placement?: string[],
    powersetNote?: string,
    placementNote?: string,
    specialNote?: string,
    private?: number,
    gender: string,
    size?: number,
    lstats?: string[], // List of parts with lstats
    tags: string[], // List of tags
    flags: string[] // List of flags
    noMastering?: number,
    noFunnel?: string,
    noReward?: string,
    noZap?: string,
    noNative?: string,
    noExtract?: string,
    bypassImmune?: string,
    sayVerb?: string,
    holiday?: string
}

const formDatabase: Ref<Form[]> = ref([]);
const channel = mwiWebsocket.channel('forms');
const formsToLoad: Ref<number> = ref(1); // Starting at 1 to cover initial loading
const changeTargetModal: Ref<Element | null> = ref(null);
const changeTargetIndex: Ref<number> = ref(0);
const changeTargetName: Ref<string> = ref('');

type Target = {
    name?: string,
    loading?: boolean,
    error?: string,
    forms?: { [form: string]: number }
}
const targets: Ref<[Target, Target, Target, Target]> = ref([{}, {}, {}, {}]);
const filterMode: Ref<string> = ref('mastered');

const filteredFormDatabase = computed(() => {
    const result = [];
    for (const form of formDatabase.value) {
        if (shouldWeShow(form)) result.push(form);
    }
    return result;
});

channel.on('formDatabase', (data: number) => {
    formDatabase.value = [];
    formsToLoad.value = data;
});

channel.on('formListing', (data: Form) => {
    formDatabase.value.push(data);
    formsToLoad.value--;
});

type FormMasteryResponse = {
    who: string,
    forms?: { [form: string]: number },
    error?: string
}

channel.on('mastery', (data: FormMasteryResponse) => {
    // User might have entered the same target in multiple rows, so just step through them all
    let updatedCount = 0;
    for (const target of targets.value) {
        if (target.name !== data.who) continue;
        target.name = data.who;
        target.error = data.error;
        target.forms = data.forms;
        target.loading = false;
        updatedCount++;
    }
    if (!updatedCount) {
        console.log("Unable to find a target to update for: ", data);
        return;
    }
});

const changeTarget = (): void => {
    if (!changeTargetName.value) return;
    let target = targets.value[changeTargetIndex.value];
    target.loading = true;
    target.name = changeTargetName.value;
    channel.send('getFormMasteryOf', target.name);
}

const launchChangeTarget = (index: number): void => {
    changeTargetIndex.value = index;
    changeTargetModal.value.show();
}

const clearTarget = (index: number) => {
    targets.value[index] = {};
}

const unknownForms = computed((): string => {
    const result = [];
    const formList = formDatabase.value.map((form) => {
        return form.name;
    });
    for (const target of targets.value) {
        for (const form in target.forms) {
            if (formList.indexOf(form) === -1 && result.indexOf(form) === -1) result.push(form)
        }
    }
    return result.join(', ');
});

const shouldWeShow = (form: Form): boolean => {
    // Filtering on targets, if selected
    if (!form.name) return false;
    let count = 0;
    for (const target of targets.value) {
        if (target.forms && target.forms[form.name]) count++;
    }
    if (filterMode.value === 'mastered' && !count) return false;
    if (filterMode.value === 'unmastered' && count) return false;

    if (!props.staff) {
        if (form.staffonly) return false;
        // Only show private forms that are present
        if (form.private && !count) return false;
    }
    return true;
}

// Send requests for data
channel.send('getFormDatabase');
if (props.startingPlayerName) {
    changeTargetIndex.value = 0;
    changeTargetName.value = props.startingPlayerName;
    changeTarget();
}

</script>

<template>
    <div class="container">

        <h1>Form Browser<span v-if="props.staff"> (Staff Mode)</span></h1>

        <p>This is presently a root page and should have some introductory text here for new users that might click on
            it. Something about forms being a key part of the game?</p>

        <spinner v-if="formsToLoad > 0"></spinner>
        <div v-else>

            <!-- Mode selector -->
            <div class="d-lg-flex align-items-center justify-content-center mb-2">
                <div class="me-1 text-primary">Mode:</div>
                <div class="btn-group" role="group" aria-label="Filter mode">
                    <input type="radio" class="btn-check" name="filter" id="filter_mastered" autocomplete="off"
                           v-model="filterMode" value="mastered"
                    >
                    <label class="btn btn-secondary" for="filter_mastered">Mastered Forms</label>

                    <input type="radio" class="btn-check" name="filter" id="filter_unmastered" autocomplete="off"
                           v-model="filterMode" value="unmastered"
                    >
                    <label class="btn btn-secondary" for="filter_unmastered">Un-mastered Forms</label>

                    <input type="radio" class="btn-check" name="filter" id="filter_none" autocomplete="off"
                           v-model="filterMode" value="none"
                    >
                    <label class="btn btn-secondary" for="filter_none">All Forms</label>

                </div>
            </div>
            <!-- Target rows -->
            <div v-for="(target, index) in targets" class="d-lg-flex align-items-center mb-2">
                <div class="flex-grow-1">
                    <span class="text-primary">Target {{ index + 1 }}: </span>
                    <template v-if="target.name">
                        {{ target.name }}
                    </template>
                    <template v-else>
                        No Target Selected
                    </template>

                </div>

                <div>
                    <div v-if="target.loading" class="me-2">
                        <spinner></spinner>
                    </div>
                    <div v-if="target.error" class="me-2 text-danger">
                        Can't display: {{ target.error }}
                    </div>
                </div>

                <div>
                    <button class="btn btn-primary me-lg-2" @click="launchChangeTarget(index)">
                        <i class="fas fa-search btn-icon-left"></i>Select Target
                    </button>

                    <button class="btn btn-primary me-lg-2" @click="clearTarget(index)" :disabled="!target.name">
                        <i class="fas fa-close btn-icon-left"></i>Clear Target
                    </button>
                </div>

            </div>

            <hr>

            <div>
                <DataTable :value="filteredFormDatabase" dataKey="name" stripedRows scrollable scrollHeight="600px">
                    <Column header="Name" field="name" class="fw-bold" frozen></Column>
                    <Column header="Gender" field="gender">
                        <template #body="{ data }">
                            {{ capital((data as Form).gender) }}
                        </template>
                    </Column>
                    <Column header="Size" field="size"></Column>
                    <Column header="Cock Count" field="cockCount"></Column>
                    <Column header="Cock Size" field="cockSize"></Column>
                    <Column header="Ball Count" field="ballCount"></Column>
                    <Column header="Ball Size" field="ballSize"></Column>
                    <Column header="Cunt Count" field="cuntCount"></Column>
                    <Column header="Cunt Size" field="cuntSize"></Column>
                    <Column header="Breast Count" field="breastCount"></Column>
                    <Column header="Breast Size" field="breastSize"></Column>
                    <Column header="Tags" field="tags"></Column>
                    <Column header="Flags" field="flags"></Column>
                    <Column header="Say Verb" field="sayVerb"></Column>
                    <Column header="Powers" field="powers"></Column>
                    <Column header="No Mastering" field="noMastering"></Column>
                    <Column header="No Funnel" field="noFunnel"></Column>
                    <Column header="No Reward" field="noReward"></Column>
                    <Column header="No Zap" field="noZap"></Column>
                    <Column header="No Native" field="noNative"></Column>
                    <Column header="No Extract" field="noExtract"></Column>
                    <Column header="Bypass Immune" field="bypassImmune"></Column>
                    <Column header="Placement" field="placement"></Column>
                    <Column header="Holiday" field="holiday"></Column>
                    <Column header="Placement Note" field="placementNote"></Column>
                    <Column header="Power Note" field="powerNote"></Column>
                    <Column header="Special Note" field="specialNote"></Column>
                </Datatable>
            </div>

            <div v-if="unknownForms" class="mt-4 alert alert-warning">
                <div>Form mastery was found for the following forms but no information on them was available:</div>
                <div>{{ unknownForms }}</div>
                <div>(This might just mean the form hasn't been released yet.)</div>
            </div>

        </div>
    </div>
    <modal-confirmation ref="changeTargetModal" title="Change Target"
                        yes-label="Search" no-label="Cancel" @yes="changeTarget"
    >
        <div class="mb-2">
            <label for="changeTargetInput" class="form-label">Enter the name of the player you want to view:</label>
            <input type="text" class="form-control" id="changeTargetInput" v-model="changeTargetName">
        </div>
    </modal-confirmation>

</template>

<style scoped>

</style>
