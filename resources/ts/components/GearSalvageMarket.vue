<script lang="ts" setup>

import {onMounted, Ref, ref} from "vue";
import {capital} from "../formatting";
import ModalConfirmation from "./ModalConfirmation.vue";
import {lex} from "../siteutils";

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

type ConversionDetails = {
    cost: number,
    quantity: number,
    what: string
}

type SalvageConfig = {
    [type: string]: {
        [rank: string]: {
            tokens?: ConversionDetails,
            upscale?: ConversionDetails,
            downscale?: ConversionDetails
        }
    }
}

const types: Ref<string[]> = ref([]);
const ranks: Ref<string[]> = ref([]);
const owned: Ref<SalvageOwned> = ref({})
const prices: Ref<SalvagePrices> = ref({})
const config: Ref<SalvageConfig> = ref({})
const transactionModal: Ref<InstanceType<typeof ModalConfirmation> | null> = ref(null);
const transactionType: Ref<string> = ref('');
const transactionConfig: Ref<ConversionDetails | null> = ref(null);
const transactionSalvageType: Ref<string> = ref('');
const transactionSalvageRank: Ref<string> = ref('');
const transactionQuantity: Ref<number> = ref(0);
const transactionQuote: Ref<number | null> = ref(null);

const channel = mwiWebsocket.channel('gear');

const acceptTransaction = () => {
    console.log("Not implemented");
}

const startTransaction = (type: string, rank: string, conversion: ConversionDetails) => {
    transactionQuantity.value = 1;
    transactionQuote.value = null;
    transactionSalvageType.value = type;
    transactionSalvageRank.value = rank;
    transactionConfig.value = conversion;
    if (transactionModal.value) transactionModal.value.show();
}
const requestDownscale = (type: string, rank: string) => {
    const conversion: ConversionDetails | undefined = config.value[type][rank]?.downscale;
    if (!conversion) throw `Couldn't find downscale details for ${rank} ${type}`;
    transactionType.value = 'downscale';
    startTransaction(type, rank, conversion);
}

const requestUpscale = (type: string, rank: string) => {
    const conversion: ConversionDetails | undefined = config.value[type][rank]?.upscale;
    if (!conversion) throw `Couldn't find upscale details for ${rank} ${type}`;
    transactionType.value = 'upscale';
    startTransaction(type, rank, conversion);
}

const startConvertToTokens = (type: string, rank: string) => {
    const conversion: ConversionDetails | undefined = config.value[type][rank]?.tokens;
    if (!conversion) throw `Couldn't find token reward details for ${rank} ${type}`;
    transactionType.value = 'token';
    startTransaction(type, rank, conversion);
}

const startBuySalvage = (type: string, rank: string) => {
    const conversion: ConversionDetails = {
        cost: prices.value[type].prices[rank].buy,
        quantity: 1,
        what: capital(rank) + ' ' + capital(type)
    }
    transactionType.value = 'buy';
    startTransaction(type, rank, conversion);
}

const startSellSalvage = (type: string, rank: string) => {
    const conversion: ConversionDetails = {
        cost: 1,
        quantity: prices.value[type].prices[rank].sell,
        what: lex('money')
    }
    transactionType.value = 'sell';
    startTransaction(type, rank, conversion);
}

