<script lang="ts" setup>

import {onMounted, Ref, ref, useTemplateRef} from "vue";
import {ansiToHtml, arrayToList, capital} from "../formatting";
import {ResponseError} from "../defs";
import {lex} from "../siteutils";
import GearCraftingRecipeSelector from "./GearCraftingRecipeSelector.vue";

export type Recipe = {
    name: string,
    description: string,
    item: {
        type: string,
        useType: string,
        slot: string
    },
    known: boolean
}

export type Modifier = {
    name: string,
    description: string,
    slot: string,
    known: boolean
}

// Represents a previous instance of either saved plans or history
export type RecipeAndModifiers = {
    name: string,
    recipeName: string,
    recipe: Recipe | null, // Might be null if invalid
    modifierNames: string[]
    modifiers: Modifier[]
    valid: boolean
}

type CraftPreview = {
    result: 'SUCCESS',
    recipe: string,
    modifiers: string[],
    feedback: {
        difficultyTier: number,
        difficultyLabel: string,
        modifiers: { [salvageType: string]: number }
    },
    buildCost: number,
    loadout: number,
    money: number,
    otherIngredients: {
        [ingredient: string]: number
    },
    quantity: number,
    scale: number,
    skills: {
        [skill: string]: {
            best: number,
            worst: number
        }
    },
    salvage: {
        [type: string]: {
            best: {
                [grade: string]: number
            },
            worst: {
                [grade: string]: number
            },
            crafter: {
                [grade: string]: number
            }
        }
    },
    upkeep: number,
    xp: number,
    notices?: string[],
    warnings?: string[]
} | ResponseError

const recipes: Ref<Recipe[]> = ref([]);
const modifiers: Ref<Modifier[]> = ref([]);
const selectedRecipe: Ref<string> = ref('');
const selectedModifiers: Ref<string[]> = ref([]);
const preview: Ref<CraftPreview | null> = ref(null);
const recipesToLoad: Ref<number | null> = ref(null); // If null, loading hasn't started. If 0, finished.
const modifiersToLoad: Ref<number | null> = ref(null); // If null, loading hasn't started. If 0, finished.
const savedPlans: Ref<RecipeAndModifiers[]> = ref([]); // Used by the recipe selector
const history: Ref<RecipeAndModifiers[]> = ref([]); // Used by the recipe selector

const recipeSelector: Ref<InstanceType<typeof GearCraftingRecipeSelector> | null> = useTemplateRef('recipe-selector');

const channel = mwiWebsocket.channel('gear');

const updatePreview = () => {
    preview.value = null;
    channel.send('craftPreview', {recipe: selectedRecipe.value, modifiers: selectedModifiers.value});
}

const toggleModifier = (modifier: Modifier) => {
    console.log("Toggled: ", modifier);
    if (selectedModifiers.value.includes(modifier.name))
        selectedModifiers.value.splice(selectedModifiers.value.indexOf(modifier.name), 1);
    else
        selectedModifiers.value.push(modifier.name);
    updatePreview();
}

const outputSalvageRange = (range: { [rank: string]: number }): string => {
    let fragments: string[] = [];
    for (const rank in range) {
        fragments.push(`${capital(rank)} x ${range[rank]}`);
    }
    return fragments.join(', ');
}

const recipeSelectorMounted = () => {
    if (recipeSelector.value) recipeSelector.value.show();
}

const recipeSelected = (recipeName: string) => {
    selectedRecipe.value = recipeName;
    updatePreview();
}

channel.on('craftPreview', (response: CraftPreview) => {
    preview.value = response;
})

channel.on('recipe', (response: Recipe) => {
    recipes.value.push(response);
    if (recipesToLoad.value) recipesToLoad.value--;
})

channel.on('modifier', (response: Modifier) => {
    modifiers.value.push(response);
    if (modifiersToLoad.value) modifiersToLoad.value--;
})


channel.on('bootCrafting', (response: {
    savedPlans: RecipeAndModifiers[],
    history: RecipeAndModifiers[],
    recipeCount: number, // Individual entries are sent separately otherwise it'll break the muck's reliable string length
    modifierCount: number // Individual entries are sent separately otherwise it'll break the muck's reliable string length
}) => {
    savedPlans.value = response.savedPlans || [];
    history.value = response.history || [];
    recipes.value = [];
    recipesToLoad.value = response.recipeCount;
    modifiers.value = [];
    modifiersToLoad.value = response.modifierCount;
    // Connect up saved plans
    for (const savedPlan of savedPlans.value) {
        savedPlan.recipe = recipes.value.find(x => x.name === savedPlan.recipeName) || null;
        if (!savedPlan.recipe) console.warn(`SavedPlan [${savedPlan.name}]: Recipe [${savedPlan.recipeName}] not found`);
        savedPlan.modifiers = [];
        for (const modifierName of savedPlan.modifierNames) {
            const modifier = modifiers.value.find(x => x.name === modifierName) || null;
            if (modifier) savedPlan.modifiers.push(modifier);
            else console.warn(`SavedPlan [${savedPlan.name}]: Modifier [${modifierName}] not found`);
        }
    }
})

onMounted(() => {
    channel.send('bootCrafting');
})

</script>

