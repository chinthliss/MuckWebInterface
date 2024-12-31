<script setup lang="ts">
import {Ref, ref} from 'vue';
import {ansiToHtml, arrayToStringWithNewlines, carbonToString} from "../formatting";
import Spinner from "./Spinner.vue";
import type {Character} from "../defs";

import DataTable from 'datatables.net-vue3';
import DataTablesLib, {Config as DataTableOptions} from 'datatables.net-bs5';

DataTable.use(DataTablesLib);

const props = defineProps<{
    characterIn: Character,
    avatarUrl: string
}>();

type Badge = { name: string, description: string[], awarded: string }

type CharacterProfile = {
    name?: string,
    alias?: string,
    level?: number,
    sex: string | null,
    species: string | null,
    height: string | null,
    shortDescription: string | null,
    faction: string | null,
    group: string | null,
    class: string | null,
    role: string | null,
    whatIs: string | null,
    views: any[] | null,
    custom?: any[] | null,
    equipment?: any[] | null,
    badges?: Badge[] | null,
    birthday?: string,
    mailTotal?: number
    mailUnread?: number,
    laston?: string
}

const profile: Ref<CharacterProfile> = ref({
    name: props.characterIn?.name,
    level: props.characterIn?.level,
    sex: null,
    species: null,
    height: null,
    shortDescription: null,
    faction: null,
    group: null,
    class: null,
    role: null,
    whatIs: null,
    views: null,
    custom: null,
    equipment: null,
    badges: null
} as CharacterProfile);

const customTableOptions: DataTableOptions = {
    info: false,
    paging: false,
    language: {
        emptyTable: "No extra information configured."
    },
    columns: [
        {data: 'field'},
        {data: 'value'}
    ]
};

const viewTableOptions: DataTableOptions = {
    info: false,
    paging: false,
    language: {
        emptyTable: "No views configured"
    },
    columns: [
        {data: 'view'},
        {data: 'content'}
    ]
};

const equipmentTableOptions: DataTableOptions = {
    info: false,
    paging: false,
    language: {
        emptyTable: "Nothing equipped."
    },
    columns: [
        {data: 'name'},
        {data: 'description'}
    ]
};

const badgeTableOptions: DataTableOptions = {
    info: false,
    paging: false,
    language: {
        emptyTable: "No badges."
    },
    columns: [
        {data: 'name'},
        {data: 'description', render: arrayToStringWithNewlines},
        {data: 'awarded', render: carbonToString}
    ]
};


const channel = mwiWebsocket.channel('character');
const profileLoading: Ref<boolean> = ref(true);

channel.on('connected', () => {
    channel.send('getCharacterProfile', props.characterIn.dbref);
});

channel.on('profile', (data: CharacterProfile) => {
    profile.value = data;
    profileLoading.value = false;
});

channel.on('views', (data) => {
    profile.value.views = data;
});

channel.on('customFields', (data) => {
    profile.value.custom = data;
});

channel.on('equipment', (data) => {
    profile.value.equipment = data;
});

channel.on('badges', (_data) => {
    profile.value.badges = [];
});

channel.on('badge', (data) => {
    if (profile.value.badges) profile.value.badges.push(data)
});

</script>

<template>
    <div class="container">

        <h1>{{ profile.name }} <span v-if="profile.alias">, aka {{ profile.alias }}</span></h1>

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
                <template v-else>

                    <!-- Gender and Species  -->
                    <div class="d-flex">
                        <div>
                            <div class="label">Gender</div>
                            <div class="value">{{ profile.sex || '--' }}</div>
                        </div>
                        <div class="ms-4">
                            <div class="label">Species</div>
                            <div class="value">{{ profile.species || '--' }}</div>
                        </div>
                    </div>

                    <!-- Class and Role -->
                    <div class="mt-2 d-flex">
                        <div>
                            <div class="label">Class</div>
                            <div class="value">{{ profile.class || '--' }}</div>
                        </div>
                        <div class="ms-4">
                            <div class="label">Role</div>
                            <div class="value">{{ profile.role || '--' }}</div>
                        </div>
                    </div>

                    <!-- Level and Height -->
                    <div class="mt-2 d-flex">
                        <div>
                            <div class="label">Level</div>
                            <div class="value">{{ profile.level || '--' }}</div>
                        </div>
                        <div class="ms-4">
                            <div class="label">Height</div>
                            <div class="value">{{ profile.height || '--' }}</div>
                        </div>
                    </div>

                    <!-- Faction and Group -->
                    <div class="mt-2 d-flex">
                        <div>
                            <div class="label">Faction</div>
                            <div class="value">{{ profile.faction || '--' }}</div>
                        </div>
                        <div class="ms-4">
                            <div class="label">Group</div>
                            <div class="value">{{ profile.group || '--' }}</div>
                        </div>
                    </div>

                    <!-- Mail status and Laston  -->
                    <div class="mt-2 d-flex">
                        <div>
                            <div class="label">Mail (Unread)</div>
                            <div class="value">
                                {{ profile.mailTotal ? profile.mailTotal + ' (' + profile.mailUnread + ')' : '--' }}
                            </div>
                        </div>
                        <div class="ms-4">
                            <div class="label">Last On</div>
                            <div class="value">{{ profile.laston || '--' }}</div>
                        </div>
                    </div>

                    <!-- Birthday -->
                    <div class="mt-2">
                        <div class="label">Birthday <span class="text-muted">(+birthday)</span></div>
                        <div class="value">{{ profile.birthday || '--' }}</div>
                    </div>

                    <!-- Short Description -->
                    <div class="mt-2">
                        <div class="label">Short Description <span class="text-muted">(+glance)</span></div>
                        <div class="value"
                             v-html="profile.shortDescription ? ansiToHtml(profile.shortDescription) : '--'"
                        ></div>
                    </div>

                    <!-- WhatIs -->
                    <div class="mt-2">
                        <div class="label">What Is <span class="text-muted">(wi)</span></div>
                        <div v-html="profile.whatIs || '--'" class="value"></div>
                    </div>
                </template>
            </div>
        </div>

        <template v-if="!profileLoading">
            <!-- Custom -->
            <h3 class="mt-2">Custom Information <span class="text-muted">(profile)</span></h3>
            <DataTable class="table table-dark table-hover table-striped"
                       :options="customTableOptions" :data="profile.custom"
            >
                <thead>
                <tr>
                    <th>Field</th>
                    <th>Value</th>
                </tr>
                </thead>

            </DataTable>

            <!-- Views -->
            <h3 class="mt-2">Views <span class="text-muted">(+view)</span></h3>
            <DataTable class="table table-dark table-hover table-striped"
                       :options="viewTableOptions" :data="profile.views"
            >
                <thead>
                <tr>
                    <th>View</th>
                    <th>Content</th>
                </tr>
                </thead>
            </DataTable>

            <!-- Equipment -->
            <h3 class="mt-2">Equipment <span class="text-muted">(+equip)</span></h3>
            <DataTable class="table table-dark table-hover table-striped"
                       :options="equipmentTableOptions" :data="profile.equipment"
            >
                <thead>
                <tr>
                    <th>Name</th>
                    <th>Description</th>
                </tr>
                </thead>
            </DataTable>

            <!-- Badges -->
            <h3 class="mt-2">Badges <span class="text-muted">(+badge)</span></h3>
            <DataTable class="table table-dark table-hover table-striped"
                       :options="badgeTableOptions" :data="profile.badges"
            >
                <thead>
                <tr>
                    <th>Badge</th>
                    <th>Description</th>
                    <th>Awarded</th>
                </tr>
                </thead>
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
