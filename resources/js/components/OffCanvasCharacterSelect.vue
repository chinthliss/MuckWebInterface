<!-- Usually hosted as the content part of an OffCanvas element -->
<template>

    <div class="offcanvas offcanvas-end" tabindex="-1" ref="self"
         id="site-character-select" aria-labelledby="site-character-select-header">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title" id="site-character-select-header">Character Select</h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">
            <div v-if="initialLoading">Loading..</div>
            <div v-else-if="!characters.length">No characters.</div>
            <div v-else class="d-flex flex-column align-items-center">
                <character-card v-for="character in characters" :character="character"
                                @click="selectCharacter(character)"></character-card>
            </div>
        </div>
    </div>


</template>

<script setup>
//TODO: Pass in references to get characters location and set character location instead of hard coding
import {ref, onMounted} from 'vue';
import CharacterCard from './CharacterCard.vue';

const props = defineProps({
    links: {type: Object, required: true}
});

const characters = ref([]);
const self = ref();
let initialLoading = ref(true);

const refreshCharacterList = () => {
    console.log("(site) Refreshing character list from " + props.links.getCharacter);
    axios.get(props.links.getCharacters)
        .then(response => {
            characters.value = response.data;
            initialLoading.value = false;
        })
        .catch(error => {
            console.log("An error occurred whilst refreshing the character list: ", error.message || error);
            initialLoading.value = false;
        });

};

onMounted(() => {
    self.value.addEventListener('show.bs.offcanvas', refreshCharacterList);
});

const selectCharacter = (character) => {
    console.log("Character selected: ", character);
};


defineExpose({refreshCharacterList});
</script>

<style scoped>

</style>
