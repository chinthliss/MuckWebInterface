import Channel from './channel.mjs';

export default class ChannelHelp extends Channel {

    helpCatalogue = {
        "": {
            content: ["Root Page"],
            contains: ["Test", "Normal"]
        },
        "Test": {
            content: ["Test"],
            contains: ["Test"]
        },
        "Normal": {
            content: ["This is a page with some actual content.", "And multiple lines at that.", "And this line has [1;33mAnsi[0m!", "This line has > ' < special characters."]
        },
        "Test/Test": {
            content: ["Still a test!"]
        }
    }

    getHelpResponse = (page) => {
        const record = this.helpCatalogue[page];
        if (!record) return "NOTFOUND";

        let helpResponse = {
            'title': page,
        };

        if (record.content) helpResponse.content = record.content;

        if (record.contains) helpResponse.contains = record.contains;

        return helpResponse;

    }

    messageReceived = (connection, message, data) => {
        switch (message) {
            case 'getHelp':
                console.log("Help request received for: " + data);
                this.sendMessageToConnection(connection, 'help', this.getHelpResponse(data));
                break;
            default:
                console.log("Unhandled message: ", message);
        }
    }

}
