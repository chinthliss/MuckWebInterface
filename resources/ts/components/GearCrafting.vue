<script lang="ts" setup>

import {onMounted, Ref, ref, useTemplateRef} from "vue";
import {arrayToList, capital, rankedSalvageListToHtml} from "../formatting";
import {ResponseError} from "../defs";
import {lex} from "../siteutils";
import GearCraftingRecipeSelector from "./GearCraftingRecipeSelector.vue";
import GearCraftingModifierSelector from "./GearCraftingModifierSelector.vue";
import Callout from "./Callout.vue";
import ModalMessage from "./ModalMessage.vue";
import RpinfoViewer from "./RpinfoViewer.vue";

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
const newSavedPlanName: Ref<string> = ref('');
const newSavedPlanResponse: Ref<string> = ref('');
const recipeSelector: Ref<InstanceType<typeof GearCraftingRecipeSelector> | null> = useTemplateRef('recipe-selector');

const rpInfoCategory: Ref<string> = ref('');
const rpInfoItem: Ref<string> = ref('');
const rpInfoModal: Ref<InstanceType<typeof ModalMessage> | null> = ref(null);

const channel = mwiWebsocket.channel('gear');

const updatePreview = () => {
    preview.value = null;
    channel.send('craftPreview', {recipe: selectedRecipe.value, modifiers: selectedModifiers.value});
}

const recipeSelectorMounted = () => {
    if (recipeSelector.value) recipeSelector.value.show();
}

const recipeSelected = (recipeName: string) => {
    selectedRecipe.value = recipeName;
    updatePreview();
}

const recipeAndModifiersSelected = (recipeName: string, modifiers: string[]) => {
    selectedRecipe.value = recipeName;
    selectedModifiers.value = modifiers;
    updatePreview();
}

const modifiersChanged = () => {
    // selectedModifiers is two-way bound, so doesn't need updating, but still need to update preview
    updatePreview();
}

const saveSavedPlan = () => {
    channel.send('saveSavedPlan', {
        name: newSavedPlanName.value,
        recipe: selectedRecipe.value,
        modifiers: selectedModifiers.value
    });
    newSavedPlanResponse.value = 'Saved!';
    setTimeout(() => newSavedPlanResponse.value = '', 2000);
}

