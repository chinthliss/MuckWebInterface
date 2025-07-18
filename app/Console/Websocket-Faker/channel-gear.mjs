import Channel from './channel.mjs';

export default class ChannelGear extends Channel {

    salvage_types = ['waffle', 'banana', 'cookie'];
    salvage_ranks = ['common', 'uncommon', 'rare'];
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
            item: {}
        },
        {
            name: 'Test Modifier 2',
            description: 'This is a second test modifier',
            costMoney: 200,
            costXp: 300,
            item: {}
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
        'craftPreview': (connection, data) => {
            // Data is 'recipe' and 'modifiers'
            // Not even going to try and imitate the calculations on the muck!
            const preview = {
                recipe: data.recipe,
                modifiers: data.modifiers,
                buildCost: 10,
                commonSalvage: {
                    chemical: 10,
                    electronic: 10,
                    energy: 10,
                    food: 10
                },
                loadout: 10,
                money: 10,
                otherIngredients: {
                    something: 1
                },
                quantity: 1,
                quantityFloat: 1.0,
                scale: 0,
                skills: {
                    mechanical: 10
                },
                salvage: {
                    commonChemical: 10,
                    commonElectronic: 10,
                    commonEnergy: 10,
                    commonFood: 10,
                },
                upkeep: 10,
                xp: 10
            };

            this.sendMessageToConnection(connection, 'craftPreview', preview);
        },

        'bootSalvageDisplay': (connection, data) => {
            let owned = {
                'waffle': {'common': 55555},
                'banana': {'common': 7, 'uncommon': 9},
            }

            // Salvage display is a static widget, so takes a full state
            this.sendMessageToConnection(connection, 'bootSalvageDisplay', {
                types: this.salvage_types,
                ranks: this.salvage_ranks,
                owned: owned,
                skills: {}
            })
        },

        'bootSalvageMarket': (connection, data) => {
            let owned = {
                'waffle': {'common': 55555},
                'banana': {'common': 7, 'uncommon': 9},
            }

            // Salvage market only takes types and ranks at boot up, everything else will refire as required.
            this.sendMessageToConnection(connection, 'bootSalvageMarket', {
                types: this.salvage_types,
                ranks: this.salvage_ranks
            })

            this.sendMessageToConnection(connection, 'salvageOwned', owned)

            this.sendMessageToConnection(connection, 'salvagePrices', {
                'waffle': {'common': { 'buy': 1000, 'sell': 1000, 'demand': 0.5}}
            })
        },

        'bootSalvageAutoPurchaseConfig': (connection, data) => {
            this.sendMessageToConnection(connection, 'bootSalvageAutoPurchaseConfig', {
                ranks: this.salvage_ranks
            })

            this.sendMessageToConnection(connection, 'salvageAutoPurchaseLimits', {
                'common': 4000
            })
        }
    };

}
