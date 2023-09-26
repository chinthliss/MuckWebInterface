<script setup lang="ts">

import {ref, Ref, computed} from "vue";
import Spinner from "./Spinner.vue";
import {ansiToHtml} from "../formatting";

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

const previousPage = computed<null | string>(() => {
    const lastIndex = page.value.title.lastIndexOf('/');
    if (lastIndex === -1) return null;
    return page.value.title.slice(0, lastIndex);
});

const currentPage = computed<null | string>(() => {
    const lastIndex = page.value.title.lastIndexOf('/');
    if (lastIndex === -1) return page.value.title;
    return page.value.title.slice(lastIndex + 1);
});

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
            <template v-if="page.title">
                <span v-if="previousPage" class="text-muted">{{ previousPage }}/</span>{{ currentPage }}
            </template>
            <template v-else>Contents</template>
        </h1>

        <spinner v-if="loadingPage"></spinner>
        <div v-else-if="pageIsInvalid">
            Couldn't find a page with the requested topic.
        </div>
        <div v-else>
            <div class="mt-2" v-for="line in page.content" v-html="ansiToHtml(line)"></div>
        </div>

        <div>
            <div v-for="child in page.contains" class="mt-2">
                <a :href="rootUrl + '/' + page.title + '/' + child " @click.prevent="loadPage(page.title + '/' + child)">{{ child }}</a>
            </div>
            <div v-if="previousPage" class="mt-2">
                <a :href="rootUrl + '/' + previousPage" @click.prevent="loadPage(previousPage)">Back to: {{ previousPage }}</a>
            </div>
            <div v-if="page.title" class="mt-2">
                <a :href="rootUrl" @click.prevent="loadPage('')">Return to Contents</a>
            </div>
        </div>

    </div>
</template>

<style scoped>

</style>
