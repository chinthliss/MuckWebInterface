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

onMounted(() => {
    channel.send('bootSalvageDisplay');
})
</script>

<template>
    <table class="table table-dark table-hover table-striped table-responsive w-auto">
        <thead>
        <tr>
            <th scope="col">Salvage Type</th>
            <th scope="col" class="text-end">Skill</th>
            <th v-for="rank in ranks" scope="col" class="text-end">{{ capital(rank) }}</th>
        </tr>
        </thead>
        <tbody>
        <tr v-for="type in types">
            <td>{{ capital(type) }}</td>
            <td class="text-end">{{ skills[type] || 0 }}</td>
            <td v-for="rank in ranks" class="text-end">{{
                    (type in owned && rank in owned[type] ? owned[type][rank] : 0).toLocaleString()
                }}
            </td>
        </tr>
        </tbody>
    </table>
</template>

<style scoped>

</style>
