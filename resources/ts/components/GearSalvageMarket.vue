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
        demand: number
        prices: {
            [rank: string]: {
                buy: number,
                sell: number,
            }
        }
    }
}

type SalvageConfig = {
    [type: string]: {
        [rank: string]: {
            tokens?: number
            upscale?: string,
            downscale?: string
        }
    }
}

const types: Ref<string[]> = ref([]);
const ranks: Ref<string[]> = ref([]);
const owned: Ref<SalvageOwned> = ref({})
const prices: Ref<SalvagePrices> = ref({})
const config: Ref<SalvageConfig> = ref({})

const channel = mwiWebsocket.channel('gear');

const requestDownscale = (type: string, rank: string) => {
    throw ("Not implemented");
}

const requestUpscale = (type: string, rank: string) => {
    throw ("Not implemented");
}

const startConvertToTokens = (type: string, rank: string) => {
    throw ("Not implemented");
}

const startBuySalvage = (type: string, rank: string) => {
    throw ("Not implemented");
}

const startSellSalvage = (type: string, rank: string) => {
    throw ("Not implemented");
}

const renderBuyPriceFor = (type: string, rank: string): string => {
    if (type in prices.value && rank in prices.value[type].prices)
        return prices.value[type].prices[rank].buy.toLocaleString();
    else
        return 'Unknown';
}

const renderSellPriceFor = (type: string, rank: string): string => {
    if (type in prices.value && rank in prices.value[type].prices)
        return prices.value[type].prices[rank].sell.toLocaleString();
    else
        return 'Unknown';
}

channel.on('bootSalvageMarket', (response: {
    types: string[],
    ranks: string[]
    config: SalvageConfig
}) => {
    types.value = response.types || [];
    ranks.value = response.ranks || [];
    config.value = response.config || {};
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
    <template v-for="type in types">
        <hr/>
        <div class="d-flex align-items-center">
            <div class="flex-grow-1"><h3>{{ capital(type) }}</h3></div>
            <div>Market demand: {{ (type in prices ? prices[type].demand : 1.0) * 100 }}%</div>
        </div>

        <div class="row">
            <div class="col-6 fw-bold">Rarity</div>
            <div class="col-6 fw-bold">Owned</div>
            <div class="col-6 fw-bold">Buy</div>
            <div class="col-6 fw-bold">Sell</div>
        </div>
        <div v-for="rank in ranks" class="row">
            <div class="col-6">{{ capital(rank) }}</div>
            <div class="col-6">
                {{ (type in owned && rank in owned[type] ? owned[type][rank] : 0).toLocaleString() }}
            </div>
            <div class="col-6">
                <div class="fixedNumber">{{ renderBuyPriceFor(type, rank) }} </div>
                <button class="btn btn-secondary ms-2" @click="startBuySalvage(type, rank)">
                    <i class="fas fa-coins btn-icon-left"></i>Buy
                </button>
            </div>
            <div class="col-6">
                <div class="d-inline-block fixedNumber">{{ renderSellPriceFor(type, rank) }}</div>
                <button class="btn btn-secondary ms-2" @click="startSellSalvage(type, rank)">
                    <i class="fas fa-coins btn-icon-left"></i>Sell
                </button>
            </div>
            <div class="col-12">
                <template v-if="type in config && rank in config[type]">

                    <button v-if="config[type][rank].downscale"
                            class="btn btn-secondary ms-2" @click="requestDownscale(type, rank)">
                        <i class="fas fa-down-long btn-icon-left"></i>Downscale {{
                            config[type][rank].downscale
                        }}
                    </button>

                    <button v-if="config[type][rank].upscale"
                            class="btn btn-secondary ms-2" @click="requestUpscale(type, rank)">
                        <i class="fas fa-up-long btn-icon-left"></i>Upscale {{ config[type][rank].upscale }}
                    </button>

                    <button v-if="config[type][rank].tokens"
                            class="btn btn-secondary ms-2" @click="startConvertToTokens(type, rank)">
                        <i class="fas fa-medal btn-icon-left"></i>Convert to {{ config[type][rank].tokens }}
                        tokens
                    </button>
                </template>
            </div>
        </div>
    </template>

    <template v-for="type in types">
        <h3>{{ capital(type) }}</h3>
        Market demand: {{ (type in prices ? prices[type].demand : 1.0) * 100 }}%
        <div class="table-responsive">
            <table class="table table-dark table-hover table-responsive">
                <thead>
                <tr>
                    <th scope="col">Rarity</th>
                    <th scope="col">Owned</th>
                    <th colspan="2" scope="col">Buy Price</th>
                    <th colspan="2" scope="col">Sell Price</th>
                    <th scope="col">Conversions</th><!-- Convert to reward tokens and upscale/downscale -->
                </tr>
                </thead>
                <tbody>
                <tr v-for="rank in ranks" class="align-middle">
                    <td>{{ capital(rank) }}</td>
                    <td>{{ (type in owned && rank in owned[type] ? owned[type][rank] : 0).toLocaleString() }}</td>
                    <td>{{ renderBuyPriceFor(type, rank) }}</td>
                    <td>
                        <button class="btn btn-secondary" @click="startBuySalvage(type, rank)">
                            <i class="fas fa-coins btn-icon-left"></i>Buy
                        </button>
                    </td>
                    <td>{{
                            type in prices && rank in prices[type].prices ? prices[type].prices[rank].sell.toLocaleString()
                                : 'Unknown'
                        }}
                    </td>
                    <td>
                        <button class="btn btn-secondary" @click="startSellSalvage(type, rank)">
                            <i class="fas fa-coins btn-icon-left"></i>Sell
                        </button>
                    </td>
                    <td>
                        <template v-if="type in config && rank in config[type]">

                            <button v-if="config[type][rank].downscale"
                                    class="btn btn-secondary ms-2" @click="requestDownscale(type, rank)">
                                <i class="fas fa-down-long btn-icon-left"></i>Downscale {{
                                    config[type][rank].downscale
                                }}
                            </button>

                            <button v-if="config[type][rank].upscale"
                                    class="btn btn-secondary ms-2" @click="requestUpscale(type, rank)">
                                <i class="fas fa-up-long btn-icon-left"></i>Upscale {{ config[type][rank].upscale }}
                            </button>

                            <button v-if="config[type][rank].tokens"
                                    class="btn btn-secondary ms-2" @click="startConvertToTokens(type, rank)">
                                <i class="fas fa-medal btn-icon-left"></i>Convert to {{ config[type][rank].tokens }}
                                tokens
                            </button>
                        </template>
                    </td>
                </tr>
                </tbody>
            </table>
        </div>
    </template>
</template>

<style scoped>
    .fixedNumber {
        width: 15em;
        text-align: right;
        display: inline-block;
    }
</style>