const rpinfoRequest = (request: { category: string, item: string }) => {
    rpInfoCategory.value = request.category;
    rpInfoItem.value = request.item;
    if (rpInfoModal.value) rpInfoModal.value.show();
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
                                       @rpinfo="rpinfoRequest"
                                       @recipe-selected="recipeSelected"
                                       @recipe-and-modifiers-selected="recipeAndModifiersSelected"
        >
        </gear-crafting-recipe-selector>
        <div v-if="!selectedRecipe">You need to select a recipe to continue.</div>
        <div v-else class="fw-bold"><span class="text-primary">Selected Recipe:</span> {{ selectedRecipe }}</div>


        <!-- Modifiers selection -->
        <template v-if="selectedRecipe">
            <hr/>
            <h3>Modifiers</h3>
            <gear-crafting-modifier-selector
                v-model:selected="selectedModifiers"
                :modifiers="modifiers"
                @update="modifiersChanged"
                @rpinfo="rpinfoRequest"
            >
            </gear-crafting-modifier-selector>
        </template>


        <template v-if="selectedRecipe">

            <!-- Preview -->
            <hr/>
            <h3>Preview</h3>
            <div v-if="!preview" class="preview-placeholder">Loading..</div>
            <div v-else-if="preview.result == 'ERROR'">
                <callout type="danger">
                    <div>Generating a preview failed.</div>
                    <div>{{ preview.error }}</div>
                </callout>
            </div>
            <div v-else class="row">
                <!-- Part 1 / Left side on big screens -->
                <div class="col-xl-6">
                    <table>
                        <tbody>

                        <tr>
                            <th class="pe-2" scope="row">Recipe</th>
                            <td>{{ preview.recipe }}</td>
                        </tr>

                        <tr>
                            <th class="pe-2" scope="row">Modifiers</th>
                            <td>{{ arrayToList(preview.modifiers) || 'None' }}</td>
                        </tr>

                        <tr>
                            <th class="pe-2" scope="row">Difficulty</th>
                            <td>
                                <div aria-hidden="true" class="d-inline-block">
                                    <div :class="{ d1: preview.feedback.difficultyTier >= 1}"
                                         class="difficultyContainer"></div>
                                    <div :class="{ d2: preview.feedback.difficultyTier >= 2}"
                                         class="difficultyContainer"></div>
                                    <div :class="{ d3: preview.feedback.difficultyTier >= 3}"
                                         class="difficultyContainer"></div>
                                    <div :class="{ d4: preview.feedback.difficultyTier >= 4}"
                                         class="difficultyContainer"></div>
                                    <div :class="{ d5: preview.feedback.difficultyTier >= 5}"
                                         class="difficultyContainer"></div>
                                </div>
                                {{ preview.feedback.difficultyTier }} - {{ preview.feedback.difficultyLabel }}
                            </td>
                        </tr>

                        <tr>
                            <th class="pe-2" scope="row">Skills</th>
                            <td>
                                <div v-for="(range, skill) in preview.skills">
                                    {{ capital(skill as string) }} of {{ range.worst }} to {{ range.best }}
                                </div>
                            </td>
                        </tr>

                        <tr>
                            <th class="pe-2" scope="row">Salvage</th>
                            <td>
                                <div v-for="(range, salvage) in preview.salvage">
                                    <b>{{ capital(salvage as string) }}</b>:
                                    <div>Best: <span v-html="rankedSalvageListToHtml(range.best)"></span></div>
                                    <div>Worst: <span v-html="rankedSalvageListToHtml(range.worst)"></span></div>
                                    <div>
                                        Crafter: <span v-html="rankedSalvageListToHtml(range.crafter)"></span>
                                        <span class="text-muted"> ({{ preview.feedback.modifiers[salvage] * 100 }}% cost)</span>
                                    </div>

                                </div>
                            </td>
                        </tr>

                        </tbody>
                    </table>
                </div>
                <div class="col-xl-6">
                    <table>
                        <tbody>

                        <tr>
                            <th class="pe-2" scope="row">Other Ingredients</th>
                            <td>
                                <div v-for="(quantity, ingredient) in preview.otherIngredients"
                                     v-if="preview.otherIngredients">
                                    {{ quantity }} x {{ capital(ingredient as string) }}
                                </div>
                                <div v-else>None</div>
                            </td>
                        </tr>

                        <tr>
                            <th class="pe-2" scope="row">{{ lex('money') }}</th>
                            <td>{{ preview.money.toLocaleString() }}</td>
                        </tr>

                        <tr>
                            <th class="pe-2" scope="row">Nanites</th>
                            <td>{{ preview.buildCost.toLocaleString() }}ng</td>
                        </tr>

                        <tr>
                            <th class="pe-2" scope="row">Upkeep</th>
                            <td>{{ preview.upkeep }}</td>
                        </tr>

                        <tr>
                            <th class="pe-2" scope="row">Loadout</th>
                            <td>{{ preview.loadout }}</td>
                        </tr>

                        <tr>
                            <th class="pe-2" scope="row">Quantity</th>
                            <td>{{ preview.quantity }}</td>
                        </tr>

                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Create new saved plan -->
            <h3 class="mt-2">Create New Saved Plan</h3>
            <div class="d-flex align-items-xl-end flex-column flex-xl-row">
                <div>
                    Want to save this combination so you can use it again quickly in the future?<br/>
                    If so, give it a name and hit Save Plan.<br/>
                    Using an existing saved plan name will overwrite it.
                </div>
                <div class="flex-grow-1 ms-2 ms-xl-4 me-2 mb-2 mb-xl-0">
                    <label class="col-form-label" for="newSavedPlanName">New Saved Plan Name</label>
                    <input id="newSavedPlanName" v-model="newSavedPlanName" class="form-control"
                           placeholder="New Saved Plan Name"
                           type="text">
                </div>
                <button class="btn btn-primary" @click="saveSavedPlan">Save Plan</button>
            </div>
            <div v-if="newSavedPlanResponse" class="alert alert-info mt-2">{{ newSavedPlanResponse }}</div>

        </template>
    </template>
    <modal-message ref="rpInfoModal" title="RP-Info">
        <rpinfo-viewer v-model:category="rpInfoCategory" v-model:item="rpInfoItem"></rpinfo-viewer>
    </modal-message>
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

.preview-placeholder {
    min-height: 640px;
}
</style>
