import Channel from './channel.mjs';

const perksCatalogue = [
    {name: 'Perk 1', description: 'Description.', tags: ['vanity'], cost: 3},
    {name: 'Perk 2', description: 'Another description.', excludes: ['Perk 3'], cost: 1, tags: []},
    {name: 'Perk 3', description: 'Third description', excludes: ['Perk 2'], tags: ['vanity', 'waffle'], cost: 2},
    {name: 'Perk 4', description: 'An owned perk with a slightly longer description', tags: [], excludes: ['Perk 5']},
    {name: 'Perk 5', description: 'Perk excluded by owned perk', tags: ['vanilla', 'vanity']},
    {name: 'Perk 6', description: 'Second owned perk with notes.', tags: [], cost: 2}
];

const perksOwned = [
    {name: 'Perk 4', notes: ''},
    {name: 'Perk 6', notes: 'Test notes!'}
]

const dedicationsCatalogue = [
    {name: 'Test Dedication 1', cost: 50, description: 'Test Description'}
];

export default class ChannelCharacter extends Channel {

    sendPerkStatus = (connection) => {
        this.sendMessageToConnection(connection, 'perkStatus', {
            'perkPoints': 2,
            'vanityPoints': 1,
            'perkPointCost': 10,
            'owned': perksOwned
        });
    }

    sendCustomFields = (connection, character) => {
        const customFields = character.properties?.custom || [];
        this.sendMessageToConnection(connection, 'customFields', customFields);
    }

    handlers = {
        'getCharacterProfile': (connection, data) => {
            const character = this.getDbrefFromDatabase(data);
            if (!character) console.log("No character specified?");
            const profile = {
                name: character.name,
                level: character.properties.level,
                sex: character.properties?.sex || 'Unknown',
                species: character.properties?.species || 'Unknown',
                shortDescription: character.properties?.shortDescription || '',
                faction: character.properties?.faction || '',
                group: character.properties?.group || '',
                height: character.properties?.height || '',
                role: character.properties?.role || '',
                whatIs: character.properties?.whatIs || '',
                birthday: character.properties?.birthday || ''
            };
            this.sendMessageToConnection(connection, 'profile', profile);

            const views = character.properties?.views || [];
            this.sendMessageToConnection(connection, 'views', views);

            this.sendCustomFields(connection, character);

            const equipment = character.properties?.equipment || [];
            this.sendMessageToConnection(connection, 'equipment', equipment);

            const badges = character.properties?.badges || [];
            this.sendMessageToConnection(connection, 'badges', badges.count);

            for (const badge of badges) {
                this.sendMessageToConnection(connection, 'badge', badge);
            }
        },

        'bootPerks': (connection, _data) => {
            // Send perk list first
            this.sendMessageToConnection(connection, 'perksCatalogue', perksCatalogue.length);
            for (let i = 0; i < perksCatalogue.length; i++) {
                this.sendMessageToConnection(connection, 'perk', perksCatalogue[i]);
            }
            // Then send status
            this.sendPerkStatus(connection);
        },

        'buyPerk': (connection, data) => {
            perksOwned.push({
                name: data,
                notes: ''
            });
            this.sendPerkStatus(connection);
        },

        'updatePerkNotes': (connection, data) => {
            const perk = perksOwned.find(item => item?.name === data?.perk);
            if (perk) {
                perk.notes = data.notes;
                this.sendPerkStatus(connection);
            }
        },

        'bootCharacterEdit': (connection, data) => {
            const character = this.getDbrefFromDatabase(data);
            if (!character) throw("No character specified?");
            // Send short description
            this.sendMessageToConnection(connection, 'shortDescription', "");
            // Then send custom fields
            this.sendCustomFields(connection, character);
        },

        'updateShortDescription': (connection, data) => {
            setTimeout(() => {
                this.sendMessageToConnection(connection, 'shortDescription', data)
            }, 1000);
        },

        'addCustomField': (connection, data) => {
            const character = data.dbref ? this.getDbrefFromDatabase(data.dbref) : null;
            if (!character) console.log("No character specified?");
            if (!character.properties) character.properties = [];
            if (!character.properties.custom) character.properties.custom = [];
            character.properties.custom.push({'field': data.name, 'value': data.value});
            this.sendCustomFields(connection, character);
        },

        'editCustomField': (connection, data) => {
            const character = data.dbref ? this.getDbrefFromDatabase(data.dbref) : null;
            if (!character) throw("No character specified?");
            let customToEdit = null;
            if (character?.properties?.custom) {
                for (const customField of character.properties.custom) {
                    if (customField.field === data.originalName) customToEdit = customField;
                }
            }
            if (customToEdit) {
                customToEdit.field = data.name;
                customToEdit.value = data.value;
            } else console.log("Couldn't find custom to change!");
            this.sendCustomFields(connection, character);
        },

        'deleteCustomField': (connection, data) => {
            const character = data.dbref ? this.getDbrefFromDatabase(data.dbref) : null;
            if (!character) throw("No character specified?");
            if (character?.properties?.custom) {
                for (let i = 0; i < character.properties.custom.length; i++) {
                    if (character.properties.custom[i].field === data.name) character.properties.custom.splice(i, 1);
                }
            }
            this.sendCustomFields(connection, character);
        },

        'buyPerkPoint': (connection, _data) => {
            this.sendNotificationToConnection(connection, "Test fault!");
            // this.sendPerkStatus(connection);
        },

        /**
         * Dedications
         */
        'getDedicationList': (connection, _data) => {
            this.sendMessageToConnection(connection, 'dedicationList', dedicationsCatalogue.length);
            for (const dedication of dedicationsCatalogue) {
                this.sendMessageToConnection(connection, 'dedicationListing', dedication);
            }
        }

    }
}


