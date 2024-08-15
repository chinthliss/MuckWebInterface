<script setup lang="ts">
import {ref, onMounted, Ref} from "vue";
import ModalMessage from "./ModalMessage.vue";
import {lex} from "../siteutils";
import {AvatarItem, AvatarItemInstance} from "../defs";

const props = defineProps<{
    itemsIn: AvatarItem[],
    backgroundsIn: AvatarItem[],
    //Format: gradientId: [slots available]
    gradientsIn: { [gradientId: string]: string[] },
    renderUrl: string,
    stateUrl: string,
    gradientUrl: string
    itemUrl: string
    avatarWidthIn: number,
    avatarHeightIn: number
}>();

const avatarWidth = props.avatarWidthIn ?? 384;
const avatarHeight = props.avatarHeightIn ?? 640;


type AvatarColorSlot = {
    id: string,
    slot: string,
    label: string
}

const colorSlots: Ref<AvatarColorSlot[]> = ref([
    {id: 'skin1', slot: 'fur', label: 'Primary Fur / Skin'},
    {id: 'skin2', slot: 'fur', label: 'Secondary Fur / Skin'},
    {id: 'skin3', slot: 'skin', label: 'Naughty Bits'},
    {id: 'hair', slot: 'hair', label: 'Hair'},
    {id: 'eyes', slot: 'eyes', label: 'Eyes'}
]);

type AvatarColorSet = {
    skin1?: string | null
    skin2?: string | null
    skin3?: string | null
    hair?: string | null
    eyes?: string | null
}
type AvatarInstance = {
    colors: AvatarColorSet
    background: AvatarItemInstance | null
    items: AvatarItemInstance[]
}

let avatarCanvasContext: CanvasRenderingContext2D | null = null;
const messageDialog: Ref<InstanceType<typeof ModalMessage> | null> = ref(null);
const items: Ref<AvatarItem[]> = ref(props.itemsIn);
const backgrounds: Ref<AvatarItem[]> = ref(props.backgroundsIn);
const gradients: Ref<{ [gradientId: string]: string[] }> = ref(props.gradientsIn);
const avatarImg: Ref<HTMLImageElement | null> = ref(null);
const avatar: Ref<AvatarInstance> = ref({
    colors: {
        skin1: null,
        skin2: null,
        skin3: null,
        hair: null,
        eyes: null
    },
    background: null,
    items: []
});

const background = ref({ // Used to limit the sliders for such
    minWidth: -200,
    maxWidth: 200,
    minHeight: -200,
    maxHeight: 200
});
const loading = ref(true);
const saving = ref(false);
const messageDialogHeader = ref('');
const messageDialogContent = ref('');
const purchases: Ref<{
    gradients: {[gradientId: string]: string[]}, // Slots that require purchasing gradient for
    items: {[itemId: string]: { name: string, cost: number }}
}> = ref({
    gradients: {},
    items: {}
});

onMounted(() => {
    const canvasElement: HTMLCanvasElement = document.getElementById('Renderer') as HTMLCanvasElement;
    avatarCanvasContext = canvasElement.getContext('2d');
    loadAvatarState();
});

const loadAvatarState = () => {
    console.log("Loading avatar state");
    loading.value = true;
    axios.get(props.stateUrl)
        .then((response) => {
            let state = response.data;
            console.log("Loaded avatar state:", state);
            avatar.value.colors.skin1 = state.colors?.skin1 || '';
            avatar.value.colors.skin2 = state.colors?.skin2 || '';
            avatar.value.colors.skin3 = state.colors?.skin3 || '';
            avatar.value.colors.hair = state.colors?.hair || '';
            avatar.value.colors.eyes = state.colors?.eyes || '';

            if (state.background) {
                changeBackground(state.background.id);
                if (avatar.value.background) {
                    avatar.value.background.x = state.background.x;
                    avatar.value.background.y = state.background.y;
                    avatar.value.background.scale = state.background.scale;
                    avatar.value.background.rotate = state.background.rotate;
                }
            }

            if (state.items) {
                for (const startingItem of state.items) {
                    const item = addItem(startingItem.id);
                    if (item) {
                        item.x = startingItem.x;
                        item.y = startingItem.y;
                        item.z = startingItem.z;
                        item.scale = startingItem.scale;
                        item.rotate = startingItem.rotate;
                    }
                }
            }
            sortItems(); // Because legacy avatars may be in the wrong order
            updateDollImage();
            loading.value = false;
        })
        .catch((error) => {
            console.log("Attempt to load avatar state failed: ", error);
        });
};

