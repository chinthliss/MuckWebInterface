<!-- Usually hosted as the content part of an OffCanvas element -->
<template>

    <div class="offcanvas offcanvas-end" tabindex="-1" ref="self"
         id="site-character-select" aria-labelledby="site-character-select-header">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title" id="site-character-select-header">Character Select</h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">

            <div class="row text-center">
                <div class="col alert alert-danger" v-if="characters.length - slots > 0">
                    You are over your character limit by {{ characters.length - slots }}. This may cause characters to
                    become
                    unavailable.
                </div>
            </div>

            <div v-if="initialLoading">Loading..</div>
            <div v-else class="d-flex flex-column align-items-center">
                <character-card v-for="character in characters" :character="character"
                                @click="selectCharacter(character)" class="mt-2"></character-card>
                <div v-for="i in freeSlots" v-bind:key="i"
                     class="card empty-character-card border-primary mr-2 mt-2">
                    <div class="card-body h-100">
                        <a class="btn btn-primary w-100" :href="links.createCharacter"><span class="d-flow"><i
                            class="fas fa-plus btn-icon-left"></i>New character</span></a>
                    </div>
                </div>

            </div>


            <div v-if="cost" class="row text-center mt-2">
                <div class="col">
                    <a class="btn btn-primary btn-with-img-icon" href="#" @click="verifyBuyCharacterSlot">
                        <span class="btn-icon-accountcurrency btn-icon-left"></span>
                        Buy Character Slot
                        <span class="btn-second-line">{{ cost }} {{ lex('accountcurrency') }}</span>
                    </a>
                </div>
            </div>


        </div>
    </div>

    <ModalConfirmation id="confirm-buy-character-slot" @yes="buyCharacterSlot"
                       yes-label="Buy Character" no-label="Cancel">
        Are you sure you wish to buy a new character slot for {{ cost }} {{ lex('accountcurrency') }}?
    </ModalConfirmation>

</template>

<script setup>
import {ref, onMounted, computed} from 'vue';
import CharacterCard from './CharacterCard.vue';
import {lex} from "../siteutils";
import ModalConfirmation from "./ModalConfirmation.vue";

const props = defineProps({
    links: {type: Object, required: true}
});

const characters = ref([]);
const slots = ref(0);
const cost = ref(null);
const self = ref();
let initialLoading = ref(true);

const freeSlots = computed(() => {
    return Math.max(slots.value - characters.value.length, 0);
});

const refreshCharacterList = () => {
    // console.log("(site) Booting character select from " + props.links.getState);
    axios.get(props.links.getState)
        .then(response => {
            characters.value = response.data.characters;
            slots.value = response.data.characterSlotCount;
            cost.value = response.data.characterSlotCost;
        })
        .catch(error => {
            console.log("An error occurred whilst refreshing the character list: ", error.message || error);
        })
        .finally(() => {
            initialLoading.value = false;
        });
};

onMounted(() => {
    self.value.addEventListener('show.bs.offcanvas', refreshCharacterList);
});

const selectCharacter = (character) => {
    // console.log("(site) Character selected: ", character);
    axios.post(props.links.setCharacter, {'dbref': character.dbref})
        .then(response => {
            if (response?.data?.result === 'OK') {
                if (response.data?.redirect)
                    location = response.data.redirect;
                else
                    location.reload();
            }
        })
        .catch(error => {
            console.log("An error occurred whilst selecting a character: ", error.message || error);
        });
};

const verifyBuyCharacterSlot = () => {
    const modal = new bootstrap.Modal(document.getElementById('confirm-buy-character-slot'));
    modal.show();
}
const buyCharacterSlot = () => {
    axios.post(props.links.buySlot, {})
        .then(response => {
            if (response?.data?.result === 'OK') {
                slots.value = response.data.characterSlotCount;
                cost.value = response.data.characterSlotCost;
            } else {
                console.log("The muck declined the request: ", response.data.error);
            }
        })
        .catch(error => {
            console.log("An error occurred whilst buying a character slot: ", error.message || error);
        })
        .finally(() => {

        });
}


defineExpose({refreshCharacterList});
</script>

<style scoped>
.empty-character-card {
    display: inline-block;
    border-style: dashed;
    width: 240px;
    height: 100px;
}

.empty-character-card .btn {
    margin-top: 8px;
}
</style>
