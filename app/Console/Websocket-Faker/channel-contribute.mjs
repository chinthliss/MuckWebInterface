import Channel from './channel.mjs';

export default class ChannelCharacter extends Channel {

    /**
     * @type object[]
     */
    formsCatalogue = [
        {
            name: 'Form in progress',
            owner: 1234,
            _approved: false,
            _review: false,
            _revise: false,
            _editedAt: Math.floor(Date.now() / 1000) - 1,
            _notes: ['test', 'test2'],
            _log: [
                {name: 'Someone', what: 'test', message: ['Test'], when: Math.floor(Date.now() / 1000) - 100},
                {name: 'Person', what: 'test', message: ['Words', 'Multiline'], when: Math.floor(Date.now() / 1000) - 10},
                {name: 'Another', what: 'no message', when: Math.floor(Date.now() / 1000) - 10}
            ],
            _viewers: 'Test viewers entry',
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
            skinTransformation: 'This is a test transformation',
            skinDescription: 'This is a test description.'
        },
        {
            name: 'Form that will be rejected',
            owner: 1234,
            _approved: false,
            _published: false,
            _review: false,
            _revise: false,
            _editedAt: Math.floor(Date.now() / 1000) - 1
        },
        {
            name: 'Approved Form',
            owner: 1234,
            _approved: true,
            _published: true,
            _review: false,
            _revise: false,
            _editedAt: Math.floor(Date.now() / 1000) - 1
        },
        {
            name: 'Form ready to review',
            owner: 1234,
            _approved: false,
            _review: true,
            _revise: false,
            _editedAt: Math.floor(Date.now() / 1000) - 1
        },
        {
            name: 'Form in need of revision',
            owner: 1234,
            _approved: false,
            _review: false,
            _revise: true,
            _editedAt: Math.floor(Date.now() / 1000) - 1,
        },
        {
            name: 'Deleted Form',
            owner: 1234,
            _approved: false,
            _review: false,
            _revise: false,
            _deletedAt: Math.floor(Date.now() / 1000) - 2,
            _editedAt: Math.floor(Date.now() / 1000) - 5,
        }
    ]

    sendFormList = (connection) => {
        this.sendMessageToConnection(connection, 'formList', this.formsCatalogue.length);
        for (const form of this.formsCatalogue) {
            this.sendMessageToConnection(connection, 'formListing', {
                name: form.name,
                account: form.owner,
                credit: 'name',
                approved: form._approved ? 1 : 0,
                published: form._published ? 1 : 0,
                review: form._review ? 1 : 0,
                revise: form._revise ? 1 : 0,
                lastEdit: form._editedAt,
                deleted: form._deletedAt ?? 0
            });
        }
    };

    sendForm = (connection, data) => {
        for (const form of this.formsCatalogue) {
            if (form.name === data) {
                this.sendMessageToConnection(connection, 'form', {
                    form: form,
                    canEdit: !form._approved,
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

    // Data is expected to include {form, action, notes}
    updateFormState = (connection, data) => {
        if (data.form === 'Form that will be rejected')
            this.sendMessageToConnection(connection, 'formStateUpdate', {error: 'Rejected!'});
        else
            this.sendMessageToConnection(connection, 'formStateUpdate', {form: data.form});
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
        'updateFormState': (connection, data) => {
            this.updateFormState(connection, data);
        },
        'updateForm': (connection, data) => {
            // We don't do anything with this in dev.
            // .. Unless it's one we're using to test rejections
            if (data?.propName === 'skin-transformation')
                this.sendMessageToConnection(connection, 'updateFormFailed', data);
        },
        'approveForm': (connection, data) => {
            // We don't do anything with this in dev.
            this.sendMessageToConnection(connection, 'formApproved', data);
        },
        'previewForm': (connection, data) => {
            this.sendMessageToConnection(connection, 'formPreview', {
                form: data.form,
                content: 'FORM-PREVIEW'
            });
        },
        'previewFormMessage': (connection, data) => {
            this.sendMessageToConnection(connection, 'formMessagePreview', {
                form: data.form,
                message: data.message,
                content: [
                    data.message.toUpperCase() + '-PREVIEW-FIRST-LINE',
                    data.message.toUpperCase() + '-PREVIEW-SECOND-LINE',
                ]
            });
        },
        'getFormAsPublished': (connection, data) => {
            for (const form of this.formsCatalogue) {
                if (form.name === data) {
                    const clonedForm = {...form};
                    clonedForm.skinDescription = "This is a different description";
                    this.sendMessageToConnection(connection, 'publishedForm', clonedForm);
                }
            }
        }
    }
}
