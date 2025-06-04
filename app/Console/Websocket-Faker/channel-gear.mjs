import Channel from './channel.mjs';


export default class ChannelGear extends Channel {
    recipes = [
        {
            name: 'Test Recipe 1',
            description: 'This is the 1st test recipe',
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
        },
        {
            name: 'Test Recipe 2',
            description: 'This is the 2nd test recipe',
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
            description: 'This is a test modifier',
            costMoney: 200,
            costXp: 300,
            item: {

            }
        },
        {
            name: 'Test Modifier 2',
            description: 'This is a second test modifier',
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
        },
        'preview': (connection, data) => {
            // Data is 'recipe' and 'modifiers'
            this.sendMessageToConnection(connection, 'preview', data);
        }
    };

}
