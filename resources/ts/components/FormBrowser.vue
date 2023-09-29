<script setup lang="ts">

import {ref, Ref, computed} from "vue";
import Spinner from "./Spinner.vue";
import ModalConfirmation from "./ModalConfirmation.vue";

const props = defineProps<{
    startingPlayerName?: string
}>();

type Form = {
    name: string,
    staffonly?: number,
    hidden?: number,
    nonmasterable?: number,
    powerset?: string,
    placement?: string,
    private?: number
}

const formDatabase: Ref<Form[]> = ref([]);
const channel = mwiWebsocket.channel('forms');
const loadingData: Ref<boolean> = ref(true);
const changeTargetModal: Ref<Element | null> = ref(null);
const intendedTarget: Ref<string> = ref('');

const targetsName: Ref<string | null> = ref('');
const targetsForms: Ref<{ [form: string]: number }> = ref({});
const filterMode: Ref<string> = ref('mastered');
const error: Ref<string | null> = ref(null);

channel.on('formDatabase', (data: Form[]) => {
    formDatabase.value = data;
    loadingData.value = false;
});

type FormMasteryResponse = {
    who: string,
    forms?: { [form: string]: number },
    error?: string
}

channel.on('mastery', (data: FormMasteryResponse) => {
    targetsName.value = data.who;
    error.value = data.error || null;
    targetsForms.value = data.forms || {};
});

const changeFormMasteryTarget = (): void => {
    if (!intendedTarget.value) return;
    channel.send('getFormMasteryOf', intendedTarget.value);
}

const clearTarget = () => {
    targetsName.value = null;
    targetsForms.value = {};
    error.value = null;

}

const unknownForms = computed((): string => {
    if (! targetsName.value) return '';
    const result = [];
    const formList = formDatabase.value.map((form) => {
        return form.name;
    });
    for (const form in targetsForms.value) {
        if (formList.indexOf(form) === -1) result.push(form)
    }
    return result.join(', ');
});

const shouldWeShow = (form: Form) => {
    if (filterMode.value === 'mastered' && !targetsForms.value[form.name]) return false;
    if (filterMode.value === 'unmastered' && targetsForms.value[form.name] > 0) return false;
    return true;
}

// Send requests for data
channel.send('getFormCatalogue');
if (props.startingPlayerName) intendedTarget.value = props.startingPlayerName;
changeFormMasteryTarget();

</script>

<template>
    <div class="container">

        <h1>Form Browser</h1>

        <p>This is presently a root page and should have some introductory text here for new users that might click on
            it. Something about forms being a key part of the game?</p>

        <spinner v-if="loadingData"></spinner>
        <div v-else>

            <!-- Target row -->
            <div class="d-lg-flex align-items-center">
                <div class="flex-grow-1">
                    <template v-if="targetsName">
                        <span class="text-primary">Present Target:</span> {{ targetsName }}
                    </template>
                    <template v-else>
                        No Target Selected
                    </template>

                </div>

                <div>
                    <button class="btn btn-primary me-lg-2" @click="changeTargetModal.show()">
                        <i class="fas fa-search btn-icon-left"></i>Select Target
                    </button>

                    <button class="btn btn-primary me-lg-2" @click="clearTarget" :disabled="!targetsName" >
                        <i class="fas fa-close btn-icon-left"></i>Clear Target
                    </button>
                </div>

                <div class="me-1">Target Mode: </div>
                <div class="btn-group" role="group" aria-label="Filter mode">
                    <input type="radio" class="btn-check" name="filter" id="filter_mastered" autocomplete="off"
                           v-model="filterMode" value="mastered" :disabled="!targetsName"
                    >
                    <label class="btn btn-secondary" for="filter_mastered">Mastered Forms</label>

                    <input type="radio" class="btn-check" name="filter" id="filter_unmastered" autocomplete="off"
                           v-model="filterMode" value="unmastered" :disabled="!targetsName"
                    >
                    <label class="btn btn-secondary" for="filter_unmastered">Un-mastered Forms</label>

                    <input type="radio" class="btn-check" name="filter" id="filter_none" autocomplete="off"
                           v-model="filterMode" value="none" :disabled="!targetsName"
                    >
                    <label class="btn btn-secondary" for="filter_none">All Forms</label>
                </div>
            </div>

            <hr>

            <div v-if="error" class="alert alert-danger">
                The game refused the request to view the present target with the following reason:
                {{ error }}
            </div>
            <div v-else>
                <template v-for="form in formDatabase">
                    <div v-if="shouldWeShow(form)">
                        {{ form.name }}
                    </div>
                </template>
                <div v-if="unknownForms" class="mt-4 alert alert-warning">
                    <div>Form mastery was found for the following forms but they don't seem to be in the database:</div>
                    <div>{{ unknownForms }}</div>
                </div>
            </div>

        </div>
    </div>
    <modal-confirmation ref="changeTargetModal" title="Change Target" yes-label="Search" no-label="Cancel" @yes="changeFormMasteryTarget">
        <div class="mb-2">
            <label for="changeTargetInput" class="form-label">Enter the name of the player you want to view:</label>
            <input type="text" class="form-control" id="changeTargetInput" v-model="intendedTarget">
        </div>
    </modal-confirmation>

</template>

<style scoped>

</style>
