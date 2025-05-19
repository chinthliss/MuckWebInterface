<script setup lang="ts">

type Recipe = {
    name: string
}

type Modifier = {
    name: string
}

type BluePrint = {
    recipe: Recipe
    modifiers: Modifier[]
}

const bluePrints: BluePrint[] = [];
const recipes: Recipe[] = [];
const modifiers: Modifier[] = [];
</script>

<template>
    <div>WORK IN PROGRESS</div>

    <h3>My Blueprints</h3>
    <p>Blueprints are saved combinations of a recipe and modifiers, so that you can use later.</p>
    <div v-for="blueprint in bluePrints">{{blueprint.recipe.name}}</div>

    <h3>Recipes</h3>
    <div v-for="recipe in recipes">{{recipe.name}}</div>

    <h3>Modifiers</h3>
    <div v-for="modifier in modifiers">{{modifier.name}}</div>
</template>

<style scoped>

</style>
