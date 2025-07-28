<script lang="ts" setup>

import {onMounted, Ref, ref} from "vue";
import {capital} from "../formatting";

type SalvageOwned = {
    [type: string]: {
        [rank: string]: number
    }
}

type SalvageSkills = {
    [type: string]: number
}

const types: Ref<string[]> = ref([]);
const ranks: Ref<string[]> = ref([]);
const owned: Ref<SalvageOwned> = ref({})
const skills: Ref<SalvageSkills> = ref({})

const channel = mwiWebsocket.channel('gear');

channel.on('bootSalvageDisplay', (response: {
    types: string[],
    ranks: string[]
    owned: SalvageOwned,
    skills: SalvageSkills
}) => {
    types.value = response.types;
    ranks.value = response.ranks;
    owned.value = response.owned;
    skills.value = response.skills;
})

const renderOwnedFor = (type: string, rank: string): string => {
    if (type in owned.value && rank in owned.value[type])
        return owned.value[type][rank].toLocaleString();
    else
        return '0';
}

onMounted(() => {
    channel.send('bootSalvageDisplay');
})
</script>

<template>

    <!-- Vertical table for smaller displays -->
    <table class="d-xl-none table table-dark table-hover table-striped">
        <tbody>
        <template v-for="type in types">
            <tr><td colspan="2" class="fw-bold text-center">{{ capital(type) }}</td></tr>
            <tr><td>Crafting  Skill</td><td class="text-end">{{ skills[type] || 0 }}</td></tr>
            <tr v-for="rank in ranks">
                <td>{{ capital(rank) }}</td>
                <td class="text-end">{{ renderOwnedFor(type, rank) }}</td>
            </tr>
        </template>
        </tbody>
    </table>

    <!-- Full table for large displays -->
    <table class="d-none d-xl-table table table-dark table-hover table-striped">
        <thead>
        <tr>
            <th scope="col">Salvage Type</th>
            <th scope="col" class="text-end">Skill</th>
            <th v-for="rank in ranks" scope="col" class="text-end">{{ capital(rank) }}</th>
        </tr>
        </thead>
        <tbody>
        <tr v-for="type in types">
            <th scope="row">{{ capital(type) }}</th>
            <td class="text-end">{{ skills[type] || 0 }}</td>
            <td v-for="rank in ranks" class="text-end">{{ renderOwnedFor(type, rank) }}</td>
        </tr>
        </tbody>
    </table>
</template>

<style scoped>

</style>
