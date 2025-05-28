import Channel from './channel.mjs';


export default class ChannelGear extends Channel {
    recipes = [
        {
            name: 'Test Recipe',
            description: 'this is a test entry',
            availability: 'somewhere',
            costMoney: 100,
            costXp: 200,
            skills: {
                mechanical: 20
            },
            salvage: {
                food: {
                    common: 1
                }
            },
            item: {

            }
        }
    ];

    modifiers = [
        {
            name: 'Test Modifier',
            costMoney: 200,
            costXp: 300,
            item: {

            }
        }
    ];

    blueprints = [
        {
            name: 'Test Blueprint',
            recipeName: 'Test Recipe',
            modifierNames: ['Test Modifier']
        },
        {
            name: 'Broken Blueprint',
            recipeName: 'Broken Recipe',
            modifierNames: ['Broken Modifier']
        }

    ];

    handlers = {
        'bootCrafting': (connection, _data) => {
            let initialEnvironment = {
                recipes: this.recipes,
                modifiers: this.modifiers,
                blueprints: this.blueprints
            };

            this.sendMessageToConnection(connection, 'bootCrafting', initialEnvironment);
        }
    };

}
