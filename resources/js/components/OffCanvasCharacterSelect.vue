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
            <div v-else>
                <div v-for="character in characters">Character</div>
            </div>
        </div>
    </div>


</template>

<script setup>
import {ref, onMounted} from 'vue';

const characters = ref([]);
const self = ref();
let initialLoading = ref(false);

const refreshCharacterList = () => {
    console.log("(site) Refreshing character list");
    axios.get('/api/characters')
        .then(response => {
            console.log("Loaded character list:", response.data);
        })
        .catch(error => {
            console.log("An error occurred whilst refreshing the character list: ", error.message || error);
        });
    initialLoading.value = false;
}

onMounted(() => {
    self.value.addEventListener('show.bs.offcanvas', refreshCharacterList);
});

defineExpose({refreshCharacterList});
</script>

<style scoped>

</style>
