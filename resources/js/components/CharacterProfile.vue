<script setup>
import {ref, onMounted} from "vue";

const props = defineProps({
    /** @type {Character} */
    characterIn: {Type: Object, required: true},
    controls: {Type: Boolean, required: false, default: false},
    avatarUrl: {Type: String, required: true}
});

const profile = ref({
    name: props.characterIn.name,
    level: props.characterIn.level,
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
});

const websocket = /** @type {MwiWebsocket} */ (window.MwiWebsocket);
const channel = websocket.channel('character');
const profileLoading = ref(true);

channel.on('connected', (data) => {
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
                <div v-if="profileLoading" class="text-center">
                    <span class="spinner-border text-primary me-2" role="status" aria-hidden="true"></span>
                    <div>Loading..</div>
                </div>

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
                            <div class="value">{{ profile.level  || '--' }}</div>
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
    </div>

</template>

<style scoped>

</style>
