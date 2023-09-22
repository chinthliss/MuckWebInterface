<script setup lang="ts">

import {ref, Ref} from "vue";

const props = defineProps<{
    startingPage: string,
    rootUrl: string
}>();

type Page = {
    title: string,
    content: string[] | null,
    contains: string[] | null
}

const page: Ref<Page> = ref({
    title: props.startingPage,
    content: null,
    contains: null
} as Page);
const channel = mwiWebsocket.channel('help');
const loadingPage = ref(false);
const pageIsInvalid = ref(false);


const loadPage = (wantedPage: string, firstLoad: boolean = false) => {
    if (wantedPage.startsWith('/')) wantedPage = wantedPage.slice(1);
    loadingPage.value = true;
    if (firstLoad) {
        //Replace starting state
        history.replaceState({
            pageIsInvalid: pageIsInvalid.value,
            page: JSON.stringify(page.value)
        }, '', props.rootUrl + '/' + page.value.title);
    } else {
        //Push a blank state that we'll update after the load
        history.pushState({}, '', props.rootUrl + '/' + wantedPage);
    }
    pageIsInvalid.value = false;
    page.value.title = wantedPage;
    page.value.content = null;
    page.value.contains = null;

    channel.send('getHelp', wantedPage);
}

window.addEventListener('popstate', (event: PopStateEvent) => {
    console.log(event.state);
    pageIsInvalid.value = event.state.pageIsInvalid;
    page.value = JSON.parse(event.state.page);
});

channel.on('help', (data: Page | string) => {
    loadingPage.value = false;
    if (data === 'NOTFOUND') {
        pageIsInvalid.value = true;
        return;
    }
    page.value = data as Page;
    history.replaceState({
        pageIsInvalid: pageIsInvalid.value,
        page: JSON.stringify(page.value)
    }, '', props.rootUrl + '/' + page.value.title);

});

loadPage(page.value.title, true);

</script>

<template>
    <div class="container">
        <h1>
            <template v-if="page.title">{{ page.title }}</template>
            <template v-else>Contents</template>
        </h1>

        <div v-if="loadingPage">
            Loading
        </div>
        <div v-else-if="pageIsInvalid">
            Couldn't find a page with the requested topic.
        </div>
        <div v-else>
            <div v-for="line in page.content">
                {{ line }}
            </div>
            <div v-for="child in page.contains">
                <a class="pt-2" @click="loadPage(page.title + '/' + child)">{{ child }}</a>
            </div>
        </div>

        <div>
            <button class="btn btn-secondary mt-2" @click="loadPage('')">
                Return to contents
            </button>
        </div>
    </div>
</template>

<style scoped>

</style>
