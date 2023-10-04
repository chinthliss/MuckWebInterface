import Channel from './channel.mjs';

export default class ChannelForms extends Channel {

    formsCatalogue = [
        {
            name: "Test Form 1"

        },
        {
            name: "Private Test Form 1",
            placement: ['something@somewhere'],
            private: 1
        },
        {
            name: "Private Test Form 2",
            private: 1
        },
        {
            name: "Placement-pending Test Form 1",
            placementNote: "Pending",
            staffonly: 1
        },
        {
            name: "Form with Lstats 1",
            lstats: ['legs']
        },
        {
            name: "Form with flags 1",
            flags: ['flag1', 'flag2']
        },
        {
            name: "Form with tags 1",
            tags: ['tag1', 'tag2']
        }

    ]

    formsMastery = {
        "testcharacter": {
            'Test Form 1': 1,
            'Private Test Form 2': 2,
            'Not a real form': 1
        }
    }

    sendFormCatalogue = (connection) => {
        this.sendMessageToConnection(connection, 'formDatabase', this.formsCatalogue.length);
        for (let i = 0; i < this.formsCatalogue.length; i++) {
            this.sendMessageToConnection(connection, 'formListing', this.formsCatalogue[i]);
        }
    };

    sendFormMastery = (connection, who) => {
        const mastery = this.formsMastery[who.toLowerCase()];
        const response = {
            who: who
        };

        if (!mastery) {
            response.error = "Couldn't find someone with that name.";
        } else {
            response.forms = mastery;
        }

        if (who.toLowerCase() === 'refused') {
            response.error = "You don't have permission to view.";
        }

        this.sendMessageToConnection(connection, 'mastery', response);
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
