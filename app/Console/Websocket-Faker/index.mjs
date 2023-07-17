/**
 * This is an ugly muck websocket emulator to allow local development.
 */
import { WebSocketServer } from 'ws';
import Channel from "./channel.mjs";

console.log("Creating the server.");
const server = new WebSocketServer({ port: 8001 });

/**
 * @type {Map<WebSocket, Array>}
 */
const connections = new Map();

/**
 * @type {Map<string, Channel>}
 */
const channels = new Map();

const processIncomingMessage = (connection, unprocessedMessage) => {
    const [channelName, message, data] = unprocessedMessage.split(',', 3);
    console.log(" << " + channelName + "." + message + ": " + data);
    const channel = this.channels[channelName];
    channel.messageReceived(connection, message, data);
}

const processIncomingSystemMessage = (connection, unprocessedMessage) => {
    let [message, data] = unprocessedMessage.split(',', 2);
    console.log(" << SYS." + message + ": " + data);
    switch (message) {
        case 'joinChannels':
            let channelsToJoin = JSON.parse(data);
            if (typeof(channelsToJoin) === 'string') channelsToJoin = [channelsToJoin];
            for (const channel of channelsToJoin) {
                if (!channels.has(channel)) {
                    console.log("Creating channel: " + channel);
                    channels[channel] = new Channel(channel);
                }
                channels[channel].connect(connection);
                console.log(`Connection joined channel '${channel}'`);
            }
            break;
        case 'test':
            console.log("Mwi-Websocket Test message received. Data=", data);
            break;
        default:
            console.error("Unrecognized system message received: " + message);
    }
}

server.on('connection', (connection, request) => {
    console.log("Connection - new from: ", request.socket.remoteAddress);
    const data = {};
    connections.set(connection, data);
    connection.send('welcome\r\n');

    connection.on('message', (data) => {
        //console.debug(" << %s", data);
        const message = data.toString();

        if (message.startsWith('auth FAKEWEBSOCKETAUTHTOKEN')) {
            connection.send('accepted 1,1234,Test');
        }

        if (message.startsWith('MSG')) processIncomingMessage(connection, message.slice(3));
        if (message.startsWith('SYS')) processIncomingSystemMessage(connection, message.slice(3));


    });

    connection.on('close', () => {
        console.log("Connection - dropped from: ", request.socket.remoteAddress);
        for (const [, channel] of channels) {
            channel.disconnect(connection);
        }
        connections.delete(connection);
    });

});

console.log("Server up and running. Use CTRL + C to close.");

