import { WebSocketServer } from 'ws';

console.log("Creating the server.");
const server = new WebSocketServer({ port: 8001 });

const connections = new Map();

server.on('connection', (connection, request) => {
    console.log("Accepted new connection: ", request.socket.remoteAddress);
    const data = {};
    connections.set(connection, data);

    connection.on('close', () => {
        connections.delete(connection);
    });

});

console.log("Server up and running. Use CTRL + C to close.");

