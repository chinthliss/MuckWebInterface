<script lang="ts" setup>

import {onMounted, Ref, ref} from "vue";

type Recipe = {
    name: string,
    description: string,
    item: {
        type: string,
        useType: string
    }
}

type Modifier = {
    name: string,
    description: string
}

type SavedPlan = {
    name: string,
    recipeName: string,
    recipe: Recipe | null, // Might be null if invalid
    modifierNames: string[]
    modifiers: Modifier[]
}

type CraftPreview = {}

const savedPlans: Ref<SavedPlan[]> = ref([]);
const recipes: Ref<Recipe[]> = ref([]);
const modifiers: Ref<Modifier[]> = ref([]);
const showDescriptions: Ref<boolean> = ref(false);
const selectedRecipe: Ref<string> = ref('');
const selectedModifiers: Ref<string[]> = ref([]);
const preview: Ref<CraftPreview | null> = ref(null);
const channel = mwiWebsocket.channel('gear');

const updatePreview = () => {
    preview.value = null;
    channel.send('preview', {recipe: selectedRecipe.value, modifiers: selectedModifiers.value});
}
const selectRecipe = (recipe: Recipe) => {
    selectedRecipe.value = recipe.name;
    updatePreview();
}

const toggleModifier = (modifier: Modifier) => {
    console.log("Toggled: ", modifier);
    if (selectedModifiers.value.includes(modifier.name))
        selectedModifiers.value.splice(selectedModifiers.value.indexOf(modifier.name), 1);
    else
        selectedModifiers.value.push(modifier.name);
    updatePreview();
}

const classForRecipeIcon = (recipe: Recipe) => {
    if (recipe.item.useType == 'consumable') return 'fa-utensils';
    if (recipe.item.useType == 'tool') return 'fa-toolbox';
    return 'fa-shirt'; // Default for equipment
}

channel.on('preview', (response: CraftPreview) => {
    preview.value = response;
});

channel.on('bootCrafting', (response: {
    savedPlans: SavedPlan[],
    recipes: Recipe[],
    modifiers: Modifier[]
}) => {
    savedPlans.value = response.savedPlans || [];
    recipes.value = response.recipes;
    modifiers.value = response.modifiers;
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

    <!-- Legend -->
    <div class="bg-secondary text-black p-1 rounded-4">
    <!-- <h6 class="text-center">Legend</h6> -->
        <div class="d-flex justify-content-evenly">
            <div><i class="fas fa-shirt use-type-icon"></i> Equipment</div>
            <div><i class="fas fa-toolbox use-type-icon"></i> Tool/Usable</div>
            <div><i class="fas fa-utensils use-type-icon"></i> Consumable</div>
        </div>
    </div>


    <h2>Saved Plans</h2>
    <p>These are saved combinations of a recipe and any modifiers, so that they can be re-used later.</p>
    <div v-if="savedPlans.length > 0" v-for="savedPlan in savedPlans">
        {{ savedPlan.name }}
        <span v-if="savedPlan.recipe">{{ savedPlan.recipe.name }}</span>
        <span v-else>INVALID: {{ savedPlan.recipeName }}</span>
    </div>
    <div v-else>You have no saved plans</div>

    <hr/>
    <h2>Present Plan</h2>
    <div class="form-check form-switch mb-2">
        <input id="showDescriptionsSwitch" v-model="showDescriptions" class="form-check-input" role="switch"
               type="checkbox"
        >
        <label class="form-check-label" for="showDescriptionsSwitch">Show Descriptions?</label>
    </div>
    <div class="row mb-2">
        <div class="col-12 col-xl-6">
            <h3>Select Recipe</h3>
            <div v-for="recipe in recipes" class="card button mb-2" role="button"
                 v-bind:class="{ 'text-bg-primary': recipe.name == selectedRecipe }"
                 @click="selectRecipe(recipe)"
            >
                <div class="card-body">
                    <h5 class="card-title">
                        <span class="d-flex">
                            <span class="flex-md-grow-1">{{ recipe.name }}</span>
                            <i :class="['fas', 'use-type-icon', classForRecipeIcon(recipe)]"></i>
                        </span>
                    </h5>
                    <h6 class="card-subtitle fst-italic">{{ recipe.item.type || 'Unset' }}</h6>
                    <p v-if="showDescriptions" class="card-text mt-2">{{ recipe.description }}</p>
                </div>

            </div>
        </div>
        <div class="col-12 col-xl-6">
            <h3>Select Modifiers</h3>
            <div v-for="modifier in modifiers" class="card mb-2" role="button"
                 v-bind:class="{ 'text-bg-primary': selectedModifiers.includes(modifier.name) }"
                 @click="toggleModifier(modifier)"
            >
                <div class="card-body">
                    <h5 class="card-title">{{ modifier.name }}</h5>
                    <p v-if="showDescriptions" class="card-text">{{ modifier.description }}</p>
                </div>

            </div>

        </div>
    </div>
    <h3>Preview</h3>
    <div v-if="preview">
        You're making a thing!
    </div>
    <div v-else-if="selectedRecipe">
        Loading..
    </div>
    <div v-else>
        You need to at least select a recipe.
    </div>

</template>

<style scoped>
.use-type-icon {
    width: 24px;
}
</style>
