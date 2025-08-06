<script lang="ts" setup>

import {onMounted, Ref, ref} from "vue";
import {capital} from "../formatting";
import {lex} from "../siteutils";
import ModalConfirmation from "./ModalConfirmation.vue";
import ModalMessage from "./ModalMessage.vue";
import Callout from "./Callout.vue";

type TransactionType = 'buy' | 'sell' | 'upscale' | 'downscale' | 'token';

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

type Transaction = {
    what: string
    cost: string,
    value: number,
    error?: string
}

const types: Ref<string[]> = ref([]);
const ranks: Ref<string[]> = ref([]);
const owned: Ref<SalvageOwned> = ref({})
const prices: Ref<SalvagePrices> = ref({})
const config: Ref<SalvageConfig> = ref({})
const money: Ref<number> = ref(0);
const transactionModal: Ref<InstanceType<typeof ModalConfirmation> | null> = ref(null);
const transactionResultModal: Ref<InstanceType<typeof ModalMessage> | null> = ref(null);
const transactionType: Ref<TransactionType> = ref('buy');
const transactionConfig: Ref<ConversionDetails | null> = ref(null);
const transactionSalvageType: Ref<string> = ref('');
const transactionSalvageRank: Ref<string> = ref('');
const transactionQuantity: Ref<number> = ref(0);
const transactionQuote: Ref<Transaction | null> = ref(null);

const transactionResultTitle: Ref<string> = ref('Transaction Failed');
const transactionResultText: Ref<string | null> = ref(null);

const channel = mwiWebsocket.channel('gear');

const acceptTransaction = () => {
    channel.send('salvageMarketTransaction', {
        'type': transactionType.value,
        'salvageType': transactionSalvageType.value,
        'salvageRank': transactionSalvageRank.value,
        'quantity': transactionQuantity.value,
        'quote': transactionQuote.value?.value,
    });
}

const getQuoteForTransaction = () => {
    transactionQuote.value = null;
    channel.send('salvageMarketQuote', {
        'type': transactionType.value,
        'salvageType': transactionSalvageType.value,
        'salvageRank': transactionSalvageRank.value,
        'quantity': transactionQuantity.value,
    })
}

