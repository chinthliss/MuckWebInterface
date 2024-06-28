import Channel from './channel.mjs';

export default class ChannelCharacter extends Channel {

    /**
     * @type object[]
     */
    formsCatalogue = [
        {
            _: {
                owner: 1234,
                approved: false,
                review: false,
                revise: false,
                lastEdit: Math.floor(Date.now() / 1000) - 1,
                notes: ['test', 'test2'],
                log: [
                    {who: 'person', what: 'test', when: Math.floor(Date.now() / 1000) - 1}
                ]
            },
            name: 'Form in progress',
            mass: 5,
            height: 5,
            say: 'wibble',
            oSay: 'wibbles',

            tags: 'tag anothertag',
            breastCount: 1,
            breastSize: 2,
            ballCount: 3,
            ballSize: 4,
            cockCount: 5,
            cockSize: 6,
            cuntCount: 7,
            cuntSize: 8,
            clitCount: 9,
            clitSize: 10,
            victory: ['This is a victory description.', 'With multiple lines!'],
            oVictory: ['This is a 3rd party victory description.'],
            defeat: ['This is a defeat description.'],
            skin: {
                transformation: 'This is a test transformation',
                description: 'This is a test description.'
            },
            head: {},
            torso: {},
            arms: {},
            legs: {},
            groin: {},
            ass: {}
        },
        {
            _: {
                owner: 1234,
                approved: true,
                review: false,
                revise: false,
                lastEdit: Math.floor(Date.now() / 1000) - 1,
            },
            name: 'Approved Form',
            skin: {},
            head: {},
            torso: {},
            arms: {},
            legs: {},
            groin: {},
            ass: {}
        },
        {
            _: {
                owner: 1234,
                approved: false,
                review: true,
                revise: false,
                lastEdit: Math.floor(Date.now() / 1000) - 1,
            },
            name: 'Form ready to review',
            skin: {},
            head: {},
            torso: {},
            arms: {},
            legs: {},
            groin: {},
            ass: {}
        },
        {
            _: {
                owner: 1234,
                approved: false,
                review: false,
                revise: true,
                lastEdit: Math.floor(Date.now() / 1000) - 1,
            },
            name: 'Form in need of revision',
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
                owner: form._.owner,
                approved: form._.approved,
                review: form._.review,
                revise: form._.revise,
                lastEdit: form._.lastEdit
            });
        }
    };

    sendForm = (connection, data) => {
        for (const form of this.formsCatalogue) {
            if (form.name === data) {
                this.sendMessageToConnection(connection, 'form', {
                    form: form,
                    canEdit: !form._.approved,
                    staff: 1
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
        },
        'updateForm': (connection, data) => {
            // We don't do anything with this in dev.
            // .. Unless it's one we're using to test rejections
            if (data?.propName === 'skin-transformation')
                this.sendMessageToConnection(connection, 'updateFormFailed', data);
        }

    }
}
