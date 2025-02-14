<script setup lang="ts">

import {ref, Ref} from "vue";
import Spinner from "./Spinner.vue";
import {ansiToHtml} from "../formatting";
import {lex} from "../siteutils";

const channel = mwiWebsocket.channel('info');

type StatusListing = {
    status: string,
    property: string,
    desc: string,
    fragment: string
}

const statuses: Ref<StatusListing[]> = ref([]);
const statusesToLoad: Ref<number | null> = ref(null);
const statusesToLoadRemaining: Ref<number> = ref(-1);

channel.on('statusList', (data: number) => {
    statuses.value = [];
    statusesToLoad.value = statusesToLoadRemaining.value = data;
});

channel.on('status', (data: StatusListing) => {
    statuses.value.push(data);
    statusesToLoadRemaining.value--;
});

channel.send('getAllStatuses');

</script>

<template>
    <spinner v-if="statusesToLoadRemaining"></spinner>
    <div v-else>
        Now display them!
    </div>

</template>

<style scoped>

</style>
