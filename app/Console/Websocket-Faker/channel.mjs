export default class Channel {

    /**
     * @type {string};
     */
    #name;

    /**
     * Connections we're aware of
     * @type WebSocket[]
     */
    #connections

    constructor(channelName) {
        if (typeof channelName !== 'string' || channelName === '') throw "Attempt to create channel with no channelName specified.";
        this.#name = channelName;
        this.#connections = [];
    }

    #transmitMessageToConnection = (connection, message, data) => {
        const parsedMessage = 'MSG' + this.#name + ',' + message + ',' + (data ? JSON.stringify(data) : '');
        console.log(" >> " + parsedMessage);
        connection.send(parsedMessage + '\r\n');
    }

    sendMessageToConnection = (connection, message, data) => {
        this.#transmitMessageToConnection(connection, message, data);
    }

    sendMessageToChannel = (message, data) => {
        for (const connection of this.#connections) {
            this.#transmitMessageToConnection(connection, message, data);
        }
    }

    disconnect = (connection) => {
        this.#connections.filter((value) => {
            return (value !== connection);
        })
    }

    connect = (connection) => {
        this.#connections.push(connection);
        this.sendMessageToConnection(connection, 'connected', 1)
        this.sendMessageToConnection(connection, 'playerConnected', 1)
        this.sendMessageToConnection(connection, 'accountConnected', 1)
    }

    messageReceived = (connection, message, data) => {

    }

}
