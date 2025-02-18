<script setup lang="ts">

import {ref, Ref} from "vue";
import Spinner from "./Spinner.vue";
import {arrayToStringWithBreaks, capital} from "../formatting";

import DataTable from 'datatables.net-vue3';
import DataTablesLib, {Config as DataTableOptions} from 'datatables.net-bs5';

DataTable.use(DataTablesLib);

const channel = mwiWebsocket.channel('info');

type StatusListing = {
    status: string,
    properties: string[],
    desc: string,
    fragment: string
}

const statuses: Ref<StatusListing[]> = ref([]);
const statusesToLoad: Ref<number | null> = ref(null);
const statusesToLoadRemaining: Ref<number> = ref(-1);

const tableOptions: DataTableOptions = {
    info: false,
    paging: false,
    language: {
        emptyTable: "No notifications to show."
    },
    columns: [
        {data: 'status', name: 'Status'},
        {data: 'desc', orderable: false},
        {data: 'fragment', orderable: false},
        {data: 'properties', orderable: false, render: arrayToStringWithBreaks}
    ]
};

channel.on('statusList', (data: number) => {
    statuses.value = [];
    statusesToLoad.value = statusesToLoadRemaining.value = data;
});

channel.on('status', (data: StatusListing) => {
    // Tweak how the properties show
    const formattedProperties: string[] = []
    for (const property of data.properties) {
        const parts: string[] = property.split('/', 3);
        if (parts.length == 3) {
            let [category, name, location] = parts;
            category = capital(category);
            formattedProperties.push(`${category} ${name} - ${location}`);
        } else formattedProperties.push(property);
    }
    data.properties = formattedProperties;
    statuses.value.push(data);
    statusesToLoadRemaining.value--;
});

channel.send('getAllStatuses');

</script>

<template>
    <spinner v-if="statusesToLoadRemaining"></spinner>
    <div v-else>
        <DataTable id="table" class="table table-dark table-hover table-striped"
                   :options="tableOptions" :data="statuses"
        >
            <thead>
            <tr>
                <th>Status</th>
                <th>Description</th>
                <th>Tooltip</th>
                <th>Places Found</th>
            </tr>
            </thead>
        </DataTable>
    </div>

</template>

<style scoped>

</style>
