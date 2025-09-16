<script lang="ts" setup>

import type {Modifier, Recipe, RecipeAndModifiers} from "./GearCrafting.vue"
import {onMounted, ref, Ref, useTemplateRef} from "vue";
import Collapse from "./Collapse.vue";
import {capital} from "../formatting";

const {
    recipes = [],
    history = [],
    savedPlans = []
} = defineProps<{
    recipes: Recipe[],
    modifiers: Modifier[],
    history: RecipeAndModifiers[],
    savedPlans: RecipeAndModifiers[]
}>();

const collapseControl: Ref<InstanceType<typeof Collapse> | null> = useTemplateRef('collapseControl');
const showDescriptions: Ref<boolean> = ref(false);
const nameFilter: Ref<string> = ref('');
const showEquipment: Ref<boolean> = ref(true);
const showUsable: Ref<boolean> = ref(true);
const showConsumable: Ref<boolean> = ref(true);

const show = () => {
    // TBC: Trigger load maybe?
    if (collapseControl.value) collapseControl.value.show();
}
defineExpose({show});

const hide = () => {
    if (collapseControl.value) collapseControl.value.hide();
}

const emit = defineEmits(['update', 'mounted'])

const classForRecipeIcon = (recipe: Recipe) => {
    if (recipe.item.useType == 'consumable') return 'fa-utensils';
    if (recipe.item.useType == 'tool') return 'fa-toolbox';
    return 'fa-shirt'; // Default for equipment
}

const selectRecipe = (recipeName: string) => {
    emit('update', recipeName);
    hide();
}

const shouldShow = (recipe: Recipe): boolean => {
    if (!showEquipment.value && recipe.item.useType == 'equipment') return false;
    if (!showUsable.value && recipe.item.useType == 'usable') return false;
    if (!showConsumable.value && recipe.item.useType == 'consumable') return false;
    if (!nameFilter.value) return true;
    return (recipe.name.toLowerCase().includes(nameFilter.value.toLowerCase()))
}

onMounted(() => {
    emit('mounted');
})

</script>

<template>
    <collapse ref="collapseControl" class="mb-2 p-2" title="Recipe Selection" @mounted="emit('mounted')">
        <ul id="recipeSelectionTabs" class="nav nav-tabs nav-fill mt-2 sticky-top" role="tablist">
            <li class="nav-item" role="presentation">
                <button id="all-tab" aria-controls="all-pane" aria-selected="true"
                        class="nav-link active" data-bs-target="#all-pane" data-bs-toggle="tab" role="tab"
                        type="button"
                >Select from complete list
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button id="saved-plans-tab" aria-controls="saved-plans-pane" aria-selected="true"
                        class="nav-link" data-bs-target="#saved-plans-pane" data-bs-toggle="tab" role="tab"
                        type="button"
                >Select from saved plans
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button id="history-tab" aria-controls="history-pane" aria-selected="true"
                        class="nav-link" data-bs-target="#history-pane" data-bs-toggle="tab" role="tab"
                        type="button"
                >Select from history
                </button>
            </li>
        </ul>
        <div id="recipeSelectionPanes" class="tab-content border p-4 rounded-bottom-1">
            <!-- Select from the full list -->
            <div id="all-pane" aria-labelledby="all-tab" class="tab-pane show active" role="tabpanel" tabindex="0">

                <!-- Controls -->
                <div>
                    <!-- Type Filter -->
                    <div class="d-flex align-items-center mb-2">
                        <div class="me-2">Type Filter</div>
                        <div class="flex-grow-1 d-flex flex-column justify-content-evenly flex-lg-row">

                            <input id="btn-equipment" v-model="showEquipment" autocomplete="off" class="btn-check"
                                   type="checkbox">
                            <label class="btn btn-outline-primary w-100 me-2 mb-2" for="btn-equipment">
                                <i class="fas fa-shirt use-type-icon"></i> Equipment
                            </label>

                            <input id="btn-usable" v-model="showUsable" autocomplete="off" class="btn-check"
                                   type="checkbox">
                            <label class="btn btn-outline-primary w-100 me-2 mb-2" for="btn-usable">
                                <i class="fas fa-toolbox use-type-icon"></i> Tool/Usable
                            </label>

                            <input id="btn-consumable" v-model="showConsumable" autocomplete="off" class="btn-check"
                                   type="checkbox">
                            <label class="btn btn-outline-primary w-100 mb-2" for="btn-consumable">
                                <i class="fas fa-utensils use-type-icon"></i> Consumable
                            </label>

                        </div>
                    </div>

                    <div class="row">
                        <!-- Name toggle -->
                        <div class="col-12 col-xl-6 d-flex mb-2">
                            <label class="col-form-label me-2" for="nameFilter">Name</label>
                            <input id="nameFilter" v-model="nameFilter" class="form-control"
                                   placeholder="Filter by name"
                                   type="text">
                        </div>
                        <!-- Description toggle -->
                        <div
                            class="col-12 col-xl-6 form-check form-switch mb-2 d-flex align-items-center justify-content-center">
                            <input id="showDescriptionsSwitch" v-model="showDescriptions" class="form-check-input me-2"
                                   role="switch"
                                   type="checkbox"
                            >
                            <label class="form-check-label" for="showDescriptionsSwitch">Show Descriptions?</label>
                        </div>


                    </div>
                </div>


                <div>
                    <template v-for="recipe in recipes" :key="recipe.name">
                        <div v-if="shouldShow(recipe)" class="card button mb-2"
                             role="button"
                             @click="selectRecipe(recipe.name)"
                        >
                            <div class="card-body">
                                <h5 class="card-title">
                                    <span class="d-flex align-items-center">
                                        <span class="flex-grow-1">{{ recipe.name }}</span>
                                        <i :class="['fas', 'use-type-icon', classForRecipeIcon(recipe)]"></i>
                                    </span>
                                </h5>

                                <div class="card-subtitle fst-italic">{{ recipe.item.type || 'Unset' }}</div>
                                <div v-if="recipe.item.slot" class="card-text">Slot: {{
                                        capital(recipe.item.slot)
                                    }}
                                </div>
                                <p v-if="showDescriptions" class="card-text mt-2">{{ recipe.description }}</p>
                            </div>

                        </div>
                    </template>
                </div>

            </div>

            <!-- Select from saved plans -->
            <div id="saved-plans-pane" aria-labelledby="saved-plans-tab" class="tab-pane" role="tabpanel" tabindex="0">
                <div>SAVED PLANS GO HERE</div>
                <div v-for="plan in savedPlans" class="card button mb-2" role="button">
                    <div class="card-body">
                        <h5 class="card-title">{{ plan.name }}</h5>
                        <h6 class="card-subtitle fst-italic">{{ plan.recipeName }}</h6>
                    </div>
                </div>
            </div>

            <!-- Select from history -->
            <div id="history-pane" aria-labelledby="history-tab" class="tab-pane" role="tabpanel" tabindex="0">
                <div>RECENT HISTORY GOES HERE</div>
                <div v-for="entry in history" class="card button mb-2" role="button">
                    <div class="card-body">
                        <h5 class="card-title">{{ entry.name || entry.recipeName }}</h5>
                    </div>
                </div>
            </div>

        </div>
    </collapse>
</template>

<style scoped>
.use-type-icon {
    width: 24px;
}

</style>
