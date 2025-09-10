<script setup lang="ts">

import type { Recipe, Modifier, RecipeAndModifiers } from "./GearCrafting.vue"
import {ref, Ref} from "vue";

const {
    expanded = true,
    recipes = [],
    modifiers = [],
    history = [],
    savedPlans = []
} = defineProps<{
    expanded: boolean,
    recipes: Recipe[],
    modifiers: Modifier[],
    history: RecipeAndModifiers[],
    savedPlans: RecipeAndModifiers[]
}>();

const emit = defineEmits(['update'])

</script>

<template>
    <p>RECIPE SELECTOR GOES HERE</p>
    <div>Recipes: {{ recipes.length }}</div>
    <div>Modifiers: {{ modifiers.length }}</div>

    <!-- Legend -->
    <div class="bg-secondary text-black p-1 rounded-4">
        <!-- <h6 class="text-center">Legend</h6> -->
        <div class="d-flex justify-content-evenly">
            <div><i class="fas fa-shirt use-type-icon"></i> Equipment</div>
            <div><i class="fas fa-toolbox use-type-icon"></i> Tool/Usable</div>
            <div><i class="fas fa-utensils use-type-icon"></i> Consumable</div>
        </div>
    </div>
</template>

<style scoped>

</style>
