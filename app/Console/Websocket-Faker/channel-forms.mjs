import Channel from './channel.mjs';

export default class ChannelForms extends Channel {

    formsCatalogue = {
    }

    sendFormCatalogue = (connection) => {
        this.sendMessageToConnection(connection, 'formDatabase', this.formsCatalogue);
    };

    sendFormMastery = (connection, who) => {
        const response = {
            who: who,
            error: "Not Implemented Yet"
        };
        this.sendMessageToConnection(connection, 'formDatabase', response);
    }

    messageReceived = (connection, message, data) => {
        switch (message) {
            case 'getFormCatalogue':
                this.sendFormCatalogue(connection);
                break;
            case 'getFormMasteryOf':
                this.sendFormMastery(connection, data);
                break;
            default:
                console.log("Unhandled message: ", message);
        }
    }

}
