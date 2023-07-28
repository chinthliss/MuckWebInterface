import Channel from './channel.mjs';
export default class ChannelCharacter extends Channel {

    messageReceived = (connection, message, data) => {
        switch (message) {
            case 'getCharacterProfile':
                const character = this.getDbrefFromDatabase(data);
                const characterName = data.characterName;
                const profile = {
                    name: characterName,
                    sex: character?.sex || 'Unknown',
                    species: character?.species || 'Unknown',
                    shortDescription: character?.shortDescription || '',
                    faction: '',
                    group: '',
                    height: '',
                    role: '',
                    whatIs: '',
                    equipment: []
                };
                this.sendMessageToConnection(connection, 'characterProfile', profile);
                break;
            default:
                console.log("Unhandled message: ", message);
        }
    }

}
