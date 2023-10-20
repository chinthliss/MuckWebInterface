<script setup lang="ts">
import {Ref, ref} from 'vue';
import {carbonToString} from "../formatting";
import Spinner from "./Spinner.vue";
import type {Character} from "../defs";
import DataTable from 'primevue/datatable';
import Column from "primevue/column";

const props = defineProps<{
    characterIn: Character,
    avatarUrl: string
}>();

type Badge = { name: string, description: string[], awarded: string }

type characterProfile = {
    name?: string
    level?: number
    sex?: string
    species?: string
    height?: string
    shortDescription?: string
    faction?: string
    group?: string
    role?: string
    whatIs?: string
    views?: any[] | null
    pinfo?: any[] | null
    equipment?: any[] | null
    badges?: Badge[] | null
}

const profile: Ref<characterProfile> = ref({
    name: props.characterIn?.name,
    level: props.characterIn?.level,
    sex: null,
    species: null,
    height: null,
    shortDescription: null,
    faction: null,
    group: null,
    role: null,
    whatIs: null,
    views: null,
    pinfo: null,
    equipment: null,
    badges: null
} as characterProfile);

const channel = mwiWebsocket.channel('character');
const profileLoading: Ref<boolean> = ref(true);

channel.on('connected', () => {
    channel.send('getCharacterProfile', props.characterIn.dbref);
});

channel.on('characterProfileCore', (data) => {
    profile.value.sex = data.sex;
    profile.value.species = data.species;
    profile.value.height = data.height;
    profile.value.shortDescription = data.shortDescription;
    profile.value.faction = data.faction;
    profile.value.group = data.group;
    profile.value.role = data.role;
    profile.value.whatIs = data.whatIs;
    profileLoading.value = false;
});

channel.on('characterProfileViews', (data) => {
    profile.value.views = data;
});

channel.on('characterProfilePinfo', (data) => {
    profile.value.pinfo = data;
});

channel.on('characterProfileEquipment', (data) => {
    profile.value.equipment = data;
});


channel.on('characterProfileBadges', (data) => {
    profile.value.badges = data;
});


</script>

<template>
    <div class="container">

        <h1>{{ profile.name }}</h1>

        <div class="d-flex flex-column flex-xl-row">
            <div class="mx-auto">
                <div id="AvatarContainer" class="border border-primary">
                    <img v-if="avatarUrl" :src="avatarUrl" alt="Character Avatar" id="AvatarImg">
                    <div v-if="!avatarUrl" class="mt-4 text-center">
                        Avatars are disabled
                    </div>
                </div>
            </div>

            <div id="ProfileContainer" class="flex-grow-1 mt-2 mt-xl-0 ms-xl-4">
                <Spinner v-if="profileLoading"/>
                <div v-else>
                    <!-- Gender, Species and Height -->
                    <div class="d-flex">
                        <div>
                            <div class="label">Height</div>
                            <div class="value">{{ profile.height || '--' }}</div>
                        </div>
                        <div class="ms-4">
                            <div class="label">Gender</div>
                            <div class="value">{{ profile.sex || '--' }}</div>
                        </div>
                        <div class="flex-grow-1 ms-4">
                            <div class="label">Species</div>
                            <div class="value">{{ profile.species || '--' }}</div>
                        </div>
                    </div>

                    <!-- Level and Role -->
                    <div class="mt-2 d-flex">
                        <div>
                            <div class="label">Level</div>
                            <div class="value">{{ profile.level || '--' }}</div>
                        </div>
                        <div class="flex-grow-1 ms-4">
                            <div class="label">Role</div>
                            <div class="value">{{ profile.role || '--' }}</div>
                        </div>
                    </div>

                    <!-- Faction and Group -->
                    <div class="mt-2 d-flex">
                        <div>
                            <div class="label">Faction</div>
                            <div class="value">{{ profile.faction || '--' }}</div>
                        </div>
                        <div class="flex-grow-1 ms-4">
                            <div class="label">Group</div>
                            <div class="value">{{ profile.group || '--' }}</div>
                        </div>
                    </div>

                    <!-- Short Description -->
                    <div class="mt-2">
                        <div class="label">Short Description <span class="text-muted">(+glance)</span></div>
                        <div class="value">{{ profile.shortDescription || '--' }}</div>
                    </div>

                    <!-- WhatIs -->
                    <div class="mt-2">
                        <div class="label">What Is <span class="text-muted">(wi)</span></div>
                        <div v-html="profile.whatIs || '--'" class="value"></div>
                    </div>
                </div>
            </div>
        </div>

        <template v-if="!profileLoading">
            <!-- Views -->
            <h3 class="mt-2">Views <span class="text-muted">(+view)</span></h3>
            <DataTable :value="profile.views" stripedRows>
                <template #empty>No views configured.</template>
                <Column header="View" field="view" sortable></Column>
                <Column header="Content" field="content"></Column>
            </DataTable>

            <!-- Pinfo -->
            <h3 class="mt-2">Custom Information <span class="text-muted">(+finger)</span></h3>
            <DataTable :value="profile.pinfo" stripedRows>
                <template #empty>No extra information configured.</template>
                <Column header="Field" field="field" sortable></Column>
                <Column header="Value" field="value"></Column>
            </DataTable>

            <!-- Equipment -->
            <h3 class="mt-2">Equipment <span class="text-muted">(+equip)</span></h3>
            <DataTable :value="profile.equipment" striedRows>
                <template #empty>Nothing equipped.</template>
                <Column header="Name" field="name" sortable></Column>
                <Column header="Description" field="description"></Column>
            </DataTable>

            <!-- Badges -->
            <h3 class="mt-2">Badges <span class="text-muted">(+badge)</span></h3>
            <DataTable :value="profile.badges" stripedRows>
                <template #empty>No badges.</template>
                <Column header="Badge" field="name" sortable></Column>
                <Column header="Description" field="description">
                    <template #body="{ data }">
                        {{ (data as Badge).description.join('\n') }}
                    </template>
                </Column>
                <Column header="Awarded" field="awarded" sortable>
                    <template #body="{ data }">
                        {{ carbonToString((data as Badge).awarded) }}
                    </template>
                </Column>
            </DataTable>
        </template>

    </div>

</template>

<style scoped lang="scss">
@use 'resources/sass/variables' as *;

.label {
    font-weight: 600;
    color: $primary;
}

</style>
