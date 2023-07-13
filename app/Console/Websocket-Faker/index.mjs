/**
 * This is an ugly muck websocket emulator to allow local development
 */
import { WebSocketServer } from 'ws';

console.log("Creating the server.");
const server = new WebSocketServer({ port: 8001 });

const connections = new Map();

// Every line from the muck ends with \r\n so we have to fake this
const expectedLineEnding = String.fromCharCode(13) + String.fromCharCode(10);

// Mostly a utility function to ensure the message is logged and has the line ending
const transmitMessageToConnection = (connection, message) => {
    console.debug(" >> " + message);
    connection.send(message + expectedLineEnding);
}

const sendChannelMessage = (connection, channel, message, data) => {
    transmitMessageToConnection('MSG' + channel + ',' + message + ',' + (data ? JSON.stringify(data) : '') );
}

const sendSystemMessage = (connection, message, data) => {
    transmitMessageToConnection('SYS' + message + ',' + (data ? JSON.stringify(data) : '') );
}

const processIncomingMessage = (connection, unprocessedMessage) => {
    let [channel, message, data] = unprocessedMessage.split(',', 3);
    console.log(" << " + channel + "." + message + ": " + data);
}

const processIncomingSystemMessage = (connection, unprocessedMessage) => {
    let [message, data] = unprocessedMessage.split(',', 2);
    console.log(" << SYS." + message + ": " + data);
}

server.on('connection', (connection, request) => {
    console.log("Connection - new from: ", request.socket.remoteAddress);
    const data = {};
    connections.set(connection, data);
    transmitMessageToConnection(connection, 'welcome');

    connection.on('message', (data) => {
        console.debug(" << %s", data);
        const message = data.toString();

        if (message.startsWith('auth FAKEWEBSOCKETAUTHTOKEN')) {
            transmitMessageToConnection(connection, 'accepted 1,1234,Test');
        }

        if (message.startsWith('MSG')) processIncomingMessage(connection, message.slice(3));
        if (message.startsWith('SYS')) processIncomingSystemMessage(connection, message.slice(3));


    });

    connection.on('close', () => {
        console.log("Connection - dropped from: ", request.socket.remoteAddress);
        connections.delete(connection);
    });

});

console.log("Server up and running. Use CTRL + C to close.");

