import Channel from './channel.mjs';

export default class ChannelForms extends Channel {

    /**
     * @type object[]
     */
    formsCatalogue = [
        {
            name: "Complete Test Form",
            gender: 'herm',
            size: 1,
            cockCount: 2,
            cockSize: 3,
            ballCount: 4,
            ballSize: 5,
            clitCount: 6,
            clitSize: 7,
            cuntCount: 8,
            cuntSize: 9,
            breastCount: 10,
            breastSize: 11,
            tags: ['tag1', 'tag2'],
            flags: {arm: ['flag1', 'flag2'], legs: ['flag3'], head: ['flag4']},
            placementNote: 'Placement Note',
            specialNote: 'Special Note',
            powerNote: 'Power Note',
            sayVerb: 'says',
            noMastering: 1,
            noFunnel: 1,
            noReward: 1,
            noZap: 1,
            noNative: 1,
            noExtract: 1,
            bypassImmune: 1,
            placement: ['something@somewhere', 'somethingelse@somewhereelse'],
            holiday: 'July',
            powers: {'legs': ['A power']},
            powersBonus: {'2': ['A set power']},
            lstats: {waffle: ['legs', 'arms']},
            kemo: ['arms', 'legs'],
            chubby: ['arms', 'legs'],
            color: ['arms', 'legs'],
            dividers: ['arm', 'leg', 'tail']
        },
        {
            name: "Private Test Form 1",
            gender: 'maleherm',
            placement: ['something@somewhere'],
            private: 1
        },
        {
            name: "Private Test Form 2",
            gender: 'male',
            private: 1
        },
        {
            name: "Placement-pending Test Form 1",
            gender: 'male',
            placementNote: "Pending",
            staffonly: 1
        },
        {
            name: "Form with Lstats 1",
            gender: 'male',
            lstats: {stat: ['legs']}
        },
        {
            name: "Form with flags 1",
            gender: 'female',
            flags: {torso: ['flag1', 'flag2']}
        },
        {
            name: "Form with tags 1",
            gender: 'herm',
            tags: ['tag1', 'tag2']
        },
        {
            name: "Form with tags 2",
            gender: 'herm',
            tags: ['tag3', 'tag1']
        },
        {
            name: "Form with a special note",
            gender: 'shemale',
            specialNote: 'Test Special Note'
        },
        {
            name: "Form with a special note 2",
            gender: 'shemale',
            specialNote: 'Another special note'
        }
    ]

    formsMastery = {
        "testcharacter": {
            'Complete Test Form': 1,
            'Private Test Form 2': 2,
            'Not a real form': 1
        },
        "admincharacter": {
            'Private Test Form 2': 2,
            'Form with tags 1': 1
        }
    }

    sendFormDatabase = (connection) => {
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
            case 'getFormDatabase':
                this.sendFormDatabase(connection);
                break;
            case 'getFormMasteryOf':
                this.sendFormMastery(connection, data);
                break;
            default:
                console.log("Unhandled message: ", message);
        }
    }

    constructor(channelName, database) {
        super(channelName, database);
        for (let i = 0; i < 50; i++) {
            this.formsCatalogue.push({
                name: "Random Form " + i,
                gender: 'neuter'
            });
        }
    }

}
