<script setup lang="ts">

import {ref, Ref} from "vue";

type Perk = {
    name: string,
    description: string,
    costs: number
    tags?: string[],
    excludes?: string[]
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
    perks.value.push(data);
    perksToLoadRemaining.value--;
});

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
        <div class="position-sticky top-0 bg-primary text-dark">Info bar</div>

        <div v-for="perk in perks" class="card mt-2">
            <div class="card-body">
                <div class="float-end">Cost</div>
                <h5 class="text-primary card-title">{{perk.name}}</h5>
                <p class="card-text">{{perk.description}}</p>
            </div>
            <div v-if="perk.excludes" class="card-footer text-body-secondary">
                Excludes: {{ perk.excludes.join(', ') }}
            </div>
        </div>
    </div>
</template>

<style scoped>
</style>
