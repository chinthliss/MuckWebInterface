<script lang="ts" setup>

import {onMounted, Ref, ref} from "vue";
import {capital} from "../formatting";
import ModalConfirmation from "./ModalConfirmation.vue";

type SalvageLimits = {
    [type: string]: number
}

const ranks: Ref<string[]> = ref([]);
const limits: Ref<SalvageLimits> = ref({});
const channel = mwiWebsocket.channel('gear');
const autoRankBeingChanged: Ref<string | null> = ref(null);
const autoLimitBeingChanged: Ref<number | null> = ref(null);
const autoChangeModal: Ref<InstanceType<typeof ModalConfirmation> | null> = ref(null);

const startAutoPurchaseChange = (rank: string) => {
    autoRankBeingChanged.value = rank;
    autoLimitBeingChanged.value = limits.value[rank] ?? 0;
    if (autoChangeModal.value) autoChangeModal.value.show();
}

const saveAutoPurchaseChange = () => {
    // Send to the muck, it'll confirm the change and trigger a refresh
    channel.send('setSalvageAutoPurchaseLimit', {rank: autoRankBeingChanged.value, limit: autoLimitBeingChanged.value})
}

channel.on('salvageAutoPurchaseLimits', (response: SalvageLimits) => {
    limits.value = response ?? {};
})

channel.on('bootSalvageAutoPurchaseConfig', (response: {
    ranks: string[]
}) => {
    ranks.value = response.ranks ?? [];
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
            <td>{{ rank in limits ? limits[rank].toLocaleString() : "Disabled" }}</td>
            <td>
                <button class="btn btn-secondary ms-2" @click="startAutoPurchaseChange(rank)">
                    <i class="fas fa-wrench btn-icon-left"></i>Change
                </button>
            </td>
        </tr>
        </tbody>
    </table>

    <modal-confirmation ref="autoChangeModal" :title="'Set limit for ' + capital(autoRankBeingChanged)"
                        no-label="Cancel" yes-label="Save Changes"
                        @yes="saveAutoPurchaseChange"
    >
        <div class="mb-2">
            <p>Enter a new value below. Set it to 0 if you want to disable auto-purchasing.</p>
            <label class="form-label" for="autoLimitBeingChanged">New limit:</label>
            <input id="autoLimitBeingChanged" v-model="autoLimitBeingChanged" class="form-control" type="number">
        </div>
    </modal-confirmation>
</template>

<style scoped>

</style>
