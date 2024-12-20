<script setup lang="ts">

import {computed, ref, Ref} from "vue";
import Progress from "./Progress.vue";
import {timestampToString} from "../formatting";

import DataTable from 'datatables.net-vue3';
import DataTablesLib, {Api, Config as DataTableOptions} from 'datatables.net-bs5';
import {DataTablesNamedSlotProps} from "../defs";

DataTable.use(DataTablesLib);


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

let dtApi: Api | null = null;

const expanded: Ref<boolean> = ref(props.startExpanded);
const initialLoadDone: Ref<boolean> = ref(false); // Since it's passive and might not load at all
const formsToLoad: Ref<number | null> = ref(null); // May be set to 0 if the user can't see any forms
const formsToLoadRemaining: Ref<number> = ref(0);
const formList: Ref<FormListing[]> = ref([]);
const statusFilter: Ref<string> = ref('');
const nameFilter: Ref<string> = ref('');
// We don't start showing accounts until we see we're being sent them (we don't get sent them if not staff)
const seeingAccounts: Ref<boolean> = ref(false);

const tableOptions: DataTableOptions = {
    info: false,
    paging: false,
    layout: {
        topEnd: null
    },
    language: {
        emptyTable: "No forms to view. Maybe you could create your own?"
    },
    scrollY: '400px',
    columns: [
        {data: 'name', name: 'name'},
        {data: 'account', visible: false, name: 'account'},
        {data: 'credit'},
        {data: 'lastEdit', render: timestampToString},
        {data: 'approved', name: 'approved'},
        {data: 'published', name: 'published'},
        {data: 'status', name: 'status'}
    ],
    createdRow: (row: Node, data: any) => {
        row.addEventListener('click', () => rowClicked(data));
    },
    initComplete: () => {
        dtApi = new DataTablesLib.Api('table');
        if (seeingAccounts.value) showAccountColumn();
    }
};

const statusFilterChanged = () => {
    if (dtApi) {
        let statusColumn = dtApi.columns('status:name');
        console.log(`Setting status filter to ${statusFilter.value} on:`, statusColumn);
        statusColumn.search(statusFilter.value, {exact: true}).draw();
    }
}

const nameFilterChanged = () => {
    if (dtApi) {
        let nameColumn = dtApi.columns('name:name');
        nameColumn.search(nameFilter.value).draw();
    }
}

const loading = computed((): boolean => {
    return (!formsToLoad.value || formsToLoadRemaining.value > 0);
});

// Returns a 0 to 100 value, not a ratio.
const loadingPercentage = computed(() => {
    if (!formsToLoad.value) return 0;
    return (formsToLoad.value - formsToLoadRemaining.value) * 100 / formsToLoad.value;
});

const showAccountColumn = () => {
    if (dtApi) {
        dtApi.columns('account:name').visible(true);
    }

}

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

const newForm = () => {
    emit('new');
}

const rowClicked = (form: FormListing) => {
    emit('update', form.name);
    expanded.value = false;
}

channel.on('formList', (data: number) => {
    formsToLoad.value = data;
    formsToLoadRemaining.value = data;
    formList.value = [];
});

channel.on('formListing', (data: FormListing) => {
    formsToLoadRemaining.value--;
    formList.value.push(data);
    if (data.account && !seeingAccounts.value) {
        seeingAccounts.value = true;
        showAccountColumn();
    }
    data.status = statusForFormListing(data);
});

if (props.startExpanded) getFormList()
</script>

<template>
    <template v-if="expanded">
        <h4 class="my-4">Form Selection</h4>
        <div class="row">
            <div class="col-12 col-xl-6">
                <div class="w-100 d-flex align-items-center justify-content-center">
                    <div class="me-2 text-primary">Name Filter:</div>
                    <div class="flex-grow-1  btn-group" role="group" aria-label="Name Filter">
                        <input type="text" class="form-control" id="nameFilter"
                               placeholder="Enter search term here"
                               v-model="nameFilter" @input="nameFilterChanged"
                        >
                    </div>
                </div>
            </div>
            <div class="col-12 col-xl-6">
                <div class="w-100 d-flex align-items-center justify-content-center">
                    <div class="me-2 text-primary">Status Filter:</div>
                    <div class="flex-grow-1  btn-group" role="group" aria-label="Status Filter">
                        <input type="radio" class="btn-check" name="filter" id="filter_none" autocomplete="off"
                               v-model="statusFilter" value="" @change="statusFilterChanged"
                        >
                        <label class="btn btn-outline-secondary" for="filter_none">All Forms</label>

                        <input type="radio" class="btn-check" name="filter" id="filter_review" autocomplete="off"
                               v-model="statusFilter" value="Awaiting Review" @change="statusFilterChanged"
                        >
                        <label class="btn btn-outline-secondary" for="filter_review">Waiting for Review</label>

                        <input type="radio" class="btn-check" name="filter" id="filter_revise" autocomplete="off"
                               v-model="statusFilter" value="Revision Needed" @change="statusFilterChanged"
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
        <DataTable v-else id="table" class="table table-dark table-hover table-striped"
                   :options="tableOptions" :data="formList"
        >
            <thead>
            <tr>
                <th>Name</th>
                <th v-if="seeingAccounts">Account</th>
                <th>Credit</th>
                <th>Last Edit</th>
                <th class="text-center">Approved?</th>
                <th class="text-center">Published?</th>
                <th>Status</th>
            </tr>
            </thead>
            <template #column-approved="dt: DataTablesNamedSlotProps">
                <i class="fa-solid fa-check w-100 text-center"
                   v-if="(dt.rowData as FormListing).approved"
                ></i>
            </template>
            <template #column-published="dt: DataTablesNamedSlotProps">
                <i class="fa-solid fa-check w-100 text-center"
                   v-if="(dt.rowData as FormListing).published"
                ></i>
            </template>
        </DataTable>
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

<style scoped lang="scss">
:deep(#table tbody) {
    cursor: pointer;
}
</style>
