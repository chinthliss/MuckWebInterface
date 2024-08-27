import Channel from './channel.mjs';

export default class ChannelCharacter extends Channel {
    handlers = {
        'bootAdminDashboard': (connection, _data) => {
            this.sendMessageToConnection(connection, 'bootAdminDashboard', {
                formsForReview:['test', 'test2']
            });
        }
    }
}
