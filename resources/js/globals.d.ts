// This line allows other code to know the vite global types are available
/// <reference types="vite/client" />

import MwiWebsocket from "muckwebinterface-websocket";
import {AxiosStatic} from "axios";

export {};

declare global {

    // These declarations are so we can set intended globals on Window.
    interface Window {
        bootstrap: any;
        axios: any;
        $: any;
        mwiWebsocket: any;
    }

    // Jquery declares itself as a global
    // var $: JQueryStatic;

    // Bootstrap declares itself as a global
    // var bootstrap: Object;

    var axios: AxiosStatic;

    var mwiWebsocket: typeof MwiWebsocket;
}
