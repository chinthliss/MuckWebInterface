<script setup lang="ts">

import {ref, Ref} from "vue";
import Progress from "./Progress.vue";
import DataTable, {DataTableRowClickEvent} from 'primevue/datatable';
import Column from "primevue/column";
import {FilterMatchMode, FilterService} from "@primevue/core/api";
import {timestampToString} from "../formatting";

type FormListing = {
    name: string,
    account?: number, // Only transmitted if we're staff
    credit?: string,
    approved: boolean,
    published: boolean,
    review: boolean,
    revise: boolean,
    lastEdit: number, // Timestamp
    status?: string // Not passed in, we'll calculate ourselves
}

const props = defineProps<{
    startExpanded: boolean
}>();

const emit = defineEmits(['update', 'new'])

const channel = mwiWebsocket.channel('contribute');

const expanded: Ref<boolean> = ref(props.startExpanded);
const formListLoaded: Ref<boolean> = ref(false);
const formListLoadTotal: Ref<number> = ref(1); // May be set to 0 if the viewer can't see any forms.
const formListLoadLeft: Ref<number> = ref(1);
const formList: Ref<FormListing[]> = ref([]);
// We don't start showing accounts until we see we're being sent them (we don't get sent them if not staff)
const seeingAccounts: Ref<boolean> = ref(false);

const filters = ref({
    name: {value: null, matchMode: FilterMatchMode.CONTAINS},
    global: {value: 'all', matchMode: 'filteredFormList'}
});

FilterService.register('filteredFormList', (name: string, mode: string) => {
    const form: FormListing | undefined = formList.value.find((form) => form.name == name);
    if (!form) return false;
    switch (mode) {
        case 'review':
            return form.review;
        case 'revise':
            return form.revise;
        default:
            return true;
    }
});

const getFormList = () => {
    formListLoadTotal.value = 0;
    formListLoadLeft.value = 1;
    formListLoaded.value = true;
    channel.send('getFormList');
}

const statusForFormListing = (form: FormListing) => {
    console.log("Test for " + form.name);
    if (form.revise) return 'Revision Needed';
    if (form.review) return 'Awaiting Review';
    return form.approved ? 'Finished' : 'Under Construction';
}

const toggleExpanded = () => {
    expanded.value = !expanded.value;
    if (!formListLoaded.value) getFormList();
}

const refreshForms = () => {
    if (formListLoadLeft > 0) return;
    getFormList();
}

// Allow external refresh
const refresh = () => {
    refreshForms();
}
defineExpose({refresh});

const rowClicked = (e: DataTableRowClickEvent) => {
    const form = e.data;
    emit('update', form.name);
    expanded.value = false;
}

const newForm = () => {
    emit('new');
}

channel.on('formList', (data: number) => {
    formListLoadTotal.value = formListLoadLeft.value = data;
    formList.value = [];
});

channel.on('formListing', (data: FormListing) => {
    formListLoadLeft.value--;
    formList.value.push(data);
    if (data.account) seeingAccounts.value = true;
    data.status = statusForFormListing(data);
});

if (props.startExpanded) getFormList()
</script>

<template>
    <template v-if="expanded">
        <div class="row my-4">
            <div class="col-6">
                <h4>Form Selection</h4>
            </div>
            <div class="col-6">
                <div class="d-lg-flex align-items-center justify-content-center">
                    <div class="me-2 text-primary">Filter:</div>
                    <div class="me-4 btn-group" role="group" aria-label="Filter mode">
                        <input type="radio" class="btn-check" name="filter" id="filter_none" autocomplete="off"
                               v-model="filters.global.value" value="all"
                        >
                        <label class="btn btn-outline-secondary" for="filter_none">All Forms</label>

                        <input type="radio" class="btn-check" name="filter" id="filter_review" autocomplete="off"
                               v-model="filters.global.value" value="review"
                        >
                        <label class="btn btn-outline-secondary" for="filter_review">Waiting for Review</label>

                        <input type="radio" class="btn-check" name="filter" id="filter_revise" autocomplete="off"
                               v-model="filters.global.value" value="revise"
                        >
                        <label class="btn btn-outline-secondary" for="filter_revise">Revision Required </label>

                    </div>
                </div>
            </div>
        </div>

        <Progress v-if="formListLoadLeft" id="form-selection-progress-bar"
                  :percentage="(formListLoadTotal - formListLoadLeft) * 100 / formListLoadTotal"
                  alt="Form list loading progress"
        ></Progress>
        <div v-else>
            <DataTable :value="formList" dataKey="name" size="small" stripedRows scrollable
                       scrollHeight="600px" @row-click="rowClicked"
                       v-model:filters="filters" filterDisplay="row"
            >
                <template #empty>There are no forms that you can view. Perhaps you should create a new one?</template>
                <Column header="Name" field="name" :sortable="true">
                    <template #filter="{ filterModel, filterCallback }">
                        <input v-model="filterModel.value" type="text" @input="filterCallback()"
                               class="p-column-filter" placeholder="Search by name"
                        />
                    </template>
                </Column>
                <Column v-if="seeingAccounts" header="Account" field="account" :sortable="true"></Column>
                <Column header="Credit" field="credit" :sortable="true"></Column>
                <Column header="Last Edit" field="lastEdit" :sortable="true">
                    <template #body="{ data }">
                        {{ timestampToString((data as FormListing).lastEdit) }}
                    </template>
                </Column>
                <Column field="approved" header="Approved?" :sortable="true">
                    <template #body="{ data }">
                        <i class="fa-solid fa-check w-100 text-center"
                           v-if="(data as FormListing).approved"
                        ></i>
                    </template>
                </Column>
                <Column field="published" header="Published?" :sortable="true">
                    <template #body="{ data }">
                        <i class="fa-solid fa-check w-100 text-center"
                           v-if="(data as FormListing).published"
                        ></i>
                    </template>
                </Column>
                <Column field="status" header="Status" :sortable="true"></Column>
            </DataTable>
        </div>
    </template>
    <!-- Expand or shrink buttons -->
    <div class="mt-2 d-flex justify-content-end">
        <button v-if="expanded" class="btn btn-secondary me-2" @click="refreshForms">
            <i class="fas fa-refresh btn-icon-left"></i>Refresh Forms
        </button>

        <button class="btn btn-secondary me-2" @click="newForm">
            <i class="fas fa-file btn-icon-left"></i>New Form
        </button>

        <button class="btn btn-secondary" @click="toggleExpanded">
            <i v-if="expanded" class="fas fa-minus btn-icon-left"></i>
            <i v-else class="fas fa-plus btn-icon-left"></i>
            {{ expanded ? 'Hide' : 'Show' }} Form Selection
        </button>
    </div>

</template>

<style scoped>

</style>
