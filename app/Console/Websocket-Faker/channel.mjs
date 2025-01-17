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

    /**
     * Reference to the faker database, if required
     * @type Object[]
     */
    #database

    /**
     * The message this channel will handle
     * @type {Object.<string, function>}
     */
    handlers = {}

    constructor(channelName, database) {
        if (typeof channelName !== 'string' || channelName === '') throw "Attempt to create channel with no channelName specified.";
        this.#name = channelName;
        this.#connections = [];
        this.#database = database;
    }

    #transmitChannelMessageToConnection(connection, message, data) {
        const parsedJson = (typeof data !== 'undefined' ? JSON.stringify(data) : '');
        const parsedMessage = 'MSG' + this.#name + ',' + message + ',' + parsedJson;
        console.log(" >> " + this.#name + '.' + message + ': ' + parsedJson);
        connection.send(parsedMessage + '\r\n');
    }

    #transmitSystemMessageToConnection(connection, message, data) {
        const parsedMessage = 'SYS' + message + ',' + (data ? JSON.stringify(data) : '');
        console.log(" >> " + parsedMessage);
        connection.send(parsedMessage + '\r\n');
    }

    sendMessageToConnection(connection, message, data) {
        this.#transmitChannelMessageToConnection(connection, message, data);
    }

    sendSystemMessageToConnection(connection, message, data) {
        this.#transmitSystemMessageToConnection(connection, message, data);
    }

    sendMessageToChannel(message, data) {
        for (const connection of this.#connections) {
            this.#transmitChannelMessageToConnection(connection, message, data);
        }
    }

    sendNotificationToConnection(connection, notice) {
        for (const connection of this.#connections) {
            this.#transmitSystemMessageToConnection(connection, 'notice', notice);
        }
    }

    disconnect(connection) {
        this.#connections.filter((value) => {
            return (value !== connection);
        })
    }

    connect(connection, accountId = null, dbref = null) {
        this.#connections.push(connection);
        this.sendSystemMessageToConnection(connection, 'joinedChannel', this.#name);
        this.sendMessageToConnection(connection, 'connected', 1)
        this.sendMessageToConnection(connection, 'playerConnected', 1)
        this.sendMessageToConnection(connection, 'accountConnected', 1)
        this.connectionEnteredChannel(connection);
        this.playerEnteredChannel(connection, dbref);
        this.accountEnteredChannel(connection, accountId);
    }

    connectionEnteredChannel(connection) {

    }

    playerEnteredChannel(connection, dbref) {

    }

    accountEnteredChannel(connection, account) {

    }

    messageReceived(connection, message, data) {
        if (this.handlers[message]) this.handlers[message](connection, data);
        else console.log("Unhandled message: ", message);
    }

    getDbrefFromDatabase(dbref) {
        if (typeof dbref === 'string') dbref = parseInt(dbref);
        for (const candidate of this.#database) {
            if (candidate.dbref === dbref) return candidate;
        }
        return null;
    }

}
