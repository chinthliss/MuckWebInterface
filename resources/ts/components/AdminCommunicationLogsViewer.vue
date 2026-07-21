<script setup lang="ts">
import {ref, Ref} from "vue";
import Spinner from "./Spinner.vue";
import DataTable from 'datatables.net-vue3';
import DataTablesLib, {Config as DataTableOptions} from 'datatables.net-bs5';
import {integer} from "@vue/language-server";
import {csrf} from "../siteutils";

DataTable.use(DataTablesLib);

type LogEntry = {
    when_at: string,
    from_dbref: integer,
    from_name: string,
    to_dbref: integer,
    to_name: string,
    content: string
}

const logType: Ref<string> = ref('');
const from: Ref<string> = ref('');
const to: Ref<string> = ref('');
const log: Ref<LogEntry[]> = ref([]);
let loading = ref(false);

const tableOptions: DataTableOptions = {
    info: false,
    paging: false,
    searching: false,
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
</script>

<template>

    <hr/>

    <div v-if="logType == 'ooc'">
        Room OOC logs. You'll need to enter the dbref of the room into the 'From' field'.
    </div>
    <div v-else-if="logType == 'ic'">
        Room IC logs. You'll need to enter the dbref of the room into the 'From' field'.
    </div>
    <div v-else-if="logType == 'channel'">
        Channel logs. The 'From' field should be the channel's name.
    </div>
    <div v-else-if="logType == 'page'">
        Page logs. At least one of the 'From' or 'To' fields must be entered.
        Both values can either be a complete name or a dbref.
    </div>
    <div v-else>
        Select a type to see additional criteria / instructions.
    </div>


    <!-- Type selector -->
    <form action="" method="POST">
        <input type="hidden" name="_token" :value="csrf()">

        <div class="d-flex align-items-center justify-content-center">
            <div class="me-2 text-primary">Type:</div>
            <div class="btn-group" role="group" aria-label="Select Type buttons">

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

        </div>

        <div class="form-group">
            <label for="from">From</label>
            <input type="text" class="form-control" id="from" v-model="from">

        </div>

        <div class="form-group">
            <label for="to">To</label>
            <input type="text" class="form-control" id="to" v-model="to">
        </div>

        <button type="submit" value="submit" class="btn btn-primary">Retrieve Logs</button>
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
    </DataTable>

</template>

<style scoped>

</style>
