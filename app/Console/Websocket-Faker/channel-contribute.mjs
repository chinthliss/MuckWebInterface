import Channel from './channel.mjs';

export default class ChannelCharacter extends Channel {

    /**
     * @type object[]
     */
    formsCatalogue = [
        {
            name: 'Form in progress',
            owner: 1234,
            approved: false,
            review: false,
            revise: false,
            lastEdit: Math.floor(Date.now() / 1000) - 1,
            mass: 5,
            height: 5,
            say: 'wibble',
            oSay: 'wibbles',
            log: [
                {who: 'person', what: 'test', when: Math.floor(Date.now() / 1000) - 1}
            ]
        },
        {
            name: 'Approved Form',
            owner: 1234,
            approved: true,
            review: false,
            revise: false,
            lastEdit: Math.floor(Date.now() / 1000) - 10
        },
        {
            name: 'Form ready to review',
            owner: 1234,
            approved: false,
            review: true,
            revise: false,
            lastEdit: Math.floor(Date.now() / 1000) - 100
        },
        {
            name: 'Form in need of revision',
            owner: 1234,
            approved: false,
            review: false,
            revise: true,
            lastEdit: Math.floor(Date.now() / 1000) - 1000
        }
    ]

    sendFormList = (connection) => {
        this.sendMessageToConnection(connection, 'formList', this.formsCatalogue.length);
        for (let i = 0; i < this.formsCatalogue.length; i++) {
            this.sendMessageToConnection(connection, 'formListing', this.formsCatalogue[i]);
        }
    };

    sendForm = (connection, data) => {
        for (const form of this.formsCatalogue) {
            if (form.name === data) {
                this.sendMessageToConnection(connection, 'form', {
                    form: form, canEdit: !form.approved
                });
                return;
            }
        }
        this.sendMessageToConnection(connection, 'form', {error: 'No such form exists'});
    }

    createForm = (connection, formId) => {
        this.sendMessageToConnection(connection, 'createForm', {error: 'Not implemented yet'});
    }

    deleteForm = (connection, formId) => {
        this.sendMessageToConnection(connection, 'deleteForm', {error: 'Not implemented yet'});
    }

    handlers = {
        'getFormList': (connection, _data) => {
            this.sendFormList(connection);
        },
        'getForm': (connection, data) => {
            this.sendForm(connection, data);
        },
        'createForm': (connection, data) => {
            this.createForm(connection, data);
        },
        'deleteForm': (connection, data) => {
            this.deleteForm(connection, data);
        }

    }
}
