export default class Channel {

    /**
     * @type {string};
     */
    #name;

    /**
     * Connections we're aware of
     * @type Map
     */
    #connections

    constructor(channelName) {
        if (typeof channelName !== 'string' || channelName === '') throw "Attempt to create channel with no channelName specified.";
        this.#name = channelName;
        this.#connections = new Map();
    }
}
