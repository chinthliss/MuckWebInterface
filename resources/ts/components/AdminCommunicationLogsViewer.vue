<script lang="ts" setup>
import {ref, Ref} from "vue";
import Spinner from "./Spinner.vue";
import {csrf} from "../siteutils";
import {AxiosError} from "axios";
import {capital, muckColorCodesToHtml} from "../formatting";

type LogEntry = {
    when_at: string,
    time?: string
    from_dbref: number,
    from_name: string,
    to_dbref: number,
    to_name: string,
    content: string
}

type GroupedLogEntries = {
    [date: string]: LogEntry[]
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
const toTitle: Ref<string> = ref('');
const errors: Ref<Errors> = ref({});
const logByDate: Ref<GroupedLogEntries> = ref({});
let loading = ref(false);

const nameAndNumber = (name: string, dbref: number): string => {
    let dbrefComponent = '';
    if (dbref && dbref !== -1) dbrefComponent = '<sup class="text-muted ">(#' + dbref + ')</sup>';
    return capital(name) + dbrefComponent
}

const typeChanged = (): void => {
    errors.value = {};
}

const retrieveLog = (e: Event): void => {
    e.preventDefault();
    errors.value = {};
    loading.value = true;
    logByDate.value = {};
    toTitle.value = '';
    axios.post(window.location.href, {
        'type': logType.value,
        'from': from.value,
        'to': to.value,
        '_token': csrf()
    }).then((response) => {
        for (const entry of (response.data as LogEntry[])) {
            const [date, time] = entry.when_at.split(" ", 2);
            entry.time = time;
            if (!logByDate.value[date]) logByDate.value[date] = [];
            logByDate.value[date].push(entry);
            if (!toTitle.value) toTitle.value = nameAndNumber(entry.to_name, entry.to_dbref);
        }
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

    <form action="" method="POST" @submit="retrieveLog">

        <div class="d-flex flex-column flex-lg-row align-items-center">
            <!-- Type selector -->
            <div class="d-flex align-items-center justify-content-left me-2">
                <div class="me-2 text-primary" v-bind:class="{ 'is-invalid' : errors.type }">Type:</div>
                <div aria-label="Select Type buttons" class="btn-group" role="group" @change="typeChanged">

                    <input id="type_ooc" v-model="logType" autocomplete="off" class="btn-check" name="type_select"
                           type="radio" value="ooc"
                    >
                    <label class="btn btn-outline-primary" for="type_ooc">OOC</label>

                    <input id="type_ic" v-model="logType" autocomplete="off" class="btn-check" name="type_select"
                           type="radio" value="ic"
                    >
                    <label class="btn btn-outline-primary" for="type_ic">IC</label>

                    <input id="type_channel" v-model="logType" autocomplete="off" class="btn-check" name="type_select"
                           type="radio" value="channel"
                    >
                    <label class="btn btn-outline-primary" for="type_channel">Channel</label>

                    <input id="type_page" v-model="logType" autocomplete="off" class="btn-check" name="type_select"
                           type="radio" value="page"
                    >
                    <label class="btn btn-outline-primary" for="type_page">Page</label>
                </div>
                <div class="invalid-feedback ms-2" role="alert">
                    <div v-for="error in errors?.type">{{ error }}</div>
                </div>

            </div>

            <!-- Instructions -->
            <div>
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
                    Page logs. Both 'From' or 'To' fields must be entered.
                    Both values can either be a complete name or a dbref.
                </div>
                <div v-else>
                    Select a type to see additional criteria / instructions.
                </div>
            </div>

        </div>

        <div class="row">

            <!-- From -->
            <div v-if="logType == 'page'" class="col-12 col-lg-6 form-group mt-2">
                <label for="from" v-bind:class="{ 'is-invalid' : errors.from }">From</label>
                <input id="from" v-model="from" class="form-control" type="text">
                <div class="invalid-feedback" role="alert">
                    <p v-for="error in errors?.from">{{ error }}</p>
                </div>
            </div>

            <!-- To -->
            <div class="col-12 col-lg-6 form-group mt-2">
                <label for="to" v-bind:class="{ 'is-invalid' : errors.to }">To</label>
                <input id="to" v-model="to" class="form-control" type="text">
                <div class="invalid-feedback" role="alert">
                    <p v-for="error in errors?.to">{{ error }}</p>
                </div>
            </div>

        </div>
        <button class="btn btn-primary mt-2" type="submit" value="submit">Retrieve Logs</button>
    </form>

    <Spinner v-if="loading"/>
    <div v-else>
        <div v-if="logType != 'page' && toTitle" class="mt-2">
            To: <span v-html="toTitle"/>
        </div>
        <table class="table table-dark table-hover table-striped table-responsive mt-2">
            <thead>
            <tr>
                <th scope="col">When</th>
                <th scope="col">From</th>
                <th v-if="logType == 'page'" scope="col">To</th>
                <th scope="col">Content</th>
            </tr>
            </thead>
            <tbody>
            <tr v-if="!logByDate">
                <td colspan="4">
                    No log entries found.
                </td>
            </tr>
            <template v-for="(logEntries, date) in logByDate" v-else>
                <tr>
                    <td class="fw-bold text-primary" colspan="4">{{ date }}</td>
                </tr>
                <tr v-for="logEntry in logEntries">
                    <td>{{ logEntry.time }}</td>
                    <td v-html="nameAndNumber(logEntry.from_name, logEntry.from_dbref)"/>
                    <td v-if="logType == 'page'" v-html="nameAndNumber(logEntry.to_name, logEntry.to_dbref)"/>
                    <td>{{ muckColorCodesToHtml(logEntry.content) }}</td>
                </tr>
            </template>
            </tbody>
        </table>
    </div>

</template>

<style scoped>

</style>
