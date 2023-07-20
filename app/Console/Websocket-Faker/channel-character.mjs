import Channel from './channel.mjs';
export default class ChannelCharacter extends Channel {

    messageReceived = (connection, message, data) => {
        switch (message) {
            case 'getCharacterProfile':
                const characterName = data.characterName;
                const profile = {
                    name: characterName,
                    sex: '',
                    species: '',
                    shortDescription: '',
                    faction: '',
                    group: '',
                    height: '',
                    role: '',
                    whatIs: '',
                    equipment: ''
                };
                this.sendMessageToConnection(connection, 'characterProfile', profile);
                break;
            default:
                console.log("Unhandled message: ", message);
        }
    }

}