const saveAvatarState = () => {
    console.log("Saving avatar state");
    saving.value = true;
    if (!avatar.value.background?.base) throw "Base background wasn't set whilst saving item.";
    let avatarState = {
        colors: avatar.value.colors,
        items: avatar.value.items.map((item: AvatarItemInstance): AvatarItem => {
            if (!item?.base) throw "Base item wasn't set whilst saving item.";
            return {
                id: item.base.id,
                name: item.base.name,
                rotate: item.rotate,
                scale: item.scale,
                x: item.x,
                y: item.y,
                z: item.z
            } as AvatarItem;
        }),
        background: {
            id: avatar.value.background.base.id,
            name: avatar.value.background.base.name,
            rotate: avatar.value.background.rotate,
            scale: avatar.value.background.scale,
            x: avatar.value.background.x,
            y: avatar.value.background.y,
            z: avatar.value.background.z
        }
    };
    axios.post(props.stateUrl, avatarState)
        .then(() => {
            console.log("Saved avatar state.");
        })
        .catch((error) => {
            console.log("Attempt to save avatar state failed: ", error?.response?.data || error);
        })
        .then(() => {
            saving.value = false;
        });
};

const updateDollImage = () => {
    console.log("Updating doll image");
    //For the editor the only thing on the doll loaded from the server is the coloring
    let setColors: AvatarColorSet = {};
    if (avatar.value.colors.skin1) setColors.skin1 = avatar.value.colors.skin1;
    if (avatar.value.colors.skin2) setColors.skin2 = avatar.value.colors.skin2;
    if (avatar.value.colors.skin3) setColors.skin3 = avatar.value.colors.skin3;
    if (avatar.value.colors.hair) setColors.hair = avatar.value.colors.hair;
    if (avatar.value.colors.eyes) setColors.eyes = avatar.value.colors.eyes;
    avatarImg.value = new Image();
    avatarImg.value!.onload = () => {
        console.log("Avatar Doll loaded: " + avatarImg.value?.src);
        redrawCanvas();
    }
    avatarImg.value!.src = props.renderUrl + '/' + (Object.values(setColors).length > 0 ? btoa(JSON.stringify(setColors)) : '');
    refreshPurchases();
};

const drawItemOnContext = (ctx: CanvasRenderingContext2D, item: AvatarItemInstance) => {
    const imageWidth = item.image.naturalWidth;
    const imageHeight = item.image.naturalHeight;
    ctx.translate(item.x, item.y);
    ctx.scale(item.scale, item.scale);
    // Ideally we wouldn't do the next line and we'd work from the centre
    // However the existing framework works from the top-left, so we need to match
    ctx.translate(imageWidth / 2, imageHeight / 2);
    ctx.rotate(item.rotate * (Math.PI / 180.0));

    ctx.drawImage(item.image, -imageWidth / 2, -imageHeight / 2);
    //Reset translation/rotation
    ctx.setTransform(1, 0, 0, 1, 0, 0);
};