const renderOwnedFor = (type: string, rank: string): string => {
    if (type in owned.value && rank in owned.value[type])
        return owned.value[type][rank].toLocaleString();
    else
        return '0';
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
    <div v-for="type in types">
        <hr/>
        <div class="d-flex align-items-center">
            <div class="flex-grow-1"><h3>{{ capital(type) }}</h3></div>
            <div>Market demand: {{ ((type in prices ? prices[type].demand : 1.0) * 100).toFixed(2) }}%</div>
        </div>

        <!-- We use two structures here
            On a bigger screen we use a big table
            On a smaller screen we use a table per what would have been a 'row' on the larger table
        -->

        <!-- Small screens -->
        <div class="d-xl-none">
            <div v-for="rank in ranks">

                <table class="table table-dark table-hover">
                    <thead>
                    <tr>
                        <th class="text-center" colspan="3" scope="col">{{ capital(rank) }} {{ capital(type) }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    <!-- Owned -->
                    <tr class="align-middle">
                        <td>Owned</td>
                        <td class="text-end">{{ renderOwnedFor(type, rank) }}</td>
                        <td></td>
                    </tr>

                    <!-- Buy -->
                    <tr class="align-middle">
                        <td>Buy Price</td>
                        <td class="text-end">{{ renderBuyPriceFor(type, rank) }}</td>
                        <td class="text-center">
                            <button class="btn btn-secondary" @click="startBuySalvage(type, rank)">
                                <i class="fas fa-coins btn-icon-left"></i>Buy
                            </button>
                        </td>
                    </tr>

                    <!-- Sell -->
                    <tr class="align-middle">
                        <td>Sell Price</td>
                        <td class="text-end">{{ renderSellPriceFor(type, rank) }}</td>
                        <td class="text-center">
                            <button class="btn btn-secondary" @click="startSellSalvage(type, rank)">
                                <i class="fas fa-coins btn-icon-left"></i>Sell
                            </button>
                        </td>
                    </tr>
                    </tbody>
                </table>
                <!-- Other Controls -->
                <div class="text-center">
                    <button v-if="config[type][rank].downscale"
                            class="btn btn-secondary me-2 my-1" @click="requestDownscale(type, rank)">
                        <i class="fas fa-down-long btn-icon-left"></i>Downscale {{
                            config[type][rank].downscale
                        }}
                    </button>

                    <button v-if="config[type][rank].upscale"
                            class="btn btn-secondary me-2 my-1" @click="requestUpscale(type, rank)">
                        <i class="fas fa-up-long btn-icon-left"></i>Upscale {{ config[type][rank].upscale }}
                    </button>

                    <button v-if="config[type][rank].tokens"
                            class="btn btn-secondary me-2 my-1" @click="startConvertToTokens(type, rank)">
                        <i class="fas fa-medal btn-icon-left"></i>Convert to {{ config[type][rank].tokens }}
                        tokens
                    </button>
                </div>


            </div>
        </div>

        <!-- Big screens -->
        <table class="d-none d-xl-table table table-dark table-hover table-responsive">
            <thead>
            <tr>
                <th scope="col">Rarity</th>
                <th scope="col" class="text-end">Owned</th>
                <th scope="col" class="text-end">Buy Price</th>
                <th scope="col"></th>
                <th scope="col" class="text-end">Sell Price</th>
                <th scope="col"></th>
                <th scope="col">Conversions</th><!-- Convert to reward tokens and upscale/downscale -->
            </tr>
            </thead>
            <tbody>
            <tr v-for="rank in ranks" class="align-middle">
                <td>{{ capital(rank) }}</td>
                <td class="text-end">{{ renderOwnedFor(type, rank) }}</td>
                <td class="text-end">{{ renderBuyPriceFor(type, rank) }}</td>
                <td>
                    <button class="btn btn-secondary my-1" @click="startBuySalvage(type, rank)">
                        <i class="fas fa-coins btn-icon-left"></i>Buy
                    </button>
                </td>
                <td class="text-end">{{ renderSellPriceFor(type, rank) }}</td>
                <td>
                    <button class="btn btn-secondary my-1" @click="startSellSalvage(type, rank)">
                        <i class="fas fa-coins btn-icon-left"></i>Sell
                    </button>
                </td>
                <td>
                    <template v-if="type in config && rank in config[type]">

                        <button v-if="config[type][rank].downscale"
                                class="btn btn-secondary me-2 my-1" @click="requestDownscale(type, rank)">
                            <i class="fas fa-down-long btn-icon-left"></i>Downscale
                            {{ config[type][rank].downscale.cost }} to
                            {{ config[type][rank].downscale.quantity }} {{ config[type][rank].downscale.what }}
                        </button>

                        <button v-if="config[type][rank].upscale"
                                class="btn btn-secondary me-2 my-1" @click="requestUpscale(type, rank)">
                            <i class="fas fa-up-long btn-icon-left"></i>Upscale
                            {{ config[type][rank].upscale.cost }} to
                            {{ config[type][rank].upscale.quantity }} {{ config[type][rank].upscale.what }}
                        </button>

                        <button v-if="config[type][rank].tokens"
                                class="btn btn-secondary me-2 my-1" @click="startConvertToTokens(type, rank)">
                            <i class="fas fa-medal btn-icon-left"></i>Convert
                            {{ config[type][rank].tokens.cost }} to
                            {{ config[type][rank].tokens.quantity }} {{ config[type][rank].tokens.what }}
                        </button>
                    </template>
                </td>
            </tr>
            </tbody>
        </table>

    </div>

    <modal-confirmation ref="transactionModal" title="Confirm Transaction"
                        no-label="Cancel" yes-label="Confirm"
                        @yes="acceptTransaction"
    >
        <p>Type: {{ transactionType }}</p>
        <p>You Pay: {{ transactionConfig?.cost }} x
            <span v-if="transactionType == 'buy'"> {{ lex('money') }}</span>
            <span v-else> {{ capital(transactionSalvageRank) }} {{ capital(transactionSalvageType) }} </span>
        </p>
        <p>You get: {{ transactionConfig?.quantity }} x {{ transactionConfig?.what }} </p>
        <p>Amount: {{ transactionQuantity  }}</p>
        <p>Quote: {{ transactionQuote || 'Unknown' }}</p>
    </modal-confirmation>
</template>

<style scoped>

</style>
