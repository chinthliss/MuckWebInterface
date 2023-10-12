<script setup lang="ts">
import {ref, onMounted, Ref} from 'vue';
import {csrf} from "../siteutils";
import {arrayToList, ansiToHtml} from "../formatting";

type CharacterSubmission = {
    gender: string,
    birthday: string,
    faction: string,
    perks: string[],
    flaws: string[]
}

type PerkOrFlawConfig = {
    name: string,
    description: string,
    category?: string,
    excludes: string[],
    excluded: boolean
}

type FactionConfig = {
    name: string,
    description: string
}

type PerkCategoryConfig = {
    label: string,
    category: string,
    description: string
}

const props = defineProps<{
    config: {
        factions: FactionConfig[]
        perkCategories: PerkCategoryConfig[]
        perks: PerkOrFlawConfig[]
        flaws: PerkOrFlawConfig[]
    },
    errors?: {
        gender?: string[]
        birthday?: string[]
        faction?: string[]
        perks?: string[]
        flaws?: string[]
        other?: string[]
    },
    old?: CharacterSubmission
}>();

const factions: Ref<FactionConfig[]> = ref(props.config?.factions || []);
const perks: Ref<PerkOrFlawConfig[]> = ref(props.config?.perks || []);
const flaws: Ref<PerkOrFlawConfig[]> = ref(props.config?.flaws || []);
const perkCategories: Ref<PerkCategoryConfig[]> = ref(props.config?.perkCategories || []);

const character: Ref<CharacterSubmission> = ref({
    gender: '',
    birthday: '',
    faction: '',
    perks: [],
    flaws: []
} as CharacterSubmission);

const submitting = ref(false);

const hideDuringSubmit = () => {
    submitting.value = true;
};

onMounted(() => {
    // Restore any old values
    if (props.old?.gender) character.value.gender = props.old.gender;
    if (props.old?.birthday) character.value.birthday = props.old.birthday;
    if (props.old?.faction) character.value.faction = props.old.faction;
    if (props.old?.perks) {
        Object.values(props.old.perks).forEach(item => {
            character.value.perks.push(item);
        })
    }
    if (props.old?.flaws) {
        Object.values(props.old?.flaws).forEach(item => {
            character.value.flaws.push(item);
        });
    }
});

const updateExclusions = (type: string) => {
    let catalog: PerkOrFlawConfig[];
    let selected: string[];
    if (type === 'perks') {
        catalog = perks.value;
        selected = character.value.perks;
    } else {
        catalog = flaws.value;
        selected = character.value.flaws;
    }
    // Pass 1 - get active exclusions
    let excluded = [];
    catalog.forEach(item => {
        if (selected.includes(item.name)) excluded = excluded.concat(item.excludes);
    });

    // Pass 2 - Tag excluded
    catalog.forEach(item => {
        item.excluded = excluded.includes(item.name);
    });

    // Unset anything excluded
    // selected = selected.filter(value => !excluded.includes(value));
    let changed: boolean = false;
    for (const excludedKey of excluded) {
        const index = selected.indexOf(excludedKey);
        if (index !== -1) {
            selected.splice(index, 1);
            changed = true;
        }
    }
    if (changed) updateExclusions(type); // Because unsetting something may remove additional exclusions
};

</script>

