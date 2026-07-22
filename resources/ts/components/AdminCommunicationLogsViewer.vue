<script setup lang="ts">
import {ref, Ref} from "vue";
import Spinner from "./Spinner.vue";
import DataTable from 'datatables.net-vue3';
import DataTablesLib, {Config as DataTableOptions} from 'datatables.net-bs5';
import {DataTablesNamedSlotProps} from "../defs";
import {csrf} from "../siteutils";
import {AxiosError} from "axios";
import {ansiToHtml} from "../formatting";

DataTable.use(DataTablesLib);

type LogEntry = {
    when_at: string,
    from_dbref: number,
    from_name: string,
    to_dbref: number,
    to_name: string,
    content: string
}

type Errors = {
    type?: string[],
    from?: string[],
    to?: string[]
}

type AxiosErrorWithOurResponse = {
    errors: Errors
}

const logType: Ref<string> = ref('');
const from: Ref<string> = ref('');
const to: Ref<string> = ref('');
const errors: Ref<Errors> = ref({});
const log: Ref<LogEntry[]> = ref([]);
let loading = ref(false);

const tableOptions: DataTableOptions = {
    info: false,
    paging: false,
    searching: false,
    scrollX: true,
    language: {
        emptyTable: "No log entries found."
    },
    columns: [
        {data: 'when_at', name: 'when'},
        {data: null, name: 'from'},
        {data: null, name: 'to'},
        {data: 'content', name: 'content', orderable: false}
    ]
};

const nameAndNumber = (name: string, dbref: number): string => {
    let dbrefComponent = '';
    if (dbref && dbref !== -1) dbrefComponent = '(#' + dbref + ')';
    return name + dbrefComponent
}

const typeChanged = (): void => {
    errors.value = {};
}

const retrieveLog = (e: Event): void => {
    e.preventDefault();
    errors.value = {};
    loading.value = true;
    log.value = [];
    axios.post(window.location.href, {
        'type': logType.value,
        'from': from.value,
        'to': to.value,
        '_token': csrf()
    }).then((response) => {
        log.value = response.data;
    }).catch((error: AxiosError) => {
        console.log(error);
        if (error.status == 422) {
            let data: AxiosErrorWithOurResponse = (error.response?.data as AxiosErrorWithOurResponse);
            errors.value = (data.errors as Errors);
        }
    }).finally(() => {
        loading.value = false;
    })
}
</script>

<template>

    <hr/>

    <div v-if="logType == 'ooc'">
        Room OOC logs. Enter the dbref of the room into the 'To' field'.
    </div>
    <div v-else-if="logType == 'ic'">
        Room IC logs. Enter the dbref of the room into the 'To' field'.
    </div>
    <div v-else-if="logType == 'channel'">
        Channel logs. Enter the channel's name into the 'To' field.
    </div>
    <div v-else-if="logType == 'page'">
        Page logs. At least one of the 'From' or 'To' fields must be entered.
        Both values can either be a complete name or a dbref.
    </div>
    <div v-else>
        Select a type to see additional criteria / instructions.
    </div>


    <!-- Type selector -->
    <form action="" method="POST" @submit="retrieveLog">

        <div class="d-flex align-items-center justify-content-left">
            <div class="me-2 text-primary" v-bind:class="{ 'is-invalid' : errors.type }">Type:</div>
            <div class="btn-group" role="group" aria-label="Select Type buttons" @change="typeChanged">

                <input type="radio" class="btn-check" name="type_select" id="type_ooc" autocomplete="off"
                       v-model="logType" value="ooc"
                >
                <label class="btn btn-outline-primary" for="type_ooc">OOC</label>

                <input type="radio" class="btn-check" name="type_select" id="type_ic" autocomplete="off"
                       v-model="logType" value="ic"
                >
                <label class="btn btn-outline-primary" for="type_ic">IC</label>

                <input type="radio" class="btn-check" name="type_select" id="type_channel" autocomplete="off"
                       v-model="logType" value="channel"
                >
                <label class="btn btn-outline-primary" for="type_channel">Channel</label>

                <input type="radio" class="btn-check" name="type_select" id="type_page" autocomplete="off"
                       v-model="logType" value="page"
                >
                <label class="btn btn-outline-primary" for="type_page">Page</label>
            </div>
            <div class="invalid-feedback ms-2" role="alert">
                <div v-for="error in errors?.type">{{ error }}</div>
            </div>

        </div>

        <!-- From -->
        <div class="form-group mt-2" v-if="logType == 'page'">
            <label for="from" v-bind:class="{ 'is-invalid' : errors.from }">From</label>
            <input type="text" class="form-control" id="from" v-model="from">
            <div class="invalid-feedback" role="alert">
                <p v-for="error in errors?.from">{{ error }}</p>
            </div>
        </div>

        <!-- To -->
        <div class="form-group mt-2">
            <label for="to" v-bind:class="{ 'is-invalid' : errors.to }">To</label>
            <input type="text" class="form-control" id="to" v-model="to">
            <div class="invalid-feedback" role="alert">
                <p v-for="error in errors?.to">{{ error }}</p>
            </div>
        </div>

        <button type="submit" value="submit" class="btn btn-primary mt-2">Retrieve Logs</button>
    </form>

    <Spinner v-if="loading"/>
    <DataTable v-else class="table table-dark table-hover table-striped"
               :options="tableOptions" :data="log"
    >
        <thead>
        <tr>
            <th>When</th>
            <th>From</th>
            <th>To</th>
            <th>Content</th>
        </tr>
        </thead>
        <template #column-from="dt: DataTablesNamedSlotProps">
            {{ nameAndNumber((dt.rowData as LogEntry).from_name, (dt.rowData as LogEntry).from_dbref) }}
        </template>
        <template #column-to="dt: DataTablesNamedSlotProps">
            {{ nameAndNumber((dt.rowData as LogEntry).to_name, (dt.rowData as LogEntry).to_dbref) }}
        </template>
        <template #column-content="dt: DataTablesNamedSlotProps">
            <span v-html="ansiToHtml((dt.rowData as LogEntry).content)"/>
        </template>
    </DataTable>

</template>

<style scoped>

</style>
