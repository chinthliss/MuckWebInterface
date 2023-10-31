<script setup lang="ts">

import {ref, Ref} from "vue";

type Perk = {
    name: string,
    description: string,
    cost: number
    tags?: string[],
    excludes?: string[],
    excluded?: boolean,
    owned?: boolean
}

const perks: Ref<Perk[]> = ref([]);
const perksToLoad: Ref<number | null> = ref(null);
const perksToLoadRemaining: Ref<number> = ref(1); // Starting at 1 to cover initial loading

const channel = mwiWebsocket.channel('character');

channel.on('perksCatalogue', (data: number) => {
    perks.value = [];
    perksToLoadRemaining.value = data;
    perksToLoad.value = data;
});

channel.on('perk', (data: Perk) => {
    data.excluded = false;
    perks.value.push(data);
    perksToLoadRemaining.value--;
    if (data.owned) recalculateExclusions();
});

const recalculateExclusions = () => {
    const excluded = [];
    // Pass 1, calculation exclusions
    for (const perk of perks.value) {
        if (perk.owned && perk.excludes) excluded.concat(perk.excludes);
    }
    // Pass 2, flag excluded
    for (const perk of perks.value) {
        perk.excluded = (excluded.indexOf(perk.name) !== -1);
    }
}

// Send requests for data
channel.send('getPerksCatalogue');

</script>

<template>
    <p>Content Pending</p>
    <p>Toggle - All / Owned / Unowned</p>
    <p>Toggle - Hide excluded perks?</p>

    <div v-if="perksToLoadRemaining">Loading..</div>
    <div v-else>
        <!-- Floating info bar -->
        <div class="position-sticky top-0 bg-primary text-dark p-1 rounded-1 z-1">
            <div>Points: ???</div>
            <div>Vanity Points: ???</div>
        </div>

        <p>Perks that have the 'vanity' tag will deduct from your vanity points first, then use regular points to make
            up the difference if required.</p>

        <div v-for="perk in perks" class="card mt-2"
             v-bind:class="{ 'border-primary-subtle': !perk.owned, 'border-dark-subtle': perk.excluded }"
        >
            <div class="card-body">
                <div class="float-end">
                    <span v-if="perk.cost">Cost: {{ perk.cost }}</span>
                    <span v-else>Free</span>
                    <button class="btn btn-primary ms-2" v-if="!perk.excluded && !perk.owned">Buy</button>
                </div>
                <h5 class="card-title" v-bind:class="{ 'text-primary': perk.owned, 'text-muted': perk.excluded }"
                >{{ perk.name }}
                    <span v-if="perk.owned" class="badge rounded-pill text-bg-primary">Owned</span>
                    <span v-if="perk.excluded" class="badge rounded-pill text-bg-warning">Excluded</span>
                </h5>

                <p class="card-text" v-bind:class="{ 'text-muted': perk.excluded }">{{ perk.description }}</p>
            </div>

            <div class="card-footer text-body-secondary d-flex"
                 v-bind:class="{ 'border-primary-subtle': !perk.owned, 'border-dark-subtle': perk.excluded }"
            >
                <div class="flex-grow-1">
                    <template v-if="perk.excludes">Excludes: {{ perk.excludes.join(', ') }}</template>
                </div>
                <div>
                    <template v-if="!perk.tags">No tags</template>
                    <span v-for="tag in perk.tags" class="badge rounded-pill text-bg-secondary ms-1">{{ tag }}</span>

                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
</style>
