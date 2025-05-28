<script setup lang="ts">

import {onMounted, Ref, ref} from "vue";

type Recipe = {
    name: string
}

type Modifier = {
    name: string
}

type Blueprint = {
    name: string,
    recipeName: string,
    recipe: Recipe | null, // Might be null if invalid
    modifierNames: string[]
    modifiers: Modifier[]
}

const blueprints: Ref<Blueprint[]> = ref([]);
const recipes: Ref<Recipe[]> = ref([]);
const modifiers: Ref<Modifier[]> = ref([]);

const channel = mwiWebsocket.channel('gear');

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
    <h3>My Blueprints</h3>
    <p>Blueprints are saved combinations of a recipe and any modifiers, so that they can be re-used later.</p>
    <div v-for="blueprint in blueprints">
        {{ blueprint.name }}
        <span v-if="blueprint.recipe">{{ blueprint.recipe.name }}</span>
        <span v-else>INVALID: {{ blueprint.recipeName }}</span>
    </div>

    <h3>Recipes</h3>
    <div v-for="recipe in recipes">{{ recipe.name }}</div>

    <h3>Modifiers</h3>
    <div v-for="modifier in modifiers">{{ modifier.name }}</div>
</template>

<style scoped>

</style>
