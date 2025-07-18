<script lang="ts" setup>

import {onMounted, Ref, ref} from "vue";
import {capital} from "../formatting";

type SalvageOwned = {
    [type: string]: {
        [rank: string]: number
    }
}

type SalvagePrices = {
    [type: string]: {
        [rank: string]: {
            buy: number,
            sell: number,
            demand: number
        }
    }
}

const types: Ref<string[]> = ref([]);
const ranks: Ref<string[]> = ref([]);
const owned: Ref<SalvageOwned> = ref({})
const prices: Ref<SalvagePrices> = ref({})

const channel = mwiWebsocket.channel('gear');

channel.on('bootSalvageMarket', (response: {
    types: string[],
    ranks: string[]
    owned: SalvageOwned
}) => {
    types.value = response.types || [];
    ranks.value = response.ranks || [];
})

channel.on('salvageOwned', (response: SalvageOwned) => {
    owned.value = response || {};
})

channel.on('salvagePrices', (response: SalvagePrices) => {
    prices.value = response || {};
})

onMounted(() => {
    channel.send('bootSalvageMarket');
})

</script>

<template>
    <table class="table table-dark table-hover table-striped table-responsive">
        <thead>
        <tr>
            <th scope="col">Type</th>
            <th scope="col">Rarity</th>
            <th scope="col">Owned</th>
            <th scope="col">Demand Markup</th>
            <th scope="col">Buy Price</th>
            <th scope="col">Sell Price</th>
            <th scope="col">Conversions</th><!-- Convert to reward tokens and conversions -->
        </tr>
        </thead>
        <tbody>
        <template v-for="type in types">
            <tr v-for="rank in ranks">
                <td>{{ capital(type) }}</td>
                <td>{{ capital(rank) }}</td>
                <td>{{ (type in owned && rank in owned[type] ? owned[type][rank] : 0).toLocaleString() }}</td>
                <td>{{
                        (type in prices && rank in prices[type] ? prices[type][rank].demand : 1.0) * 100
                    }}%</td>
                <td>{{ type in prices && rank in prices[type] ? prices[type][rank].buy.toLocaleString() : '??' }}</td>
                <td>{{ type in prices && rank in prices[type] ? prices[type][rank].sell.toLocaleString() : '??' }}</td>
                <td>N/A</td>
            </tr>
        </template>
        </tbody>
    </table>
</template>

<style scoped>

</style>
