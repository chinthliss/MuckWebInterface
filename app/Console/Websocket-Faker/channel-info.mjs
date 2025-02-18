import Channel from './channel.mjs';

export default class ChannelHelp extends Channel {

    statusCatalogue = {
        "testStatus": {
            status: 'testStatus',
            desc: 'testDescription',
            fragment: 'testFragment',
            properties: ['special/specialname/testProperty', 'talent/talentname/testPropertyTwo']
        },
        "anotherStatus": {
            status: 'anotherStatus',
            desc: 'testDescription with a longer description in it, to taste layout when it takes more space',
            fragment: 'testFragment',
            properties: ['recipe/recipename/testProperty with a longer location', 'recipe modifier/recipemodifiername/testPropertyTheSecond']
        }

    }

    handlers = {
        'getAllStatuses': (connection, _data) => {
            let count = Object.keys(this.statusCatalogue).length;
            this.sendMessageToConnection(connection, 'statusList', count);
            for (const status in this.statusCatalogue) {
                this.sendMessageToConnection(connection, 'status', this.statusCatalogue[status]);
            }
        }
    }

}