<template>
    <div class="container">

        <h1>Character Generation</h1>

        <form action="" method="POST" @submit="hideDuringSubmit">
            <input type="hidden" name="_token" :value="csrf()">

            <!-- Gender -->
            <h2 class="mt-2">Gender</h2>
            <div class="row">
                <div class="col-12 col-md-6">
                    <p>This is your starting biological gender and may change rapidly.</p>
                    <p>See some of the perks below if you wish to prevent or reduce the chance of this.</p>
                </div>
                <div class="col-12 col-md-6">
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="gender" value="male" id="gender-male"
                               v-model="character.gender"
                        >
                        <label class="form-check-label" for="gender-male">Male</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="gender" value="female" id="gender-female"
                               v-model="character.gender"
                        >
                        <label class="form-check-label" for="gender-female">Female</label>
                    </div>
                    <div class="text-danger" role="alert" v-if="errors?.gender">
                        <p v-for="error in errors.gender">{{ error }}</p>
                    </div>
                </div>
            </div>

            <!-- Birthday -->
            <h2 class="mt-2">Birthday</h2>
            <div class="row">
                <div class="col-12 col-md-6">
                    <p>Your birthday must be:</p>
                    <ul>
                        <li>After 1940 and before the present day.</li>
                        <li>Outside of the years 1990 - 2008.</li>
                    </ul>
                    <p>Regardless of what date you were born, due to nanites accelerating development the minimum age of
                        a character is 18.</p>
                </div>
                <div class="col-12 col-md-auto">
                    <div>
                        <label class="form-label visually-hidden" for="birthday">Birthday</label>
                        <input class="form-control" type="date" name="birthday" id="birthday"
                               v-model="character.birthday"
                               placeholder="dd/mm/yyyy"
                        >
                    </div>
                    <div class="text-danger" role="alert" v-if="errors?.birthday">
                        <p v-for="error in errors.birthday">{{ error }}</p>
                    </div>
                </div>
            </div>

            <!-- Faction -->
            <h2 class="mt-2">Faction</h2>
            <p>This is the faction that helped you get settled in this world. Whichever one you select will define how
                others see you, by assuming you follow that faction's ideals and broad outlook. It will also directly
                control where you start in the game.</p>
            <table>
                <tr v-for="faction in factions" class="align-top">
                    <td class="pe-2 pb-2">
                        <input type="radio" class="btn-check" name="faction" v-model="character.faction"
                               :value="faction.name"
                               :id="'faction-' + faction.name"
                        >
                        <label class="btn btn-outline-primary w-100" :for="'faction-' + faction.name">{{
                                faction.name
                            }}</label>
                    </td>
                    <td class="ps-2 pb-2">
                        <div v-html="ansiToHtml(faction.description)"></div>
                    </td>
                </tr>
            </table>
            <div class="text-danger" role="alert" v-if="errors?.faction">
                <p v-for="error in errors.faction">{{ error }}</p>
            </div>

            <!-- Starting Perks -->
            <h2 class="mt-2">Starting Perks</h2>
            <p>These are only a fraction of the perks available and, to streamline character generation, their costs are
                hidden.</p>
            <p>Perks can be purchased at any time, so be sure to visit the perk page later to spend the rest of your
                points or to get more information.</p>
            <div v-for="category in perkCategories">
                <h3>• {{ category.label }}</h3>
                <p v-html="ansiToHtml(category.description)"></p>
                <table>
                    <template v-for="perk in perks">
                        <tr v-if="perk && perk.category === category.category" class="align-top">
                            <td class="pe-2 pb-2">
                                <input type="checkbox" class="btn-check" name="perks[]" v-model="character.perks"
                                       :disabled="perk?.excluded" :value="perk.name" :id="'perk-' + perk.name"
                                       autocomplete="off"
                                       @change="updateExclusions('perks')"
                                >
                                <label class="btn btn-outline-primary w-100" :for="'perk-' + perk.name">{{
                                        perk.name
                                    }}</label>
                            </td>
                            <td class="ps-2 pb-2">
                                <div v-html="ansiToHtml(perk.description)"></div>
                                <div class="small text-muted" v-if="perk.excludes.length">Excludes: {{
                                        arrayToList(perk.excludes)
                                    }}
                                </div>
                            </td>
                        </tr>
                    </template>
                </table>
            </div>

            <!-- Flaws -->
            <h2 class="mt-2">Flaws</h2>
            <p>You may take as many, or as few, flaws as you want.</p>
            <table>
                <tr v-for="flaw in flaws" class="align-top">
                    <td class="pe-2 pb-2">
                        <input type="checkbox" class="btn-check" name="flaws[]" v-model="character.flaws"
                               :disabled="flaw?.excluded" :value="flaw.name" :id="'flaw-' + flaw.name"
                               autocomplete="off"
                               @change="updateExclusions('flaws')"
                        >
                        <label class="btn btn-outline-primary w-100" :for="'flaw-' + flaw.name">{{ flaw.name }}</label>
                    </td>
                    <td class="ps-2 pb-2">
                        <div v-html="ansiToHtml(flaw.description)"></div>
                        <div class="small text-muted" v-if="flaw.excludes.length">Excludes: {{
                                arrayToList(flaw.excludes)
                            }}
                        </div>

                    </td>
                </tr>
            </table>
            <div class="text-danger" role="alert" v-if="errors?.flaws">
                <p v-for="error in errors.flaws">{{ error }}</p>
            </div>

            <!-- Other errors -->
            <div class="text-danger" role="alert" v-if="errors?.other">
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

<style scoped>

</style>
