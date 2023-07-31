import Channel from './channel.mjs';

export default class ChannelCharacter extends Channel {

    messageReceived = (connection, message, data) => {
        switch (message) {
            case 'getCharacterProfile':
                const character = this.getDbrefFromDatabase(data);
                if (!character) throw "No character found. This should have already been blocked by a 404.";
                const profile = {
                    name: character.name,
                    sex: character.properties?.sex || 'Unknown',
                    species: character.properties?.species || 'Unknown',
                    shortDescription: character.properties?.shortDescription || '',
                    faction: character.properties?.faction || '',
                    group: character.properties?.group || '',
                    height: character.properties?.height || '',
                    role: character.properties?.role || '',
                    whatIs: character.properties?.whatIs || '',
                };
                this.sendMessageToConnection(connection, 'characterProfileCore', profile);

                const views = character.properties?.views || [];
                this.sendMessageToConnection(connection, 'characterProfileViews', views);

                const pinfo = character.properties?.pinfo || [];
                this.sendMessageToConnection(connection, 'characterProfilePinfo', pinfo);

                const equipment = character.properties?.equipment || [];
                this.sendMessageToConnection(connection, 'characterProfileEquipment', equipment);

                const badges = character.properties?.badges || [];
                this.sendMessageToConnection(connection, 'characterProfileBadges', badges);


                break;
            default:
                console.log("Unhandled message: ", message);
        }
    }

}
