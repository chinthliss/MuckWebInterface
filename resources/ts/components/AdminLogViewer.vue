<script setup lang="ts">
import {ref, Ref} from "vue";
import Spinner from "./Spinner.vue";
import {DataTablesNamedSlotProps} from "../defs";

import DataTable from 'datatables.net-vue3';
import DataTablesLib, {Config as DataTableOptions} from 'datatables.net-bs5';

DataTable.use(DataTablesLib);

const logRegEx = /^\[\d{4}-\d\d-\d\d (\d\d:\d\d:\d\d)] (?:\S*)?\.(\S*): /mg;

const props = defineProps<{
    dates: string[]
}>();

type LogEntry = {
    time: string,
    level: string,
    lines: string[]
}
const log: Ref<LogEntry[]> = ref([]);

let loading = ref(false);

const renderLines = (lines: string[]): string => {
    return lines.join('\n');
}

const tableOptions: DataTableOptions = {
    info: false,
    paging: false,
    searching: false,
    language: {
        emptyTable: "No lines in log file."
    },
    columns: [
        {data: 'time'},
        {data: 'level', name: 'level'},
        {data: 'lines', render: renderLines, className: 'text-break'}
    ]
};

const classForLevel = (data: LogEntry):string => {
    if (['EMERGENCY', 'CRITICAL', 'ALERT', 'ERROR'].indexOf(data.level) !== -1) return 'text-danger';
    if (data.level === 'WARNING') return 'text-warning';
    return '';
}


const loadDate = (date: string, event: Event) => {
    event.preventDefault();
    loading.value = true;
    axios.get('/admin/logs/' + date)
        .then(response => parseLog(response.data))
        .finally(() => {
            loading.value = false
        });
}

const parseLog = (rawText: string) => {
    const newLog: LogEntry[] = [];
    let slicePoints = [];
    // Figure out where individual line entries start
    let token = logRegEx.exec(rawText);
    while (token) {
        newLog.push({
            time: token[1],
            level: token[2],
            lines: []
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
        newLog[i].lines = rawText
            .slice(logStart, logEnd)
            .split('\n');
    }
    log.value = newLog;
}

</script>

<template>
    <div class="container">
        <h1>Site Logs</h1>

        <div class="d-flex align-items-start">

            <!-- Available log list -->
            <div class="ps-2 pe-2 text-nowrap border border-secondary rounded" id="date-selector">
                <div v-for="date in props.dates"><a href="#" @click="loadDate(date, $event)">{{ date }}</a></div>
            </div>

            <!-- View individual log -->
            <div class="flex-grow-1 ps-4">
                <Spinner v-if="loading"/>
                <div v-else-if="!log.length">
                    Select a date to view log entries.
                </div>
                <DataTable v-else class="table table-dark table-hover table-striped"
                           :options="tableOptions" :data="log"
                >
                    <thead>
                    <tr>
                        <th>Time</th>
                        <th>Level</th>
                        <th>Log</th>
                    </tr>
                    </thead>
                    <template #column-level="dt: DataTablesNamedSlotProps">
                        <span :class="classForLevel(dt.rowData)">{{ (dt.rowData as LogEntry).level }}</span>
                    </template>
                </DataTable>
            </div>
        </div>

    </div>
</template>

<style scoped>

</style>
