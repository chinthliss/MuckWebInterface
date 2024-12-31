import Channel from './channel.mjs';

const baseForm = {
    size: 5,
    cockCount: 1,
    cockSize: 5,
    ballCount: 2,
    ballSize: 5,
    clitCount: 1,
    clitSize: 5,
    cuntCount: 1,
    cuntSize: 5,
    breastCount: 2,
    breastSize: 5,
    gender: 'herm',
    sayVerb: 'says',
    noMastering: 0,
    noFunnel: 0,
    noReward: 0,
    noZap: 0,
    noNative: 0,
    noExtract: 0,
    bypassImmune: 0,
    tags: [],
    flags: [],
    lstats: [],
    kemo: [],
    chubby: [],
    dividers: [],
    color: [],
    powers: [],
    powersBonus: {}
}

export default class ChannelForms extends Channel {

    /**
     * @type object[]
     */
    formsCatalogue = [
        {
            ...baseForm,
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
            lstats: {waffle: ['legs', 'arms', 'tail']},
            kemo: ['arms', 'legs'],
            chubby: ['head', 'legs'],
            color: ['arms', 'head'],
            dividers: ['arm', 'leg', 'tail']
        },
        {
            ...baseForm,
            name: "Private Test Form 1",
            placement: ['something@somewhere'],
            private: 1,
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
            breastSize: 11
        },
        {
            ...baseForm,
            name: "Private Test Form 2",
            private: 1
        },
        {
            ...baseForm,
            name: "Placement-pending Test Form 1",
            placementNote: "Pending",
            staffonly: 1
        },
        {
            ...baseForm,
            name: "Form with Lstats 1",
            lstats: {stat: ['legs']}
        },
        {
            ...baseForm,
            name: "Form with flags 1",
            flags: {torso: ['flag1', 'flag2']}
        },
        {
            ...baseForm,
            name: "Form with tags 1",
            tags: ['tag1', 'tag2']
        },
        {
            ...baseForm,
            name: "Form with tags 2",
            tags: ['tag3', 'tag1']
        },
        {
            ...baseForm,
            name: "Form with a special note",
            specialNote: 'Test Special Note'
        },
        {
            ...baseForm,
            name: "Form with a special note 2",
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

    handlers = {
        'getFormDatabase': (connection, _data) => {
            this.sendFormDatabase(connection);
        },

        'getFormMasteryOf': (connection, data) => {
            this.sendFormMastery(connection, data);
        }
    }

    constructor(channelName, database) {
        super(channelName, database);
        for (let i = 0; i < 50; i++) {
            let nextForm = {
                ...baseForm,
                name: "Random Form " + i,
                size: Math.floor(Math.random() * 9) + 1,
                cockCount: Math.floor(Math.random() * 2),
                ballCount: Math.floor(Math.random() * 2),
                clitCount: Math.floor(Math.random() * 2),
                cuntCount: Math.floor(Math.random() * 2),
                breastCount: Math.floor(Math.random() * 2),
            };
            nextForm.cockSize = nextForm.cockCount > 0 ? Math.floor(Math.random() * 11) + 1 : 0;
            nextForm.ballSize = nextForm.ballCount > 0 ? Math.floor(Math.random() * 11) + 1 : 0;
            nextForm.clitSize = nextForm.clitCount > 0 ? Math.floor(Math.random() * 11) + 1 : 0;
            nextForm.cuntSize = nextForm.cuntCount > 0 ? Math.floor(Math.random() * 11) + 1 : 0;
            nextForm.breastSize = nextForm.breastCount > 0 ? Math.floor(Math.random() * 11) + 1 : 0;
            // Boringly simple gender tests here
            nextForm.gender = 'neuter';
            if (nextForm.cockCount > 0) nextForm.gender = 'male';
            if (nextForm.cuntCount > 0) nextForm.gender = 'female';
            if (nextForm.cockCount > 0 && nextForm.cuntCount > 0) nextForm.gender = 'herm';
            this.formsCatalogue.push(nextForm);
        }
    }

}
