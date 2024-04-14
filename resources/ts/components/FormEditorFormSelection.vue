<script setup lang="ts">

import {ref, Ref} from "vue";
import DataTable from 'primevue/datatable';
import Column from "primevue/column";
import ProgressBar from "primevue/progressbar";
import {FilterMatchMode} from "primevue/api";

type FormListing = {
    name: string,
    owner?: number,
    approved: boolean,
    review: boolean,
    revise: boolean
}

const props = defineProps<{
    startExpanded: boolean,
}>();

const emit = defineEmits(['update'])

const channel = mwiWebsocket.channel('contribute');

const expanded: Ref<boolean> = ref(props.startExpanded);
const formListLoaded: Ref<boolean> = ref(false);
const formListLoadTotal: Ref<number> = ref(1); // May be set to 0 if the viewer can't see any forms.
const formListLoadLeft: Ref<number> = ref(1);
const formList: Ref<FormListing[]> = ref([]);
const selected: Ref<FormListing | undefined> = ref();

const filters = ref({
    name: {value: null, matchMode: FilterMatchMode.CONTAINS}
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

const rowSelected = () => {
    emit('update', selected.value?.name);
    expanded.value = false;
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
    <!-- Form Selection (or nothing)-->
    <div v-if="!expanded"></div>
    <ProgressBar v-else-if="formListLoadLeft" :indeterminate="!formListLoadTotal"
                 :value="(formListLoadTotal - formListLoadLeft) * 100 / formListLoadTotal"
    >
        {{ Math.floor((formListLoadTotal - formListLoadLeft) * 100 / formListLoadTotal) }}%
    </ProgressBar>
    <div v-else>
        <DataTable :value="formList" dataKey="name" size="small" stripedRows scrollable
                   scrollHeight="flex" @row-select="rowSelected" selectionMode="single"
                   v-model:selection="selected" v-model:filters="filters" filterDisplay="row"
        >
        <template #empty>There are no forms that you can view. Maybe you should create a new one?</template>
        <Column header="Name" field="name" :sortable="true">
            <template #filter="{ filterModel, filterCallback }">
                <input v-model="filterModel.value" type="text" @input="filterCallback()"
                       class="p-column-filter" placeholder="Search by name"
                />
            </template>
        </Column>
        <Column header="Owner" field="owner" :sortable="true"></Column>
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
    <!-- Expand or shrink buttons -->
    <div class="d-flex justify-content-end">
        <button class="btn btn-secondary" @click="toggleExpanded">
            <i v-if="expanded" class="fas fa-minus btn-icon-left"></i>
            <i v-else class="fas fa-plus btn-icon-left"></i>
            {{ expanded ? 'Hide' : 'Show' }} Form Selection
        </button>
    </div>
</template>

<style scoped>

</style>
