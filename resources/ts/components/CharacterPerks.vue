<script setup lang="ts">

import {ref, Ref} from "vue";

type Perk = {
    name: string,
    description: string
}

const perks: Ref<Perk[]> = ref([]);
const perksToLoad: Ref<number | null> = ref(null);
const perksToLoadRemaining: Ref<number> = ref(1); // Starting at 1 to cover initial loading

const channel = mwiWebsocket.channel('character');

</script>

<template>
    <p>Content Pending</p>
    <p>Toggle - All / Owned / Unowned</p>
    <p>Toggle - Hide excluded perks?</p>

    <div v-if="perksToLoadRemaining">Loading..</div>
    <div v-else>
        <!-- Floating info bar -->
        <div class="position-sticky top-0 bg-primary text-dark">Info bar</div>

    </div>
</template>

<style scoped>
</style>
