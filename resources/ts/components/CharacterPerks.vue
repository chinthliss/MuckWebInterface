<script setup lang="ts">

import {ref, Ref} from "vue";
import ModalConfirmation from "./ModalConfirmation.vue";

type Perk = {
    name: string,
    description: string,
    notes: string,
    cost: number
    tags?: string[],
    excludes?: string[],
    excluded?: boolean,
    owned?: boolean
}

const perks: Ref<Perk[]> = ref([]);
const perksToLoad: Ref<number | null> = ref(null);
const perksToLoadRemaining: Ref<number> = ref(1); // Starting at 1 to cover initial loading
const tags: Ref<string[]> = ref([]);
const perkPointsTotal: Ref<number> = ref(0);
const perkPointsAvailable: Ref<number> = ref(0);
const vanityPointsTotal: Ref<number> = ref(0);
const vanityPointsAvailable: Ref<number> = ref(0);
const showAll: Ref<boolean> = ref(true);

const updateNotesModal: Ref<InstanceType<typeof ModalConfirmation> | null> = ref(null);
const perkBeingUpdated: Ref<Perk | null> = ref(null);
const notesBeingUpdated: Ref<string> = ref('');

const channel = mwiWebsocket.channel('character');

channel.on('perksCatalogue', (data: number) => {
    perks.value = [];
    perksToLoadRemaining.value = data;
    perksToLoad.value = data;
});

channel.on('perk', (data: Perk) => {
    data.excluded = false;
    perks.value.push(data);
    perksToLoadRemaining.value--;
    if (data.tags) {
        for (const tag of data.tags) {
            if (tags.value.indexOf(tag) !== -1) tags.value.push(tag)
        }
    }
});

type PerkStatusUpdate = {
    perkTotal: number,
    perkSpent: number,
    vanityTotal: number,
    vanitySpent: number,
    owned: {
        name: string,
        notes: string
    }
};

channel.on('perkStatus', (update: PerkStatusUpdate) => {
    perkPointsTotal.value = update.perkTotal;
    perkPointsAvailable.value = update.perkTotal - update.perkSpent;
    vanityPointsTotal.value = update.vanityTotal;
    vanityPointsAvailable.value = update.vanityTotal - update.vanitySpent;
    // Clear existing values
    for (const perk of perks.value) {
        perk.owned = false;
        delete perk.notes;
    }
    // Push updates of owned perks
    for (const ownedPerk of update.owned) {
        const perk = perks.value.find((possiblePerk) => possiblePerk.name == ownedPerk.name);
        if (perk) {
            perk.owned = true;
            perk.notes = ownedPerk.notes;
        } else console.log("Couldn't find owned perk in catalog: ", ownedPerk);
    }
    recalculateExclusions();
});

const presentCostsForPerk = (perk: Perk): [number, number] => {
    let vanityCost = 0;
    if (perk.tags && perk.tags.indexOf('vanity') !== -1) {
        vanityCost = Math.min(perk.cost, vanityPointsAvailable.value);
    }
    return [vanityCost, perk.cost ? perk.cost - vanityCost : 0];
};

const presentCostForPerkAsString = (perk: Perk): string => {
    const costs = presentCostsForPerk(perk);
    const output = [];
    if (costs[0]) {
        output.push(costs[0] + " vanity points");
    }
    if (costs[1]) {
        output.push(costs[1] + " perk points");
    }
    return output.join(', ');
};

const canPurchase = (perk: Perk): boolean => {
    const costs = presentCostsForPerk(perk);
    return costs[1] <= perkPointsAvailable.value;
};
const buyPerk = (perk: Perk): void => {
    if (canPurchase(perk)) {
        channel.send('buyPerk', perk.name);
        // Muck sends a 'perkStatus' response after the purchase
    }
};

const startUpdatingNotes = (perk: Perk): void => {
    perkBeingUpdated.value = perk;
    notesBeingUpdated.value = perk.notes;
    updateNotesModal.value.show();
};

const saveUpdatedNotes = (): void => {
    if (!perkBeingUpdated.value) return;
    channel.send('updatePerkNotes', {perk: perkBeingUpdated.value?.name, notes: notesBeingUpdated.value});
    perkBeingUpdated.value.notes = notesBeingUpdated.value;
};

