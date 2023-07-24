<script setup>
import {ref, onMounted} from "vue";

const props = defineProps({
    /** @type {Character} */
    characterIn: {Type: Object, required: true},
    controls: {Type: Boolean, required: false, default: false},
    avatarUrl: {Type: String, required: true},
    avatarWidth: {Type: Number, required: true},
    avatarHeight: {Type: Number, required: true}
});

const character = ref({
    name: props.characterIn.name,
    sex: null,
    species: null,
    height: null,
    shortDescription: null,
    faction: null,
    group: null,
    role: null,
    whatIs: null,
    equipment: null
});

const websocket = /** @type {MwiWebsocket} */ (window.MwiWebsocket);
const channel = websocket.channel('character');

const processWebsocketConnectionChange = (status) => {
    if (status === 'connected') channel.send('getCharacterProfile', props.characterIn.dbref);
}

onMounted(() => {
    websocket.onConnectionStateChange(processWebsocketConnectionChange);
});

channel.on('characterProfile', (data) => {
    character.value.sex = data.sex;
    character.value.species = data.species;
    character.value.height = data.height;
    character.value.shortDescription = data.shortDescription;
    character.value.faction = data.faction;
    character.value.group = data.group;
    character.value.role = data.role;
    character.value.whatIs = data.whatIs;
    character.value.equipment = data.equipment;
});

</script>

<template>
    <div class="container">

        <h1>Character Profile</h1>

    </div>

</template>

<style scoped>

</style>
