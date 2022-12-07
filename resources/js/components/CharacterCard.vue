<template>
    <div class="card character-card" :class="'mode-' + mode" @click="clicked">
        <div class="card-body">
            <div class="avatar" v-bind:style="styleObject">
                <!--<i class="fas fa-user-alt fa-5x"></i>-->
            </div>
            <div class="name">{{ character.name }}</div>
            <div v-if="!character.wizLevel" class="level">{{ character.level }}</div>
            <div v-else-if="character.wizLevel === 1" class="flag staff">Staff</div>
            <div v-else-if="character.wizLevel === 2" class="flag staff">Admin</div>
            <div v-if="character.unapproved" class="flag unapproved">Unapproved</div>
        </div>
    </div>
</template>

<script setup>

const props = defineProps({
    character: {type: Object, required: true},
    mode: {type: String, required: false, default: 'tag'},
    onClick: {type: Function, required: false}
});

const styleObject = {
    'backgroundImage': 'url(/a/' + props.character.name + '.png)'
};

const clicked = () => {
    if (props.onClick)
        props.onClick();
    else
        window.location = '/c/' + props.character.name;
};

</script>

<style scoped lang="scss">
@import 'resources/sass/variables';
// Shared in all modes
.avatar {
    position: relative;
    text-align: center;
    background-position-x: -120px !important;
    background-position-y: -70px !important;
}

.avatar i {
    position: absolute;
    left: 0;
    right: 0;
    bottom: 0;
    vertical-align: text-bottom;
}

.character-card {
    cursor: pointer;
    border: 1px solid $primary;
    box-shadow: 0 0 2px 2px $primary;
    display: inline-block;
}

.card-body {
    position: absolute;
    left: 1px;
    right: 1px;
    top: 1px;
    bottom: 1px;
    background: $content-background;
    border: 1px solid $content-background;
}


// Tag Mode
.character-card.mode-tag {
    width: 240px;
    height: 100px;

    .avatar {
        position: absolute;
        z-index: 1;
        left: 0;
        top: 0;
        bottom: 0;
        width: 192px;
        background: darken($content-background, 5%);
    }

    .name {
        position: absolute;
        z-index: 2;
        right: 2px;
        top: 2px;
        width: 128px;
        height: 40px;
        color: white;
        text-align: right;
        text-shadow: 2px 2px black;
        font-weight: bold;
        font-size: large;
        font-family: system-ui, "Segoe UI", sans-serif;
    }

    .level {
        position: absolute;
        z-index: 2;
        left: 198px;
        right: 2px;
        height: 32px;
        bottom: 2px;
        text-shadow: 2px 2px black;
        color: white;
        text-align: center;
    }

    .flag {
        position: absolute;
        z-index: 2;
        width: 98px;
        height: 22px;
        text-align: center;
        top: 36px;
        left: 8px;
        transform: translateX(-50%) rotate(-90deg);
    }

    .staff {
        color: black;
        background: $primary;
    }

    .unapproved {
        color: $secondary;
    }
}

</style>
