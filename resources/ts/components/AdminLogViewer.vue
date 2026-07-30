<script lang="ts" setup>
import {ref, Ref} from "vue";
import Spinner from "./Spinner.vue";
import {arrayToStringWithNewlines} from "../formatting";

const logRegEx = /^\[\d{4}-\d\d-\d\d (\d\d:\d\d:\d\d)] (?:\S*)?\.(\S*): /mg;

const props = defineProps<{
    dates: string[]
}>();

type LogEntry = {
    time: string,
    level: string,
    lines: string[]
}

type LogLevel = 'DEBUG' | 'INFO' | 'WARNING' | 'ERROR';

const logDate: Ref<string> = ref('');
const log: Ref<LogEntry[]> = ref([]);
const logLevel: Ref<LogLevel> = ref('INFO');

let loading = ref(false);

const classForLevel = (line: LogEntry): string => {
    if (['EMERGENCY', 'CRITICAL', 'ALERT', 'ERROR'].indexOf(line.level) != -1) return 'text-danger';
    if (line.level == 'WARNING') return 'text-warning';
    if (line.level == 'DEBUG') return 'text-muted';
    return '';
}

const shouldShow = (line: LogEntry): boolean => {
    switch (logLevel.value) {
        case 'DEBUG':
            return true;
        case 'INFO':
            return line.level != 'DEBUG';
        case 'WARNING':
            return !['DEBUG', 'INFO'].includes(line.level);
        case 'ERROR':
            return !['WARNING', 'DEBUG', 'INFO'].includes(line.level);
    }
}


const loadDate = (date: string, event: Event) => {
    event.preventDefault();
    loading.value = true;
    logDate.value = date;
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
            <div id="date-selector" class="ps-2 pe-2 text-nowrap border border-secondary rounded">
                <div v-for="date in props.dates"><a href="#" @click="loadDate(date, $event)">{{ date }}</a></div>
            </div>

            <!-- View individual log -->
            <div class="flex-grow-1 ps-2">

                <!-- Date & LogLevel Filter -->
                <div class="d-flex align-items-center justify-content-end">
                    <div v-if="logDate" class="me-4">
                        <span class="fw-bold text-primary">Showing:</span> {{ logDate }}
                    </div>
                    <div class="me-2 text-primary">Filter Log Level:</div>
                    <div aria-label="Log Level Filter" class="btn-group" role="group">

                        <input id="level_error" v-model="logLevel" autocomplete="off" class="btn-check"
                               name="level_error"
                               type="radio" value="ERROR"
                        >
                        <label class="btn btn-outline-primary" for="level_error">Error</label>

                        <input id="level_warning" v-model="logLevel" autocomplete="off" class="btn-check"
                               name="level_warning"
                               type="radio" value="WARNING"
                        >
                        <label class="btn btn-outline-primary" for="level_warning">Warning</label>

                        <input id="level_info" v-model="logLevel" autocomplete="off" class="btn-check" name="level_info"
                               type="radio" value="INFO"
                        >
                        <label class="btn btn-outline-primary" for="level_info">Info</label>

                        <input id="level_debug" v-model="logLevel" autocomplete="off" class="btn-check"
                               name="level_debug"
                               type="radio" value="DEBUG"
                        >
                        <label class="btn btn-outline-primary" for="level_debug">Debug</label>

                    </div>

                </div>

                <Spinner v-if="loading"/>
                <div v-else-if="!log.length">
                    Select a date to view log entries.
                </div>
                <div v-else>
                    <table class="table table-dark table-hover table-striped table-responsive">
                        <thead>
                        <tr>
                            <th scope="col">Time</th>
                            <th scope="col">Level</th>
                            <th scope="col">Log</th>
                        </tr>
                        </thead>
                        <tbody>
                        <template v-for="line in log">
                            <tr v-if="shouldShow(line)">
                                <td>{{ line.time }}</td>
                                <td :class="classForLevel(line)">{{ line.level }}</td>
                                <td>{{ arrayToStringWithNewlines(line.lines) }}</td>
                            </tr>
                        </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</template>

<style scoped>

</style>
