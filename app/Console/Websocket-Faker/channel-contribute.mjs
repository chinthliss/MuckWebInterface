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
            ],
            tags: 'tag anothertag',
            breastCount:1,
            breastSize:2,
            ballCount:3,
            ballSize:4,
            cockCount:5,
            cockSize:6,
            cuntCount:7,
            cuntSize:8,
            clitCount:9,
            clitSize:10,
            skin: {},
            head: {},
            torso: {},
            arms: {},
            legs: {},
            groin: {},
            ass: {}
        },
        {
            name: 'Approved Form',
            owner: 1234,
            approved: true,
            review: false,
            revise: false,
            lastEdit: Math.floor(Date.now() / 1000) - 10,
            skin: {},
            head: {},
            torso: {},
            arms: {},
            legs: {},
            groin: {},
            ass: {}
        },
        {
            name: 'Form ready to review',
            owner: 1234,
            approved: false,
            review: true,
            revise: false,
            lastEdit: Math.floor(Date.now() / 1000) - 100,
            skin: {},
            head: {},
            torso: {},
            arms: {},
            legs: {},
            groin: {},
            ass: {}
        },
        {
            name: 'Form in need of revision',
            owner: 1234,
            approved: false,
            review: false,
            revise: true,
            lastEdit: Math.floor(Date.now() / 1000) - 1000,
            skin: {},
            head: {},
            torso: {},
            arms: {},
            legs: {},
            groin: {},
            ass: {}

        }
    ]

    sendFormList = (connection) => {
        this.sendMessageToConnection(connection, 'formList', this.formsCatalogue.length);
        for (const form of this.formsCatalogue) {
            this.sendMessageToConnection(connection, 'formListing', {
                name: form.name,
                owner: form.owner,
                approved: form.approved,
                review: form.review,
                revise: form.revise,
                lastEdit: form.lastEdit
            });
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
