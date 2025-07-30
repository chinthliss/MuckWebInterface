import Channel from './channel.mjs';

const SALVAGE_MARKET_CONFIG = {
    'waffle': {
        'common': {
            'buy': 100,
            'sell': 90,
            'owned': 10
        },
        'uncommon': {
            'buy': 1000,
            'sell': 920,
            'owned': 88,
            'downscale': {
                'cost': 1,
                'quantity': 100,
                'what': 'Common Waffle',
            },
            'tokens': {
                'cost': 1,
                'quantity': 5,
                'what': 'Reward Tokens',
            }

        },
        'betterer': {
            'buy': 4000,
            'sell': 3200,
            'downscale': {
                'cost': 1,
                'quantity': 4,
                'what': 'Uncommon Waffle',

            },
            'tokens': {
                'cost': 1,
                'quantity': 20,
                'what': 'Reward Tokens',
            }
        }
    },
    'cookie': {
        'common': {
            'buy': 200,
            'sell': 150,
            'upscale': {
                'cost': 1000,
                'quantity': 1,
                'what': 'Uncommon Waffle',

            },

        },
        'uncommon': {
            'buy': 2000,
            'sell': 1700,
            'owned': 88,
            'downscale': {
                'cost': 1,
                'quantity': 1000,
                'what': 'Common Cookie',
            },
            'tokens': {
                'cost': 1,
                'quantity': 10,
                'what': 'Reward Tokens',
            }
        },
        'betterer': {
            'buy': 4000,
            'sell': 3200,
            'downscale': {
                'cost': 1,
                'quantity': 200,
                'what': 'Uncommon Waffle',
            },
            'tokens': {
                'cost': 1,
                'quantity': 20,
                'what': 'Reward Tokens',
            }
        }
    }
}

const SALVAGE_MARKET_DEMAND = {
    'waffle': 0.5,
    'cookie': 0.2519191919
}

const salvageTypes = () => Object.keys(SALVAGE_MARKET_CONFIG);
const salvageRanks = () => Object.keys(SALVAGE_MARKET_CONFIG['waffle']);

const salvagePrices = () => {
    let prices = {};
    for (const type of salvageTypes()) {
        let perType = {
            demand: SALVAGE_MARKET_DEMAND[type] ?? 1.0,
            prices: {}
        };
        for (const rank of salvageRanks()) {
            perType.prices[rank] = {
                buy: SALVAGE_MARKET_CONFIG[type][rank].buy ?? 0,
                sell: SALVAGE_MARKET_CONFIG[type][rank].sell ?? 0
            }
        }
        prices[type] = perType;
    }
    return prices;
}

const salvageConfig = () => {
    let config = {};
    for (const type of salvageTypes()) {
        let perType = {};
        for (const rank of salvageRanks()) {
            perType[rank] = {};
            if (SALVAGE_MARKET_CONFIG[type][rank].tokens) perType[rank].tokens = SALVAGE_MARKET_CONFIG[type][rank].tokens;
            if (SALVAGE_MARKET_CONFIG[type][rank].upscale) perType[rank].upscale = SALVAGE_MARKET_CONFIG[type][rank].upscale;
            if (SALVAGE_MARKET_CONFIG[type][rank].downscale) perType[rank].downscale = SALVAGE_MARKET_CONFIG[type][rank].downscale;
        }
        config[type] = perType;
    }
    return config;
}

const salvageOwned = () => {
    let owned = {};
    for (const type of salvageTypes()) {
        let perType = {};
        for (const rank of salvageRanks()) {
            perType[rank] = SALVAGE_MARKET_CONFIG[type][rank].owned ?? 0;
        }
        owned[type] = perType;
    }
    return owned;
}

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
        'bootSalvageDisplay': (connection, _data) => {
            let owned = {
                'waffle': {'common': 55555},
                'cookie': {'common': 7, 'uncommon': 9},
            }

            // Salvage display is a static widget, so takes a full state
            this.sendMessageToConnection(connection, 'bootSalvageDisplay', {
                types: salvageTypes(),
                ranks: salvageRanks(),
                owned: owned,
                skills: {}
            })
        },

        'bootSalvageMarket': (connection, _data) => {
            // Salvage market only takes types and ranks at boot up, everything else will refire as required.
            this.sendMessageToConnection(connection, 'bootSalvageMarket', {
                types: salvageTypes(),
                ranks: salvageRanks(),
                config: salvageConfig()
            })

            this.sendMessageToConnection(connection, 'salvageOwned', salvageOwned())

            this.sendMessageToConnection(connection, 'salvagePrices', salvagePrices())
        },

        // Data is in the form {type, salvageType, salvageRank, quantity}
        // Expects response of {text, value}
        'salvageMarketQuote': (connection, data) => {
            const response = {
                text: 'This is a test, for the value of ' + 500 * data.quantity,
                value: 500 * data.quantity
            }
            this.sendMessageToConnection(connection, 'salvageMarketQuote', response)
        },

        // Data is in the form {type, salvageType, salvageRange, quote}
        // Expects response of 'OK' for success otherwise a more detailed error
        // In the case of success, should send renewed prices/owned if appropriate
        'salvageMarketTransaction': (connection, data) => {
            let response = 'OK';
            if (data.salvageRank === 'betterer') {
                response = 'Betterer always fails';
            }
            this.sendMessageToConnection(connection, 'salvageMarketTransaction', response)
        },

        'bootSalvageAutoPurchaseConfig': (connection, _data) => {
            this.sendMessageToConnection(connection, 'bootSalvageAutoPurchaseConfig', {
                ranks: salvageRanks()
            })

            this.sendMessageToConnection(connection, 'salvageAutoPurchaseLimits', {
                'common': 4000
            })
        }
    };

}
