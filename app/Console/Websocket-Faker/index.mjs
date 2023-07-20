/**
 * This is an ugly muck websocket emulator to allow local development.
 */
import {WebSocketServer} from 'ws';
import Channel from "./channel.mjs";
import ChannelCharacter from "./channel-character.mjs";

// Any channels configured in here will use a specialized faker
const channelOverrides = {
    'character': ChannelCharacter
}

console.log("Creating the server.");
const server = new WebSocketServer({port: 8001});

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
            if (typeof (channelsToJoin) === 'string') channelsToJoin = [channelsToJoin];
            for (const channel of channelsToJoin) {
                if (!channels.has(channel)) {
                    console.log("Creating channel: " + channel);
                    if (typeof channelOverrides[channel] !== 'undefined')
                        channels[channel] = new channelOverrides[channel]();
                    else
                        channels[channel] = new Channel(channel);
                }
                const connectionData = connections.get(connection);
                channels[channel].connect(connection, connectionData.accountId, connectionData.characterDbref);
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

    connection.on('message', (buffer) => {
        //console.debug(" << %s", data);
        const message = buffer.toString();

        if (message.startsWith('auth FAKEWEBSOCKETAUTHTOKEN')) {
            let [, accountId, characterDbref = -1, characterName = ''] = message.split(' ')[1].split(':');
            connection.send(`accepted ${accountId},${characterDbref},${characterName}\r\n`);
            data.accountId = accountId;
            data.characterDbref = characterDbref;
            data.characterName = characterName;
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

