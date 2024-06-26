<script setup lang="ts">

import {ref, Ref} from "vue";
import DataTable from 'primevue/datatable';
import Column from "primevue/column";
import ProgressBar from "primevue/progressbar";
import {FilterMatchMode, FilterService} from "primevue/api";
import {timestampToString} from "../formatting";

type FormListing = {
    name: string,
    owner?: number,
    approved: boolean,
    review: boolean,
    revise: boolean,
    lastEdit: number // Timestamp
}

const props = defineProps<{
    startExpanded: boolean,
}>();

const emit = defineEmits(['update', 'new'])

const channel = mwiWebsocket.channel('contribute');

const expanded: Ref<boolean> = ref(props.startExpanded);
const formListLoaded: Ref<boolean> = ref(false);
const formListLoadTotal: Ref<number> = ref(1); // May be set to 0 if the viewer can't see any forms.
const formListLoadLeft: Ref<number> = ref(1);
const formList: Ref<FormListing[]> = ref([]);
const selected: Ref<FormListing | undefined> = ref();

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

const rowSelected = () => {
    emit('update', selected.value?.name);
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

        <ProgressBar v-if="formListLoadLeft" :indeterminate="!formListLoadTotal"
                     :value="(formListLoadTotal - formListLoadLeft) * 100 / formListLoadTotal"
        >
            {{ Math.floor((formListLoadTotal - formListLoadLeft) * 100 / formListLoadTotal) }}%
        </ProgressBar>
        <div v-else>
            <DataTable :value="formList" dataKey="name" size="small" stripedRows scrollable
                       scrollHeight="flex" @row-select="rowSelected" selectionMode="single"
                       v-model:selection="selected" v-model:filters="filters" filterDisplay="row"
            >
                <template #empty>There are no forms that you can view. Perhaps you should create a new one?</template>
                <Column header="Name" field="name" :sortable="true">
                    <template #filter="{ filterModel, filterCallback }">
                        <input v-model="filterModel.value" type="text" @input="filterCallback()"
                               class="p-column-filter" placeholder="Search by name"
                        />
                    </template>
                </Column>
                <Column header="Owner" field="owner" :sortable="true"></Column>
                <Column header="Last Edit" field="lastEdit" :sortable="true">
                    <template #body="{ data }">
                        {{ timestampToString((data as FormListing).lastEdit) }}
                    </template>
                </Column>
                <Column field="approved" :sortable="true">
                    <template #header>
                        <div class="flex-grow-1 text-center">Approved?</div>
                    </template>
                    <template #body="{ data }">
                        <i class="fa-solid fa-check w-100 text-center"
                           v-if="(data as FormListing).approved"
                        ></i>
                    </template>
                </Column>
                <Column header="Status" :sortable="true">
                    <template #body="{ data }">{{ statusForFormListing(data) }}</template>
                </Column>
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
