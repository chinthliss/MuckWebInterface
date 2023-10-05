<script setup lang="ts">

import {ref, Ref, computed} from "vue";
import Spinner from "./Spinner.vue";
import ModalConfirmation from "./ModalConfirmation.vue";
import DataTable from "datatables.net-vue3";
import {capital} from "../formatting";

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

const formTableConfiguration = {
    columns: [
        {data: 'name'},
        {data: 'gender', render: capital},
        {data: 'size', defaultContent: ''},
        {data: 'cockCount', defaultContent: ''},
        {data: 'cockSize', defaultContent: ''},
        {data: 'ballCount', defaultContent: ''},
        {data: 'ballSize', defaultContent: ''},
        {data: 'cuntCount', defaultContent: ''},
        {data: 'cuntSize', defaultContent: ''},
        {data: 'breastCount', defaultContent: ''},
        {data: 'breastSize', defaultContent: ''},
        {data: 'tags', defaultContent: ''},
        {data: 'flags', defaultContent: '', render: JSON.stringify},
        {data: 'sayVerb', defaultContent: ''},
        {data: 'noMastering', defaultContent: ''},
        {data: 'noFunnel', defaultContent: ''},
        {data: 'noReward', defaultContent: ''},
        {data: 'noZap', defaultContent: ''},
        {data: 'noNative', defaultContent: ''},
        {data: 'noExtract', defaultContent: ''},
        {data: 'bypassImmune', defaultContent: ''},
        {data: 'placement', defaultContent: '', render: JSON.stringify},
        {data: 'holiday', defaultContent: ''},
        {data: 'placementNote', defaultContent: ''},
        {data: 'powerNote', defaultContent: ''},
        {data: 'specialNote', defaultContent: ''},

    ],
    paging: false,
    info: false,
    searching: true,
    scrollX: true
};

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
                <DataTable class="table table-dark table-hover table-striped table-bordered"
                           :options="formTableConfiguration"
                           :data="filteredFormDatabase"
                >
                    <thead>
                    <tr>
                        <th scope="col">Name</th>
                        <th scope="col">Gender</th>
                        <th scope="col">Size</th>
                        <th scope="col">Cock Count</th>
                        <th scope="col">Cock Size</th>
                        <th scope="col">Ball Count</th>
                        <th scope="col">Ball Size</th>
                        <th scope="col">Cunt Count</th>
                        <th scope="col">Cunt Size</th>
                        <th scope="col">Breast Count</th>
                        <th scope="col">Breast Size</th>
                        <th scope="col">Tags</th>
                        <th scope="col">Flags</th>
                        <th scope="col">Say Verb</th>
                        <th scope="col">No Mastering</th>
                        <th scope="col">No Funnel</th>
                        <th scope="col">No Reward</th>
                        <th scope="col">No Zap</th>
                        <th scope="col">No Native</th>
                        <th scope="col">No Extract</th>
                        <th scope="col">Bypass Immune</th>
                        <th scope="col">Placement</th>
                        <th scope="col">Holiday</th>
                        <th scope="col">Placement Note</th>
                        <th scope="col">Powers Note</th>
                        <th scope="col">Special Note</th>

                    </tr>
                    </thead>
                </Datatable>
            </div>

            <div v-if="unknownForms" class="mt-4 alert alert-warning">
                <div>Form mastery was found for the following forms but no information on them was available:</div>
                <div>{{ unknownForms }}</div>
                <div>(This might just mean the form hasn't been released yet.)</div>
            </div>

        </div>
    </div>
    <modal-confirmation ref="changeTargetModal" title="Change Target" yes-label="Search" no-label="Cancel"
                        @yes="changeTarget">
        <div class="mb-2">
            <label for="changeTargetInput" class="form-label">Enter the name of the player you want to view:</label>
            <input type="text" class="form-control" id="changeTargetInput" v-model="changeTargetName">
        </div>
    </modal-confirmation>

</template>

<style scoped>

</style>
