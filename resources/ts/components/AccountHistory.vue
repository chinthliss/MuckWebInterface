<script setup lang="ts">

import {AccountHistoryEntry} from "../defs";
import {ref, Ref} from "vue";
import {carbonToString} from "../formatting";

const props = defineProps<{
    historyIn: AccountHistoryEntry[]
}>();

const history: Ref<AccountHistoryEntry[]> = ref(props.historyIn);

</script>

<template>
    <table class="table table-dark table-hover table-striped table-responsive small">
        <thead>
        <tr>
            <th scope="col">When</th>
            <th scope="col">Game</th>
            <th scope="col">Message</th>
            <th scope="col">Balance</th>
        </tr>
        </thead>
        <tbody>
        <tr v-for="line in history">
            <td>{{ carbonToString(line.createdAt) }}</td>
            <td>{{ line.game }}</td>
            <td>{{ line.message }}</td>
            <td>{{ line.balance }}</td>
        </tr>
        </tbody>
    </table>
</template>

<style scoped>

</style>
