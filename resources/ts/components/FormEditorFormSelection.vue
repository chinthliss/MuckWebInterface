<script setup lang="ts">

import {computed, ref, Ref} from "vue";
import Progress from "./Progress.vue";
import DataTable, {DataTableRowClickEvent} from 'primevue/datatable';
import Column from "primevue/column";
import {FilterMatchMode, FilterService} from "@primevue/core/api";
import {timestampToString} from "../formatting";

type FormListing = {
    name: string,
    account?: number, // Only transmitted if we're staff
    credit?: string,
    approved: number,
    deleted?: number, // Timestamp
    template?: number,
    published: number,
    review: number,
    revise: number,
    lastEdit: number, // Timestamp
    status?: string // Not passed in, we'll calculate ourselves
}

const props = defineProps<{
    startExpanded: boolean
}>();

const emit = defineEmits(['update', 'new'])

const channel = mwiWebsocket.channel('contribute');

const expanded: Ref<boolean> = ref(props.startExpanded);
const initialLoadDone: Ref<boolean> = ref(false); // Since it's passive and might not load at all
const formsToLoad: Ref<number | null> = ref(null); // May be set to 0 if the user can't see any forms
const formsToLoadRemaining: Ref<number> = ref(0);
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
            return form.review == 1;
        case 'revise':
            return form.revise == 1;
        default:
            return true;
    }
});

const loading = computed(() : boolean => {
    return (!formsToLoad.value || formsToLoadRemaining.value > 0);
});

// Returns a 0 to 100 value, not a ratio.
const loadingPercentage = computed(() => {
    if (!formsToLoad.value) return 0;
    return (formsToLoad.value - formsToLoadRemaining.value) * 100 / formsToLoad.value;
});

const getFormList = () => {
    initialLoadDone.value = true;
    channel.send('getFormList');
}

const statusForFormListing = (form: FormListing) => {
    if (form.deleted) return 'Deleted';
    if (form.revise) return 'Revision Needed';
    if (form.review) return 'Awaiting Review';
    return form.approved ? 'Approved' : 'Under Construction';
}

const toggleExpanded = () => {
    expanded.value = !expanded.value;
    if (!initialLoadDone.value) getFormList();
}

const refreshForms = () => {
    if (formsToLoadRemaining.value > 0) return;
    getFormList();
}

// Allow external refresh
const refresh = () => {
    refreshForms();
}

// Allow external reveal
const expand = () => {
    expanded.value = true;
}
defineExpose({refresh, expand});

const rowClicked = (e: DataTableRowClickEvent) => {
    const form = e.data;
    emit('update', form.name);
    expanded.value = false;
}

const newForm = () => {
    emit('new');
}

const rowStyle = (_data: any) => {
    return { cursor: 'pointer' };
}

channel.on('formList', (data: number) => {
    formsToLoad.value = data;
    formsToLoadRemaining.value = data;
    formList.value = [];
});

channel.on('formListing', (data: FormListing) => {
    formsToLoadRemaining.value--;
    formList.value.push(data);
    if (data.account) seeingAccounts.value = true;
    data.status = statusForFormListing(data);
});

if (props.startExpanded) getFormList()
</script>

<template>
    <template v-if="expanded">
        <div class="row my-4">
            <div class="col-12 col-xl-6">
                <h4>Form Selection</h4>
            </div>
            <div class="col-12 col-xl-6">
                <div class="w-100 d-flex align-items-center justify-content-center">
                    <div class="me-2 text-primary">Filter:</div>
                    <div class="flex-grow-1  btn-group" role="group" aria-label="Filter mode">
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

        <Progress v-if="loading" id="form-selection-progress-bar"
                  :percentage="loadingPercentage"
                  alt="Form list loading progress"
        ></Progress>
        <div v-else>
            <DataTable :value="formList" dataKey="name" size="small" stripedRows scrollable
                       scrollHeight="600px" @row-click="rowClicked" :rowStyle="rowStyle"
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
