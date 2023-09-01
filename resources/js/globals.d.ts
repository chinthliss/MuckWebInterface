// This line allows other code to know the vite global types are available
/// <reference types="vite/client" />

import MwiWebsocket from "muckwebinterface-websocket";
import {AxiosStatic} from "axios";

export {};

declare global {

    // These declarations are so we can set them on Window.
    interface Window {
        bootstrap: any;
        axios: any;
        $: JQueryStatic;
        mwiWebsocket: typeof MwiWebsocket;
    }

    // These are delcared in their own types
    // var bootstrap: object;
    // var $: JQueryStatic;
    var axios: AxiosStatic;

    var mwiWebsocket: typeof MwiWebsocket;
}
