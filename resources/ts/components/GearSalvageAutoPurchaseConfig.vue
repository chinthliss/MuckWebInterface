<script setup lang="ts">

import {onMounted, Ref, ref} from "vue";
import {capital} from "../formatting";

const ranks: Ref<string[]> = ref([]);

const channel = mwiWebsocket.channel('gear');

channel.on('bootSalvageAutoPurchaseConfig', (response: {
    ranks: string[]
}) => {
    ranks.value = response.ranks;
})

onMounted(() => {
    channel.send('bootSalvageAutoPurchaseConfig');
})

</script>

<template>
    <table class="table table-dark table-hover table-striped table-responsive w-auto">
        <thead>
        <tr>
            <th scope="col">Type</th>
            <th scope="col">Limit</th>
            <th></th><!-- Controls -->
        </tr>
        </thead>
        <tbody>
            <tr v-for="rank in ranks">
                <td>{{ capital(rank) }}</td>
                <td>WIP</td>
                <td></td>
            </tr>
        </tbody>
    </table>
</template>

<style scoped>

</style>
