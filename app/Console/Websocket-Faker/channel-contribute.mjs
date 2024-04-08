import Channel from './channel.mjs';

export default class ChannelCharacter extends Channel {

    /**
     * @type object[]
     */
    formsCatalogue = [
        {
            name: 'test form',
            owner: 1234,
            approved: false,
            // Ready for review
            review: false,
            // Marked as revised after review
            revise: false
        }
    ]

    sendFormList = (connection) => {
        this.sendMessageToConnection(connection, 'formList', this.formsCatalogue.length);
        for (let i = 0; i < this.formsCatalogue.length; i++) {
            this.sendMessageToConnection(connection, 'formListing', this.formsCatalogue[i]);
        }
    };


    handlers = {
        'getFormList': (connection, data) => {
                this.sendFormList(connection);
        }
    }
}
