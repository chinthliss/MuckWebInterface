import Channel from './channel.mjs';

export default class ChannelHelp extends Channel {

    statusCatalogue = {
        "testStatus": {
            status: 'testStatus',
            desc: 'testDescription',
            fragment: 'testFragment',
            property: 'testProperty'
        },
        "anotherStatus": {
            status: 'anotherStatus',
            desc: 'testDescription',
            fragment: 'testFragment',
            property: 'testProperty'
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
