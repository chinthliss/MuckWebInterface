<template>
    <div class="container">

        <h1>Character Generation</h1>

        <form action="" method="POST" @submit="hideDuringSubmit">
            <input type="hidden" name="_token" :value="csrf()">

            <!-- Gender -->
            <h2>Gender</h2>
            <div class="row">
                <div class="col-12 col-md-6">
                    <p>This is your starting biological gender and may change rapidly.</p>
                    <p>See some of the perks below if you wish to prevent or reduce the chance of this.</p>
                </div>
                <div class="col-12 col-md-6">
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="gender" value="male" id="gender-male"
                               v-model="chosenGender">
                        <label class="form-check-label" for="gender-male">Male</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="gender" value="female" id="gender-female"
                               v-model="chosenGender">
                        <label class="form-check-label" for="gender-female">Female</label>
                    </div>
                    <div class="text-danger" role="alert">
                        <p v-for="error in errors.gender">{{ error }}</p>
                    </div>
                </div>
            </div>

            <!-- Birthday -->
            <h2>Birthday</h2>
            <div class="row">
                <div class="col-12 col-md-6">
                    <p>Your birthday can be between 1940 and present day.</p>
                    <p>Regardless of what date you were born, due to nanites accelerating development the minimum age of
                        a character is 18.</p>
                </div>
                <div class="col-12 col-md-auto">
                    <div>
                        <label class="form-label visually-hidden" for="birthday">Birthday</label>
                        <input class="form-control" type="date" name="birthday" id="birthday" v-model="chosenBirthday"
                               placeholder="dd/mm/yyyy">
                    </div>
                    <div class="text-danger" role="alert">
                        <p v-for="error in errors.birthday">{{ error }}</p>
                    </div>
                </div>
            </div>

            <!-- Faction -->
            <h2>Faction</h2>
            <p>This is the faction that helped you get settled in this world. Whichever one you select will define how
                others see you, by assuming you follow that faction's ideals and broad outlook. It will also directly
                control where you start in the game.</p>
            <table>
                <tr v-for="(item, name) in factions" class="align-top">
                    <td class="pr-2 pb-2">
                        <input type="radio" class="btn-check" name="faction" v-model="chosenFaction" :value="name"
                               :id="'faction-' + name">
                        <label class="btn btn-outline-primary w-100" :for="'faction-' + name">{{ name }}</label>
                    </td>
                    <td class="ps-2 pb-2">
                        <div v-html="item.description"></div>
                    </td>
                </tr>
            </table>
            <div class="text-danger" role="alert">
                <p v-for="error in errors.faction">{{ error }}</p>
            </div>

            <!-- Starting Perks -->
            <h2>Starting Perks</h2>
            <p>These are only a fraction of the perks available and to streamline character generation their costs are
                hidden.</p>
            <p>Perks can be purchased at any time, so be sure to visit the perk page later to spend the rest of your
                points or to get more information.</p>
            <div v-for="category in perkCategories">
                <h3>• {{ category.label }}</h3>
                <p>{{ category.description }}</p>
                <table>
                    <tr v-for="(item, name) in perks"
                        v-if="category.category === item.category" class="align-top">
                        <td class="pr-2 pb-2">
                            <input type="checkbox" class="btn-check" name="perks[]" v-model="chosenPerks"
                                   :disabled="item.disabled" :value="name" :id="'perk-' + name" autocomplete="off"
                                   @change="updateExclusions('perks')">
                            <label class="btn btn-outline-primary w-100" :for="'perk-' + name">{{ name }}</label>
                        </td>
                        <td class="ps-2 pb-2">
                            <div v-html="item.description"></div>
                            <div class="small" v-if="item.excludes.length">Excludes: {{
                                    arrayToList(item.excludes)
                                }}
                            </div>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Flaws -->
            <h2>Flaws</h2>
            <p>You may take as many, or as few, flaws as you want.</p>
            <table>
                <tr v-for="(item, name) in flaws" class="align-top">
                    <td class="pr-2 pb-2">
                        <input type="checkbox" class="btn-check" name="flaws[]" v-model="chosenFlaws"
                               :disabled="item.disabled" :value="name" :id="'flaw-' + name" autocomplete="off"
                               @change="updateExclusions('flaws')">
                        <label class="btn btn-outline-primary w-100" :for="'flaw-' + name">{{ name }}</label>
                    </td>
                    <td class="ps-2 pb-2">
                        <div v-html="item.description"></div>
                        <div class="small" v-if="item.excludes.length">Excludes: {{
                                arrayToList(item.excludes)
                            }}
                        </div>

                    </td>
                </tr>
            </table>
            <div class="text-danger" role="alert">
                <p v-for="error in errors.flaws">{{ error }}</p>
            </div>

            <!-- Other errors -->
            <div class="text-danger" role="alert">
                <p v-for="error in errors.other">{{ error }}</p>
            </div>

            <!-- Submit -->
            <div class="text-center">
                <button type="submit" class="btn btn-primary" v-if="!submitting">Submit Character</button>
                <div v-if="submitting">Submitting..</div>
            </div>
        </form>


    </div>
</template>

<script setup>
import {ref} from 'vue';
import {csrf} from "../siteutils";
import {arrayToList} from "../formatting";

const props = defineProps({
    errors: {required: false},
    old: {type: Object, required: false},
    config: {type: Object, required: true}
});

const factions = ref(props.config?.factions || []);
const perks = ref(props.config?.perks || []);
const flaws = ref(props.config?.flaws || []);
const perkCategories = ref(props.config?.perkCategories || []);

const chosenGender = ref();
const chosenBirthday = ref();
const chosenFaction = ref();
const chosenPerks = ref([]);
const chosenFlaws = ref([]);

const submitting = ref(false);

const hideDuringSubmit = () => {
    submitting.value = true;
};

</script>

<style scoped>

</style>
