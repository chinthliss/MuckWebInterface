import Channel from './channel.mjs';

export default class ChannelHelp extends Channel {

    statusCatalogue = {
        "testStatus": {
            status: 'testStatus',
            desc: 'testDescription',
            fragment: 'testFragment',
            property: 'testProperty'
        }
    }

    getHelpResponse = (page) => {
        const record = this.helpCatalogue[page];
        if (!record) return "NOTFOUND";

        let helpResponse = {
            'title': page,
        };

        if (record.content) helpResponse.content = record.content;

        if (record.contains) helpResponse.contains = record.contains;

        return helpResponse;

    }

    handlers = {
        'getAllStatuses': (connection, data) => {
            this.sendMessageToConnection(connection, 'statusList', this.statusCatalogue.length);
            for (const status of this.statusCatalogue) {
                this.sendMessageToConnection(connection, 'status', status);
            }
        }
    }

}
