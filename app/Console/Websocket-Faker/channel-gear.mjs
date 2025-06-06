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
                type: 'Something something something',
                useType: 'consumable'
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
                type: 'Lots of words something equipment',
                useType: 'equipment'
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

    savedPlans = [
        {
            name: 'Test Saved Plan',
            recipeName: 'Test Recipe',
            modifierNames: ['Test Modifier']
        },
        {
            name: 'Broken Saved Plan',
            recipeName: 'Broken Recipe',
            modifierNames: ['Broken Modifier']
        }

    ];

    handlers = {
        'bootCrafting': (connection, _data) => {
            let initialEnvironment = {
                recipeCount: this.recipes.length,
                modifierCount: this.modifiers.length,
                savedPlans: this.savedPlans
            };

            this.sendMessageToConnection(connection, 'bootCrafting', initialEnvironment);

            for (const recipe of this.recipes) {
                this.sendMessageToConnection(connection, 'recipe', recipe);
            }

            for (const modifier of this.modifiers) {
                this.sendMessageToConnection(connection, 'modifier', modifier);
            }
        },
        'preview': (connection, data) => {
            // Data is 'recipe' and 'modifiers'
            this.sendMessageToConnection(connection, 'preview', data);
        }
    };

}
