import Channel from './channel.mjs';

const perksCatalogue = [
    {name: 'Perk 1', description: 'Description.', tags: ['vanity'], cost: 3},
    {name: 'Perk 2', description: 'Another description.', excludes: ['Perk 3'], cost: 1, tags: []},
    {name: 'Perk 3', description: 'Third description', excludes: ['Perk 2'], tags: ['vanity', 'waffle'], cost: 2},
    {name: 'Perk 4', description: 'An owned perk with a slightly longer description',  tags: [], excludes: ['Perk 5']},
    {name: 'Perk 5', description: 'Perk excluded by owned perk', tags: ['vanilla', 'vanity']},
    {name: 'Perk 6', description: 'Second owned perk with notes.',  tags: [], cost: 2}
];

const perksOwned = [
    {name: 'Perk 4', notes: ''},
    {name: 'Perk 6', notes: 'Test notes!'}
]

export default class ChannelCharacter extends Channel {

    sendPerkStatus = (connection) => {
        this.sendMessageToConnection(connection, 'perkStatus', {
            'perkPoints': 2,
            'vanityPoints': 1,
            'owned': perksOwned
        });
    }

    messageReceived = (connection, message, data) => {
        switch (message) {
            case 'getCharacterProfile':
                const character = this.getDbrefFromDatabase(data);
                if (!character) throw "No character found. This should have already been blocked by a 404.";
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
                this.sendMessageToConnection(connection, 'characterProfileCore', profile);

                const views = character.properties?.views || [];
                this.sendMessageToConnection(connection, 'characterProfileViews', views);

                const pinfo = character.properties?.pinfo || [];
                this.sendMessageToConnection(connection, 'characterProfilePinfo', pinfo);

                const equipment = character.properties?.equipment || [];
                this.sendMessageToConnection(connection, 'characterProfileEquipment', equipment);

                const badges = character.properties?.badges || [];
                this.sendMessageToConnection(connection, 'characterProfileBadges', badges.count);

                for (const badge of badges) {
                    this.sendMessageToConnection(connection, 'characterProfileBadge', badge);
                }

                break;

            case 'bootPerks':
                // Send perk list first
                this.sendMessageToConnection(connection, 'perksCatalogue', perksCatalogue.length);
                for (let i = 0; i < perksCatalogue.length; i++) {
                    this.sendMessageToConnection(connection, 'perk', perksCatalogue[i]);
                }
                // Then send status
                this.sendPerkStatus(connection);
                break;

            case 'buyPerk':
                perksOwned.push({
                    name: data,
                    notes: ''
                });
                this.sendPerkStatus(connection);
                break;

            case 'updatePerkNotes':
                console.log(data);
                const perk = perksOwned.find(item => item?.name === data?.perk);
                if (perk) {
                    perk.notes = data.notes;
                    this.sendPerkStatus(connection);
                }
                break;

            default:
                console.log("Unhandled message: ", message);
        }
    }

}
