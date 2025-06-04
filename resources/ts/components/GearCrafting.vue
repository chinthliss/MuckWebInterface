<script lang="ts" setup>

import {onMounted, Ref, ref} from "vue";

type Recipe = {
    name: string,
    description: string
}

type Modifier = {
    name: string,
    description: string
}

type Blueprint = {
    name: string,
    recipeName: string,
    recipe: Recipe | null, // Might be null if invalid
    modifierNames: string[]
    modifiers: Modifier[]
}

type CraftPreview = {}

const blueprints: Ref<Blueprint[]> = ref([]);
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

channel.on('preview', (response: CraftPreview) => {
    preview.value = response;
});

channel.on('bootCrafting', (response: {
    blueprints: Blueprint[],
    recipes: Recipe[],
    modifiers: Modifier[]
}) => {
    blueprints.value = response.blueprints;
    recipes.value = response.recipes;
    modifiers.value = response.modifiers;
    // Connect up blueprints
    for (const blueprint of blueprints.value) {
        blueprint.recipe = recipes.value.find(x => x.name === blueprint.recipeName) || null;
        if (!blueprint.recipe) console.warn(`Blueprint [${blueprint.name}]: Recipe [${blueprint.recipeName}] not found`);
        blueprint.modifiers = [];
        for (const modifierName of blueprint.modifierNames) {
            const modifier = modifiers.value.find(x => x.name === modifierName) || null;
            if (modifier) blueprint.modifiers.push(modifier);
            else console.warn(`Blueprint [${blueprint.name}]: Modifier [${modifierName}] not found`);
        }
    }
})

onMounted(() => {
    channel.send('bootCrafting');
})

</script>

<template>
    <h2>Saved Plans</h2>
    <p>These are saved combinations of a recipe and any modifiers, so that they can be re-used later.</p>
    <div v-for="blueprint in blueprints">
        {{ blueprint.name }}
        <span v-if="blueprint.recipe">{{ blueprint.recipe.name }}</span>
        <span v-else>INVALID: {{ blueprint.recipeName }}</span>
    </div>

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
            <div v-for="recipe in recipes" class="card mb-2"
                 v-bind:class="{ 'text-bg-primary': recipe.name == selectedRecipe }"
                 @click="selectRecipe(recipe)"
            >
                <div class="card-body">
                    <h5 class="card-title">{{ recipe.name }}</h5>
                    <p v-if="showDescriptions" class="card-text">{{ recipe.description }}</p>
                </div>

            </div>
        </div>
        <div class="col-12 col-xl-6">
            <h3>Select Modifiers</h3>
            <div v-for="modifier in modifiers" class="card mb-2"
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

</style>