const redrawCanvas = () => {
    if (!avatarCanvasContext) return;
    console.log("Redrawing canvas");
    const ctx = avatarCanvasContext;
    ctx.clearRect(0, 0, ctx.canvas.width, ctx.canvas.height);

    if (avatar.value.background) drawItemOnContext(ctx, avatar.value.background);

    //Draw items behind avatar first
    for (const item of avatar.value.items) {
        if (item.z < 0) drawItemOnContext(ctx, item);
    }

    //Draw Avatar
    if (avatarImg.value) ctx.drawImage(avatarImg.value as CanvasImageSource, 0, 0);

    //Draw items in front of avatar
    for (const item of avatar.value.items) {
        if (item.z >= 0) drawItemOnContext(ctx, item);
    }
};

const adjustZ = (item: AvatarItemInstance, modifier: number) => {
    let oldZ = item.z;
    let newZ = oldZ + modifier;

    //Avoid using 0, since that represents the character doll
    if (newZ === 0 && oldZ === -1) newZ = 1;
    if (newZ === 0 && oldZ === 1) newZ = -1;
    item.z = newZ;
    sortItems();
    redrawCanvas();
};

const sortItems = () => {
    avatar.value.items.sort((a, b) => {
        if (a.z === b.z) return 0;
        return a.z < b.z ? -1 : 1;
    });
};

const changeBackground = (newId: string) => {
    console.log("Changing background to: " + newId);
    let template = null;
    for (const item of backgrounds.value) {
        if (item.id === newId) {
            template = item;
        }
    }
    if (!template) throw "Unable to find background '" + newId + "' in the background catalog.";
    if (!template.url) throw "Background doesn't have an url to load an image from!";
    if (!template.owner && !template.earned && !template.cost && template.requirement)
        throw "Couldn't switch to new background because it has an unmet requirement.";

    avatar.value.background = {
        base: template,
        rotate: template.rotate,
        scale: template.scale,
        x: template.x,
        y: template.y,
        z: 0
    } as AvatarItemInstance;
    avatar.value.background.image = new Image();
    avatar.value.background.image.onload = () => {
        if (!avatar.value.background) return;
        background.value.minWidth = -avatar.value.background.image.naturalWidth;
        background.value.minHeight = -avatar.value.background.image.naturalHeight;
        background.value.maxWidth = avatar.value.background.image.naturalWidth;
        background.value.maxHeight = avatar.value.background.image.naturalHeight;
        redrawCanvas();
    }
    avatar.value.background.image.src = template.url;
    refreshPurchases();
};

const removeBackground = () => {
    avatar.value.background = null;
    refreshPurchases();
    redrawCanvas();
};

const addItem = (newId: string) => {
    console.log("Adding item: " + newId);
    let template = null;
    // Find and make a new instanced version
    for (const possibleItem of items.value) {
        if (possibleItem.id === newId) {
            template = possibleItem;
        }
    }
    if (!template) throw "Unable to find item '" + newId + "' in the item catalog.";
    if (!template.url) throw "Item doesn't have an url to load an image from!";
    if (!template.owner && !template.earned && !template.cost && template.requirement)
        throw "Couldn't switch to new item because it has an unmet requirement.";
    let item = {
        base: template,
        rotate: template.rotate,
        scale: template.scale,
        x: template.x,
        y: template.y,
        z: 1
    }  as AvatarItemInstance;
    // Find highest Z so far
    for (const otherItem of avatar.value.items) {
        item.z = Math.max(item.z, otherItem.z + 1);
    }
    avatar.value.items.push(item);
    item.image = new Image();
    item.image.onload = () => {
        console.log("Item loaded " + item.image.src);
        redrawCanvas();
    }
    item.image.src = template.url;
    refreshPurchases();
    return item;
};

const addItemAndGotoIt = (itemId: string) => {
    if (avatar.value.items.length >= 10) return;
    addItem(itemId);
    let triggerEl = document.getElementById('nav-items-edit-tab');
    if (triggerEl) triggerEl.click();
};

