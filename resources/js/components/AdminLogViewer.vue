<script setup>
import {ref} from "vue";
import Spinner from "./Spinner.vue";
import DataTable from 'datatables.net-vue3';
import {carbonToString, usdToString} from "../formatting";

const logRegEx = /^\[\d{4}-\d\d-\d\d (\d\d:\d\d:\d\d)] (?:\S*)?\.(\S*): /mg;

const props = defineProps({
    dates: {Type: Array, required: true}
});

const log = ref();

let loading = ref(false);

const renderLines = (lines) => {
    return lines.join('\n');
}

const logTableConfiguration = {
    columns: [
        {data: 'time'},
        {data: 'level'},
        {data: 'lines', render: renderLines},
    ],
    language: {
        "emptyTable": "No lines in log file."
    },
    paging: false,
    info: false,
    searching: false,
    ordering: false
};

const loadDate = (date, event) => {
    event.preventDefault();
    loading.value = true;
    axios.get('/admin/logs/' + date)
        .then(response => parseLog(response.data))
        .finally(() => { loading.value = false });
}

const parseLog =  (rawText) => {
    log.value = [];
    let slicePoints = [];
    // Figure out where individual line entries start
    let token = logRegEx.exec(rawText);
    while (token) {
        log.value.push({
            time: token[1],
            level: token[2]
        });
        slicePoints.push({
            token_starts: token.index,
            log_starts: token.index + token[0].length,
        });
        token = logRegEx.exec(rawText);
    }
    // Slice log around the points found
    for (let i = slicePoints.length - 1; i >= 0; i--) {
        let logStart = slicePoints[i].log_starts;
        let logEnd = (i === slicePoints.length - 1 ? rawText.length : slicePoints[i + 1].token_starts);
        log.value[i].lines = rawText
            .slice(logStart, logEnd)
            .split('\n');
    }
}

const classForLevel = (level) => {
    if (['EMERGENCY', 'CRITICAL', 'ALERT', 'ERROR'].indexOf(level) !== -1) return 'text-danger';
    if (level === 'WARNING') return 'text-warning';
    return null;
}

</script>

<template>
    <div class="container">
        <h1>Site Logs</h1>

        <div class="d-flex align-items-start">

            <!-- Available log list -->
            <div class="ps-2 pe-2 text-nowrap border border-secondary rounded" id="date-selector">
                <div v-for="date in dates"><a href="#" @click="loadDate(date, $event)">{{ date }}</a></div>
            </div>

            <!-- View individual log -->
            <div class="flex-grow-1 ps-4">
                <Spinner v-if="loading"/>
                <div v-else-if="!log">
                    Select a date to view log entries.
                </div>
                <DataTable v-else class="table table-dark table-hover table-striped table-bordered"
                           :options="logTableConfiguration"
                           :data="log"
                >
                    <thead>
                    <tr>
                        <th scope="col">Time</th>
                        <th scope="col">Level</th>
                        <th scope="col">Log</th>
                    </tr>
                    </thead>
                </DataTable>
            </div>

        </div>

    </div>
</template>

<style scoped>
.log-line {
    word-break: break-word;
}
</style>