const startTransaction = (type: string, rank: string, conversion: ConversionDetails) => {
    transactionQuantity.value = 1;
    transactionSalvageType.value = type;
    transactionSalvageRank.value = rank;
    transactionConfig.value = conversion;
    getQuoteForTransaction();
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

//#region Shared Rendering

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

const renderUpscaleLabelFor = (type: string, rank: string): string => {
    const conversion: ConversionDetails | undefined = config.value[type][rank].upscale;
    if (!conversion) throw `Couldn't render upscale details for ${rank} ${type}`;
    return `Upscale ${conversion.cost} to ${conversion.quantity} ${conversion.what}`;
}

const renderDownscaleLabelFor = (type: string, rank: string): string => {
    const conversion: ConversionDetails | undefined = config.value[type][rank].downscale;
    if (!conversion) throw `Couldn't render downscale details for ${rank} ${type}`;
    return `Downscale ${conversion.cost} to ${conversion.quantity} ${conversion.what}`;
}

const renderTokenizeLabelFor = (type: string, rank: string): string => {
    const conversion: ConversionDetails | undefined = config.value[type][rank].tokens;
    if (!conversion) throw `Couldn't render token details for ${rank} ${type}`;
    return `Convert ${conversion.cost} to ${conversion.quantity} ${conversion.what}`;
}

//#endregion

const titleForTransaction = (): string => {
    let activeWord: string | null = null;
    const what: string = capital(transactionSalvageRank.value) + ' ' + capital(transactionSalvageType.value);
    switch (transactionType.value) {
        case 'buy':
            activeWord = 'Buy';
            break;
        case 'sell':
            activeWord = 'Sell';
            break;
        case 'upscale':
            activeWord = 'Upscale';
            break;
        case 'downscale':
            activeWord = 'Downscale';
            break;
        case 'token':
            activeWord = 'Tokenize';
            break;
    }
    return activeWord ? activeWord + ' ' + what : 'Unknown';
}

/**
 * This is whether the player can at least consider doing a transaction
 */
const canDoPossibleTransaction = (transactionType: TransactionType, salvageType: string, salvageRank: string): boolean => {
    let result = false; // Until we find otherwise!
    let conversion: ConversionDetails | undefined;
    switch (transactionType) {
        case 'buy':
            if (salvageType in prices.value && salvageRank in prices.value[salvageType].prices)
                result = money.value >= prices.value[salvageType].prices[salvageRank].buy;
            break;
        case 'sell':
            if (salvageType in owned.value && salvageRank in owned.value[salvageType])
                result = owned.value[salvageType][salvageRank] > 0;
            break;
        case 'upscale':
            if (salvageType in config.value && salvageRank in config.value[salvageType])
                conversion = config.value[salvageType][salvageRank].upscale;
            if (conversion && salvageType in owned.value && salvageRank in owned.value[salvageType])
                result = owned.value[salvageType][salvageRank] >= conversion.cost;
            break;
        case 'downscale':
            if (salvageType in config.value && salvageRank in config.value[salvageType])
                conversion = config.value[salvageType][salvageRank].downscale;
            if (conversion && salvageType in owned.value && salvageRank in owned.value[salvageType])
                result = owned.value[salvageType][salvageRank] >= conversion.cost;
            break;
        case 'token':
            if (salvageType in config.value && salvageRank in config.value[salvageType])
                conversion = config.value[salvageType][salvageRank].tokens;
            if (conversion && salvageType in owned.value && salvageRank in owned.value[salvageType])
                result = owned.value[salvageType][salvageRank] >= conversion.cost;
            break;
    }
    return result;
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

channel.on('money', (amount: number) => {
    money.value = amount;
});

channel.on('salvageOwned', (response: SalvageOwned) => {
    owned.value = response || {};
})

channel.on('salvagePrices', (response: SalvagePrices) => {
    prices.value = response || {};
})

channel.on('salvageMarketQuote', (quote: Transaction) => {
    transactionQuote.value = quote;
})

channel.on('salvageMarketTransaction', (response: { success: boolean, text: string }) => {
    transactionResultTitle.value = response.success ? 'Transaction Successful' : 'Transaction failed';
    transactionResultText.value = response.text;
    if (transactionResultModal.value) transactionResultModal.value.show();
})

onMounted(() => {
    channel.send('bootSalvageMarket');
})

</script>

<template>
    <div class="text-center">
        <span class="text-primary">Owned {{ lex('money') }}: </span>
        <span>{{ money.toLocaleString() }}</span>
    </div>
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
                            <button :disabled="!canDoPossibleTransaction('buy', type, rank)" class="btn btn-secondary"
                                    @click="startBuySalvage(type, rank)"
                            >
                                <i class="fas fa-coins btn-icon-left"></i>Buy
                            </button>
                        </td>
                    </tr>

                    <!-- Sell -->
                    <tr class="align-middle">
                        <td>Sell Price</td>
                        <td class="text-end">{{ renderSellPriceFor(type, rank) }}</td>
                        <td class="text-center">
                            <button :disabled="!canDoPossibleTransaction('sell', type, rank)" class="btn btn-secondary"
                                    @click="startSellSalvage(type, rank)"
                            >
                                <i class="fas fa-coins btn-icon-left"></i>Sell
                            </button>
                        </td>
                    </tr>
                    </tbody>
                </table>

                <!-- Other Controls -->
                <div class="text-center">
                    <button v-if="config[type][rank].downscale"
                            :disabled="!canDoPossibleTransaction('downscale', type, rank)"
                            class="btn btn-secondary me-2 my-1 w-100" @click="requestDownscale(type, rank)">
                        <i class="fas fa-down-long btn-icon-left"></i>{{ renderDownscaleLabelFor(type, rank) }}
                    </button>

                    <button v-if="config[type][rank].upscale"
                            :disabled="!canDoPossibleTransaction('upscale', type, rank)"
                            class="btn btn-secondary me-2 my-1 w-100" @click="requestUpscale(type, rank)">
                        <i class="fas fa-up-long btn-icon-left"></i>{{ renderUpscaleLabelFor(type, rank) }}
                    </button>

                    <button v-if="config[type][rank].tokens"
                            :disabled="!canDoPossibleTransaction('token', type, rank)"
                            class="btn btn-secondary me-2 my-1 w-100" @click="startConvertToTokens(type, rank)">
                        <i class="fas fa-medal btn-icon-left"></i>{{ renderTokenizeLabelFor(type, rank) }}
                    </button>
                </div>


            </div>
        </div>

        <!-- Big screens -->
        <table class="d-none d-xl-table table table-dark table-hover table-responsive">
            <thead>
            <tr>
                <th scope="col">Rarity</th>
                <th class="text-end" scope="col">Owned</th>
                <th class="text-end" scope="col">Buy Price</th>
                <th scope="col"></th>
                <th class="text-end" scope="col">Sell Price</th>
                <th scope="col"></th>
                <th class="text-center" scope="col">Convert</th><!-- Upscale/Downscale -->
                <th class="text-center" scope="col">Tokenize</th><!-- Upscale/Downscale -->
            </tr>
            </thead>
            <tbody>
            <tr v-for="rank in ranks" class="align-middle">
                <th scope="row">{{ capital(rank) }}</th>
                <td class="text-end">{{ renderOwnedFor(type, rank) }}</td>
                <td class="text-end">{{ renderBuyPriceFor(type, rank) }}</td>
                <td>
                    <button :disabled="!canDoPossibleTransaction('buy', type, rank)" class="btn btn-secondary my-1"
                            @click="startBuySalvage(type, rank)">
                        <i class="fas fa-coins btn-icon-left"></i>Buy
                    </button>
                </td>
                <td class="text-end">{{ renderSellPriceFor(type, rank) }}</td>
                <td>
                    <button :disabled="!canDoPossibleTransaction('sell', type, rank)" class="btn btn-secondary my-1"
                            @click="startSellSalvage(type, rank)">
                        <i class="fas fa-coins btn-icon-left"></i>Sell
                    </button>
                </td>
                <td>
                    <template v-if="type in config && rank in config[type]">
                        <button v-if="config[type][rank].upscale"
                                :disabled="!canDoPossibleTransaction('upscale', type, rank)"
                                class="btn btn-secondary my-1 d-block w-100" @click="requestUpscale(type, rank)">
                            <i class="fas fa-up-long btn-icon-left"></i>{{ renderUpscaleLabelFor(type, rank) }}
                        </button>
                        <button v-if="config[type][rank].downscale"
                                :disabled="!canDoPossibleTransaction('downscale', type, rank)"
                                class="btn btn-secondary my-1 d-block w-100" @click="requestDownscale(type, rank)">
                            <i class="fas fa-down-long btn-icon-left"></i>{{ renderDownscaleLabelFor(type, rank) }}
                        </button>
                    </template>
                </td>
                <td>
                    <button v-if="config[type][rank].tokens"
                            :disabled="!canDoPossibleTransaction('token', type, rank)"
                            class="btn btn-secondary my-1 d-block w-100" @click="startConvertToTokens(type, rank)">
                        <i class="fas fa-medal btn-icon-left"></i>{{ renderTokenizeLabelFor(type, rank) }}
                    </button>
                </td>
            </tr>
            </tbody>
        </table>

    </div>

    <modal-confirmation ref="transactionModal" :title="titleForTransaction()"
                        no-label="Cancel" yes-label="Confirm"
                        @yes="acceptTransaction"
    >
        <p>For each: {{ transactionConfig?.cost }} x
            <span v-if="transactionType == 'buy'"> {{ lex('money') }}</span>
            <span v-else>{{ capital(transactionSalvageRank) }} {{ capital(transactionSalvageType) }}</span>
        </p>

        <p>You get: {{ transactionConfig?.quantity }} x {{ transactionConfig?.what }}</p>

        <hr/>

        <p>You currently have:
            <span v-if="transactionType == 'buy'">{{ money }} {{ lex('money') }}</span>
            <span v-else>{{ renderOwnedFor(transactionSalvageType, transactionSalvageRank) }}
                {{ capital(transactionSalvageRank) }} {{ capital(transactionSalvageType) }}</span>
        </p>

        <div class="mb-2">
            <label class="form-label" for="transactionAmount">How many times do you want to do this?</label>
            <input id="transactionAmount" v-model="transactionQuantity" class="form-control"
                   max="5000" min="1" type="number" @input="getQuoteForTransaction">
        </div>
        <callout v-if="transactionType == 'buy'" class="my-2">
            The buying price may increase for every transaction.
            The quote below accounts for this and if the price ends up higher
            (because of another player using the market) the transaction will abort.
        </callout>
        <callout v-if="transactionType == 'sell'" class="my-2">
            The selling price may decrease for every transaction.
            The quote below accounts for this and if the price ends up lower
            (because of another player using the market) the transaction will abort.
        </callout>

        <div v-if="!transactionQuote">Updating quote..</div>
        <callout v-else-if="transactionQuote.error" type="danger">{{ transactionQuote.error }}</callout>
        <callout v-else type="info">
            Pay: {{ transactionQuote.cost || 'Updating Quote..' }}
            Pay: {{ transactionQuote.what || 'Updating Quote..' }}
        </callout>
    </modal-confirmation>

    <modal-message ref="transactionResultModal" :title="transactionResultTitle">
        <p>{{ transactionResultText }}</p>
    </modal-message>
</template>

<style scoped>

</style>