const recalculateExclusions = () => {
    let excluded = [];
    // Pass 1, calculation exclusions
    for (const perk of perks.value) {
        if (perk.owned && perk.excludes) {
            excluded = excluded.concat(perk.excludes);
        }
    }
    // console.log("Perks excluded: ", excluded);
    // Pass 2, flag excluded
    for (const perk of perks.value) {
        perk.excluded = (excluded.indexOf(perk.name) !== -1);
    }
}

// Send requests for data
channel.send('bootPerks');

</script>

<template>
    <!-- Filter controls -->
    <div class="d-flex justify-content-center mb-2">
        <div class="d-flex align-items-center">
            <div class="me-1 text-primary">Display:</div>
            <div class="me-4 btn-group" role="group" aria-label="Show All?">
                <input type="radio" class="btn-check" id="show_all" autocomplete="off"
                       v-model="showAll" :value="true"
                >
                <label class="btn btn-secondary" for="show_all">Show All</label>

                <input type="radio" class="btn-check" id="show_owned" autocomplete="off"
                       v-model="showAll" :value="false"
                >
                <label class="btn btn-secondary" for="show_owned">Show Owned Only</label>

            </div>
        </div>
    </div>

    <!-- Perk list -->
    <div v-if="perksToLoadRemaining">Loading..</div>
    <div v-else>
        <!-- Floating info bar -->
        <div class="position-sticky top-0 bg-primary text-dark p-1 m-1 rounded-1 z-1 d-flex">
            <div class="flex-grow-1">
                <div>Points: {{ perkPointsAvailable }} free of {{ perkPointsTotal }} total</div>
                <div>Vanity Points: {{ vanityPointsAvailable }} free of {{ vanityPointsTotal }} total</div>
            </div>
            <div class="align-self-center">TAGS</div>
        </div>

        <p>Perks that have the 'vanity' tag will use your vanity points first, then use regular points to make
            up the difference if required. This is reflected in the costs listed.</p>

        <template v-for="perk in perks">
            <div v-if="perk.owned || showAll" class="card mt-2"
                 v-bind:class="{ 'border-primary-subtle': !perk.owned, 'border-dark-subtle': perk.excluded }"
            >
                <!-- Body -->
                <div class="card-body">
                    <div class="float-end">
                        <span v-if="perk.owned" class="text-primary">Owned</span>
                        <span v-else-if="perk.excluded" class="text-warning">Excluded</span>
                        <span v-else-if="perk.cost">Cost: {{ presentCostForPerkAsString(perk) }}</span>
                        <span v-else>Free</span>
                        <button class="btn btn-primary ms-2" v-if="!perk.excluded && !perk.owned"
                                @click="buyPerk(perk)" :disabled="!canPurchase(perk)"
                        >
                            Buy
                        </button>
                    </div>
                    <h5 class="card-title" v-bind:class="{ 'text-primary': perk.owned, 'text-muted': perk.excluded }">
                        {{ perk.name }}
                    </h5>

                    <p class="card-text" v-bind:class="{ 'text-muted': perk.excluded }">{{ perk.description }}</p>
                    <template v-if="perk.owned">
                        <hr>
                        <div class="float-end">
                            <button class="btn btn-primary" @click="startUpdatingNotes(perk)">Edit Custom Notes</button>
                        </div>
                        <p class="card-text" v-bind:class="{ 'text-muted': perk.excluded }">{{ perk.notes }}</p>
                    </template>
                </div>

                <!-- Footer -->
                <div class="card-footer text-body-secondary d-flex"
                     v-bind:class="{ 'border-primary-subtle': !perk.owned, 'border-dark-subtle': perk.excluded }"
                >
                    <div class="flex-grow-1">
                        <template v-if="perk.excludes">Excludes: {{ perk.excludes.join(', ') }}</template>
                    </div>
                    <div>
                        <template v-if="!perk.tags">No tags</template>
                        <span v-for="tag in perk.tags" class="badge rounded-pill p-2 text-bg-info ms-1">{{ tag }}</span>
                    </div>
                </div>
            </div>
        </template>
    </div>

    <ModalConfirmation ref="updateNotesModal" @yes="saveUpdatedNotes"
                       title="Update Perk Notes" yes-label="Save Changes" no-label="Cancel"
    >
        <p>Editing the notes for the perk '{{ perkBeingUpdated?.name }}'.</p>
        <form>
            <textarea class="w-100" v-model="notesBeingUpdated"></textarea>
        </form>
    </ModalConfirmation>

</template>

<style scoped>
</style>
