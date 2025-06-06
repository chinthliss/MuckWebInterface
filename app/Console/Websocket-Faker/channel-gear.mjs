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
        'craftPreview': (connection, data) => {
            // Data is 'recipe' and 'modifiers'
            // Not even going to try and imitate the calculations on the muck, everything is just additive here.
            const preview = {
                buildcost: 10,
                commonSalvage: {
                    chemical: 10,
                    electronic: 10,
                    energy: 10,
                    food: 10
                },
                loadout: 10,
                money: 10,
                otherIngredients: {},
                quantity: 1,
                quantityFloat: 1.0,
                scale: 0,
                skills: {
                    mechanical: 10
                },
                trueSalvage: {
                    commonChemical: 10,
                    commonElectronic: 10,
                    commonEnergy: 10,
                    commonFood: 10,
                },
                upkeep: 10,
                xp: 10
            };
            // { "buildcost": 20, "common salvage": { "chemical": 15, "electronic": 15, "energy": 105 }, "loadout": 15, "money": 20000, "other ingredients": {}, "quantity": 1, "quantity float": 1, "scale": 0, "skills": { "electronic": 1 }, "true salvage": { "common chemical": 15, "common electronic": 15, "common energy": 5, "uncommon energy": 1 }, "upkeep": 3, "xp": 0 }


            this.sendMessageToConnection(connection, 'craftPreview', preview);
        }
    };

}