const deleteItem = (item: AvatarItemInstance) => {
    let index = avatar.value.items.indexOf(item);
    if (index === -1) throw "Couldn't find an index to delete requested item!";
    avatar.value.items.splice(index, 1);
    sortItems();
    redrawCanvas();
    refreshPurchases();
};

const itemCostOrStatus = (item: AvatarItem) => {
    if (item.owner) return 'Owner';
    if (item.earned) return 'Earned';
    if (item.cost) return item.cost + ' ' + lex('accountcurrency');
    if (item.requirement) return 'Requirements unmet';
    return '';
};

const refreshPurchases = () => {
    // Collect a list of anything that requires purchasing
    purchases.value.gradients = {};
    for (const color of colorSlots.value) {
        let gradientId = avatar.value.colors[color.id];
        let gradient = gradientId && gradients.value[gradientId];
        if (gradient) {
            if (gradient.indexOf(color.slot) === -1) {
                if (!purchases.value.gradients[gradientId])
                    purchases.value.gradients[gradientId] = [];
                purchases.value.gradients[gradientId].push(color.slot);
            }
        }
    }

    purchases.value.items = {};
    for (const item of avatar.value.items) {
        if (item.base.cost && !item.base.earned && !item.base.owner) {
            purchases.value.items[item.base.id] = {
                name: item.base.name,
                cost: item.base.cost
            };
        }
    }
    if (avatar.value.background && avatar.value.background.base.cost && !avatar.value.background.base.earned && !avatar.value.background.base.owner)
        purchases.value.items[avatar.value.background.base.id] = {
            name: avatar.value.background.base.name,
            cost: avatar.value.background.base.cost
        };
};
const purchaseGradient = (gradientId: string, slot: string) => {
    const buttons = document.querySelectorAll(`button[data-gradient="${gradientId}"]`) as NodeListOf<HTMLButtonElement>;
    buttons.forEach(el => el.disabled = true);

    console.log("Purchasing gradient: ", gradientId, " for slot ", slot);
    axios.post(props.gradientUrl, {gradient: gradientId, slot: slot})
        .then((response) => {
            if (response.data === 'OK') {
                console.log("Purchasing gradient successful.");
                if (slot === 'all') {
                    gradients.value[gradientId] = ["fur", "skin", "hair", "eyes"];
                } else gradients.value[gradientId].push(slot);
                refreshPurchases();
            } else {
                console.log("Purchasing gradient refused: " + response.data);
                messageDialogHeader.value = "Purchase failed";
                messageDialogContent.value = "Something went wrong with the purchase:\n" + response.data;
                if (messageDialog.value) messageDialog.value.show();
            }
        })
        .catch((error) => {
            console.log("Error with purchasing gradient: ", error);
        })
        .then(() => {
            const buttons = document.querySelectorAll(`button[data-gradient="${gradientId}"]`) as NodeListOf<HTMLButtonElement>;
            buttons.forEach(el => el.disabled = false);
        });
};

const purchaseItem = (itemId: string) => {
    const buttons = document.querySelectorAll(`button[data-item="${itemId}"]`) as NodeListOf<HTMLButtonElement>;
    buttons.forEach(el => el.disabled = true);
    console.log("Purchasing item: ", itemId);
    axios.post(props.itemUrl, {item: itemId})
        .then((response) => {
            if (response.data === 'OK') {
                console.log("Purchasing item successful.");
                // Might be a background or item
                for (const item of items.value) {
                    if (item.id === itemId) item.earned = true;
                }
                for (const item of backgrounds.value) {
                    if (item.id === itemId) item.earned = true;
                }
                refreshPurchases();
            } else {
                console.log("Purchasing item refused: " + response.data);
                messageDialogHeader.value = "Purchase failed";
                messageDialogContent.value = "Something went wrong with the purchase:\n" + response.data;
                if (messageDialog.value) messageDialog.value.show();
            }
        })
        .catch((error) => {
            console.log("Error with purchasing item: ", error);
        })
        .then(() => {
            const buttons = document.querySelectorAll(`button[data-item="${itemId}"]`) as NodeListOf<HTMLButtonElement>;
            buttons.forEach(el => el.disabled = false);

        });
};

