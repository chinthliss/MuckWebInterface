import Channel from './channel.mjs';

export default class ChannelCharacter extends Channel {

    /**
     * @type object[]
     */
    formsCatalogue = [
        { name: 'Form in progress',  owner: 1234,  approved: false, review: false, revise: false },
        { name: 'Approved Form',  owner: 1234,  approved: true, review: false, revise: false },
        { name: 'Form ready to review',  owner: 1234,  approved: false, review: true, revise: false },
        { name: 'Form in need of revision',  owner: 1234,  approved: false, review: false, revise: true },
    ]

    sendFormList = (connection) => {
        this.sendMessageToConnection(connection, 'formList', this.formsCatalogue.length);
        for (let i = 0; i < this.formsCatalogue.length; i++) {
            this.sendMessageToConnection(connection, 'formListing', this.formsCatalogue[i]);
        }
    };


    handlers = {
        'getFormList': (connection, _data) => {
                this.sendFormList(connection);
        }
    }
}