<template>

    <template v-if="recipesToLoad == null || modifiersToLoad == null">Starting up..</template>
    <template v-else-if="recipesToLoad > 0">Loading Recipes .. ({{ recipesToLoad }} remain)</template>
    <template v-else-if="modifiersToLoad > 0">Loading Recipe Modifiers .. ({{ recipesToLoad }} remain)</template>
    <template v-else>

        <!-- Recipe -->
        <hr/>
        <h3>Recipe</h3>
        <gear-crafting-recipe-selector ref="recipe-selector" :history="history" :modifiers="modifiers"
                                       :recipes="recipes"
                                       :saved-plans="savedPlans"
                                       @mounted="recipeSelectorMounted"
                                       @update="recipeSelected"
        >
        </gear-crafting-recipe-selector>
        <div v-if="!selectedRecipe">You need to select a recipe to continue.</div>
        <div v-else class="fw-bold"><span class="text-primary">Selected Recipe:</span> {{ selectedRecipe }}</div>


        <!-- Modifiers selection -->
        <template v-if="selectedRecipe">
            <hr/>
            <h3>Modifiers</h3>
            <div class="scrollable-area pe-2">
                <div v-for="modifier in modifiers" class="card mb-2" role="button"
                     v-bind:class="{ 'text-bg-primary': selectedModifiers.includes(modifier.name) }"
                     @click="toggleModifier(modifier)"
                >
                    <div class="card-body">
                        <h5 class="card-title">{{ modifier.name }}</h5>
                        <div v-if="modifier.slot" class="card-text">Slot: {{ capital(modifier.slot) }}</div>
                        <p class="card-text">{{ ansiToHtml(modifier.description) }}</p>
                    </div>
                </div>
            </div>
            <div class="fw-bold"><span class="text-primary">Selected Modifiers:</span> {{
                    arrayToList(selectedModifiers) || 'None'
                }}
            </div>
        </template>

        <!-- Preview -->
        <template v-if="selectedRecipe">
            <hr/>
            <h3>Preview</h3>
            <div v-if="preview">
                <div v-if="preview.result == 'ERROR'">{{ preview.error }}</div>
                <dl v-else class="row">

                    <dt class="col-sm-2">Recipe</dt>
                    <dd class="col-sm-10">{{ preview.recipe }}</dd>

                    <dt class="col-sm-2">Modifiers</dt>
                    <dd class="col-sm-10">{{ arrayToList(preview.modifiers) || 'None' }}</dd>

                    <dt class="col-sm-2">Difficulty</dt>
                    <dd class="col-sm-10">
                        <div aria-hidden="true" class="d-inline-block">
                            <div :class="{ d1: preview.feedback.difficultyTier >= 1}" class="difficultyContainer"></div>
                            <div :class="{ d2: preview.feedback.difficultyTier >= 2}" class="difficultyContainer"></div>
                            <div :class="{ d3: preview.feedback.difficultyTier >= 3}" class="difficultyContainer"></div>
                            <div :class="{ d4: preview.feedback.difficultyTier >= 4}" class="difficultyContainer"></div>
                            <div :class="{ d5: preview.feedback.difficultyTier >= 5}" class="difficultyContainer"></div>
                        </div>
                        {{ preview.feedback.difficultyTier }} - {{ preview.feedback.difficultyLabel }}
                    </dd>

                    <dt class="col-sm-2">Skills</dt>
                    <dd class="col-sm-10">
                        <div v-for="(range, skill) in preview.skills">
                            {{ capital(skill as string) }} of {{ range.worst }} to {{ range.best }}
                        </div>
                    </dd>


                    <dt class="col-sm-2">Salvage</dt>
                    <dd class="col-sm-10">
                        <div v-for="(range, salvage) in preview.salvage">
                            <b>{{ capital(salvage as string) }}</b>:
                            <div>Best: {{ outputSalvageRange(range.best) }}</div>
                            <div>Worst: {{ outputSalvageRange(range.worst) }}</div>
                            <div>
                                Crafter: {{ outputSalvageRange(range.crafter) }}
                                <span class="text-muted">({{ preview.feedback.modifiers[salvage] * 100 }}% cost)</span>
                            </div>

                        </div>
                    </dd>

                    <dt class="col-sm-2">Other Ingredients</dt>
                    <dd class="col-sm-10">
                        <div v-for="(quantity, ingredient) in preview.otherIngredients">
                            {{ quantity }} x {{ capital(ingredient as string) }}
                        </div>
                    </dd>

                    <dt class="col-sm-2">{{ lex('money') }}</dt>
                    <dd class="col-sm-10">{{ preview.money }}</dd>

                    <dt class="col-sm-2">Nanites</dt>
                    <dd class="col-sm-10">{{ preview.buildCost }}ng</dd>

                    <dt class="col-sm-2">Upkeep</dt>
                    <dd class="col-sm-10">{{ preview.upkeep }}</dd>

                    <dt class="col-sm-2">Loadout</dt>
                    <dd class="col-sm-10">{{ preview.loadout }}</dd>

                    <dt class="col-sm-2">Quantity</dt>
                    <dd class="col-sm-10">{{ preview.quantity }}</dd>

                </dl>
            </div>
            <div v-else>
                Loading..
            </div>
        </template>
    </template>
</template>

<style scoped>
.difficultyContainer {
    border: 1px solid #ccc;
    display: inline-block;
    width: 12px;
    height: 12px;
    margin-right: 2px;
}

.d1 {
    background-color: #FFEC19;
}

.d2 {
    background-color: #FFC100;
}

.d3 {
    background-color: #FF9800;
}

.d4 {
    background-color: #FF5607;
}

.d5 {
    background-color: #F6412D;
}

</style>