</script>

<template>
    <div class="container">

        <div class="row">
            <div class="col">
                <div class="bg-secondary text-black p-2 mb-4 text-center rounded">
                    <div>If you're using the latest version of the BeipMU client, it's possible to see avatars ingame!
                    </div>
                    <div>Type 'avatars #help' whilst connected for more information.</div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col">
                <div class="bg-secondary text-black p-2 mb-4 text-center rounded">
                    <div>You can set how explicit avatars appear (or whether they appear at all) from the account
                        screen.
                    </div>
                </div>
            </div>
        </div>


        <h2>Avatar Editor</h2>

        <div id="DrawingHolder">
            <div v-if="loading" class="text-center">
                <div class="spinner-border" role="status"></div>
                <div>Loading...</div>
            </div>
            <canvas :width="avatarWidth" :height="avatarHeight" id="Renderer"></canvas>
        </div>

        <ul class="nav nav-tabs nav-fill mt-2" id="avatar-edit-tab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="nav-colors-tab" data-bs-toggle="tab" data-bs-target="#nav-colors"
                        type="button" role="tab" aria-controls="nav-colors" aria-selected="true">Select Colors</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="nav-background-tab" data-bs-toggle="tab" data-bs-target="#nav-background"
                        type="button" role="tab" aria-controls="nav-background" aria-selected="false">Select / Change Background</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="nav-items-edit-tab" data-bs-toggle="tab" data-bs-target="#nav-items-edit"
                        type="button" role="tab" aria-controls="nav-items-edit" aria-selected="false">Edit Items</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="nav-items-add-tab" data-bs-toggle="tab" data-bs-target="#nav-items-add"
                        type="button" role="tab" aria-controls="nav-items-add" aria-selected="false">Add Items</button>
            </li>
        </ul>
        <div class="tab-content border p-4" id="nav-tabContent">

            <!-- Gradients -->
            <div class="tab-pane show active" id="nav-colors" role="tabpanel" aria-labelledby="nav-colors-tab">
                <div class="form-group" v-for="color in colorSlots">
                    <label :for="color.id">{{ color.label }}</label>
                    <select class="form-control" :id="color.id" v-model="avatar.colors[color.id]"
                            @change="updateDollImage">
                        <option value="">(Default)</option>
                        <option :value="gradient" v-for="(slots, gradient) in gradients">
                            {{ gradient + (slots.indexOf(color.slot) !== -1 ? '' : ' (Requires Purchase)') }}
                        </option>
                    </select>
                </div>
            </div>

            <!-- Background -->
            <div class="tab-pane" id="nav-background" role="tabpanel" aria-labelledby="nav-background-tab">
                <div v-if="avatar.background">
                    <div>Present background: {{ avatar.background.base.name }}</div>
                    <button class="btn btn-secondary" @click="removeBackground">Remove Background</button>

                    <div class="d-flex align-items-center mt-2">
                        <div class="sliderLabel">Rotation</div>
                        <div class="ms-1 flex-fill"><input type="range" v-model.number="avatar.background.rotate"
                                                           class="form-control-range w-100" min="0" max="359"
                                                           @change="redrawCanvas"></div>
                        <div class="ms-1 sliderValue">{{ avatar.background.rotate }}</div>
                    </div>

                    <div class="d-flex align-items-center mt-2">
                        <div class="sliderLabel">Scale</div>
                        <div class="ms-1 flex-fill"><input type="range" v-model.number="avatar.background.scale"
                                                           class="form-control-range w-100" min="0.1" max="2.0" step="0.01"
                                                           @change="redrawCanvas"></div>
                        <div class="ms-1 sliderValue">{{ avatar.background.scale }}</div>
                    </div>

                    <div class="d-flex align-items-center mt-2">
                        <div class="sliderLabel">X Offset</div>
                        <div class="ms-1 flex-fill"><input type="range" v-model.number="avatar.background.x"
                                                           class="form-control-range w-100" :min="background.minWidth"
                                                           :max="background.maxWidth"
                                                           @change="redrawCanvas"></div>
                        <div class="ms-1 sliderValue">{{ avatar.background.x }}</div>
                    </div>

                    <div class="d-flex align-items-center mt-2">
                        <div class="sliderLabel">Y Offset</div>
                        <div class="ms-1 flex-fill"><input type="range" v-model.number="avatar.background.y"
                                                           class="form-control-range w-100" :min="background.minHeight"
                                                           :max="background.maxHeight"
                                                           @change="redrawCanvas"></div>
                        <div class="ms-1 sliderValue">{{ avatar.background.y }}</div>
                    </div>

                </div>
                <h4 class="mt-2">Change to a different background:</h4>
                <div class="row">
                    <div role="button" class="card item-card" v-for="background in backgrounds"
                         :class="[{
                             border: avatar.background && background.id === avatar.background.base.id,
                             unavailable: !background.earned && !background.owner && !background.cost && background.requirement
                         }]"
                         @click="changeBackground(background.id)">
                        <div class="card-img-top position-relative">
                            <img :src="background.preview_url" alt="Background Thumbnail">
                        </div>
                        <div class="card-body">
                            <div class="text-center">{{ background.name }}</div>
                            <div class="text-center small">{{ itemCostOrStatus(background) }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Items - Edit -->
            <div class="tab-pane" id="nav-items-edit" role="tabpanel" aria-labelledby="nav-items-edit-tab">
                <p>This list will re-order automatically as the drawing order is changed. Items with a negative Z value
                    are drawn behind the character.</p>
                <p v-if="avatar.items.length === 0">No items added - use the 'Add Items' tab to add them.</p>
                <div class="mb-2" v-for="item in avatar.items">
                    <span>{{ item.base.name }} @ X: {{ item.x }}, Y: {{ item.y }}, Z: {{ item.z }}</span>
                    <button class="btn btn-secondary ms-2" @click="adjustZ(item, 1)">Move Forwards</button>
                    <button class="btn btn-secondary ms-2" @click="adjustZ(item, -1)">Move Backwards</button>
                    <button class="btn btn-secondary ms-2" @click="deleteItem(item)">Delete</button>

                    <div class="d-flex align-items-center mt-2">
                        <div class="sliderLabel">X</div>
                        <div class="ms-1 flex-fill"><input type="range" v-model.number="item.x"
                                                           class="form-control-range w-100" min="0" :max="avatarWidth"
                                                           @change="redrawCanvas"></div>
                        <div class="ms-1 sliderValue">{{ item.x }}</div>
                    </div>

                    <div class="d-flex align-items-center mt-2">
                        <div class="sliderLabel">Y</div>
                        <div class="ms-1 flex-fill"><input type="range" v-model.number="item.y"
                                                           class="form-control-range w-100" min="0" :max="avatarHeight"
                                                           @change="redrawCanvas"></div>
                        <div class="ms-1 sliderValue">{{ item.y }}</div>
                    </div>

                    <div class="d-flex align-items-center mt-2">
                        <div class="sliderLabel">Rotation</div>
                        <div class="ms-1 flex-fill"><input type="range" v-model.number="item.rotate"
                                                           class="form-control-range w-100" min="0" max="359"
                                                           @change="redrawCanvas"></div>
                        <div class="ms-1 sliderValue">{{ item.rotate }}</div>
                    </div>

                    <div class="d-flex align-items-center mt-2">
                        <div class="sliderLabel">Scale</div>
                        <div class="ms-1 flex-fill"><input type="range" v-model.number="item.scale"
                                                           class="form-control-range w-100" min="0.1" max="2.0" step="0.01"
                                                           @change="redrawCanvas"></div>
                        <div class="ms-1 sliderValue">{{ item.scale }}</div>
                    </div>


                </div>
            </div>

            <!-- Items - Add -->
            <div class="tab-pane" id="nav-items-add" role="tabpanel" aria-labelledby="nav-items-add-tab">
                <div class="row">
                    <div role="button" class="card item-card" v-for="item in items"
                         :class="[{
                             unavailable: !item.earned && !item.owner && !item.cost && item.requirement
                         }]"
                         @click="addItemAndGotoIt(item.id)">
                        <div class="card-img-top position-relative">
                            <img :src="item.preview_url" alt="Background Thumbnail">
                        </div>
                        <div class="card-body">
                            <div class="text-center">{{ item.name }}</div>
                            <div class="text-center small">{{ itemCostOrStatus(item) }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Any pending purchases -->
        <div v-if="Object.keys(purchases.items).length || Object.keys(purchases.gradients).length">
            <h4>Purchases Required</h4>
            <div v-for="(slots, gradient) in purchases.gradients">
                Color '{{ gradient }}'
                <button :data-gradient="gradient" :disabled="slots.length > 1"
                        class="mt-2 ms-2 btn btn-primary btn-with-img-icon"
                        @click="purchaseGradient(gradient, slots[0])">
                    <span class="btn-icon-accountcurrency btn-icon-left"></span>
                    Buy for a single slot
                    <span class="btn-second-line">5 {{ lex('accountcurrency') }}</span>
                </button>
                <button :data-gradient="gradient" class="mt-2 ms-2 btn btn-primary btn-with-img-icon"
                        @click="purchaseGradient(gradient, 'all')">
                    <span class="btn-icon-accountcurrency btn-icon-left"></span>
                    Buy for all slots
                    <span class="btn-second-line">10 {{ lex('accountcurrency') }}</span>
                </button>
            </div>
            <div v-for="(item, id) in purchases.items">
                Accessory '{{ item.name }}'
                <button :data-item="id" class="mt-2 ms-2 btn btn-primary btn-with-img-icon" @click="purchaseItem(id)">
                    <span class="btn-icon-accountcurrency btn-icon-left"></span>
                    Buy
                    <span class="btn-second-line">{{ item.cost }} {{ lex('accountcurrency') }}</span>
                </button>
            </div>
        </div>

        <!-- Save -->
        <button
            :disabled="saving || Object.keys(purchases.items).length > 0 || Object.keys(purchases.gradients).length > 0"
            class="mt-2 btn btn-primary" @click="saveAvatarState">Save Changes
            <span v-if="saving" class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
        </button>

        <modal-message ref="messageDialog" :title="messageDialogHeader">
            {{ messageDialogContent }}
        </modal-message>
    </div>
</template>

<style scoped lang="scss">
@use 'resources/sass/variables' as *;

.imgResource {
    display: none;
}

#Renderer {
    position: absolute;
    top: 0;
    left: 0;
    bottom: 0;
    right: 0;
}

#DrawingHolder {
    border: 1px solid $primary;
    position: relative;
    display: inline-block;
    width: 386px;
    height: 642px;
}

.item-card {
    width: 160px;
    height: 180px;
    display: inline-block;
}

.item-card.unavailable {
    cursor: not-allowed;
    filter: grayscale(100%);
}

.item-card .card-img-top {
    width: 160px;
    height: 60px;
}

.item-card .card-img-top img {
    max-height: 100%;
    max-width: 100%;
    width: auto;
    height: auto;
    position: absolute;
    top: 0;
    bottom: 0;
    left: 0;
    right: 0;
    margin: auto;
}

.sliderLabel {
    min-width: 80px;
}

.sliderValue {
    min-width: 32px;
}
</style>
