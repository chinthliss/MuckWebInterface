/**
 * Type Definitions
 * This file doesn't actually provide any code but holds all the typedefs used across the project in one place.
 */

/**
 * @typedef {object} Account
 * @property {int} id
 * @property {string} createdAt
 * @property {string} verifiedAt
 * @property {string} lockedAt
 * @property {string} lastConnected
 * @property {string} primaryEmail
 * @property {string} url
 * @property {int} referrals
 * @property {int} supporterPoints
 * @property {int} veterancy
 * @property {int} currency
 * @property {string[]} flags
 * @property {string[]} roles
 * @property {AccountEmail[]} emails
 * @property {AccountNote[]} notes
 * @property {MuckDbref[]} characters
 * @property {object} [patreon]
 * @property {boolean} subscriptionActive
 * @property {boolean} subscriptionRenewing
 * @property {string} subscriptionExpires
 * @property {array} subscriptions
 */

/**
 * @typedef {object} AccountTransaction
 * @property {string} id
 * @property {int} account_id
 * @property {int} subscription_id
 * @property {string} purchase_description
 * @property {string} type
 * @property {bool} open
 * @property {string} created_at
 * @property {string} paid_at
 * @property {string} completed_at
 * @property {int} total_usd
 * @property {float} account_currency_quoted
 * @property {float} account_currency_rewarded
 * @property {float} account_currency_rewarded_items
 * @property {float} total_account_currency_rewarded
 * @property {int} items
 * @property {string} result
 * @property {string} url
 */

/**
 * @typedef {object} AccountSubscription
 * @property {string} id
 * @property {int} account_id
 * @property {string} type
 * @property {float} amount_usd
 * @property {int} recurring_interval
 * @property {string} status
 * @property {string} created_at
 * @property {string} next_charge_at
 * @property {string} last_charge_at
 * @property {string} closed_at
 * @property {string} url
 */

/**
 * @property {int} subscription_id
 * @property {string} purchase_description
 * @property {string} type
 * @property {bool} open
 * @property {string} created_at
 * @property {string} paid_at
 * @property {string} completed_at
 * @property {int} total_usd
 * @property {float} account_currency_quoted
 * @property {float} account_currency_rewarded
 * @property {float} account_currency_rewarded_items
 * @property {float} total_account_currency_rewarded
 * @property {int} items
 * @property {string} result
 * @property {string} url
 */

/**
 * @typedef {object} AccountNote
 * @property {int} accountId
 * @property {string} whenAt
 * @property {string} body
 * @property {string} staffMember
 * @property {string} game
 */

/**
 * @typedef {object} AccountEmail
 * @property {string} email
 * @property {string} createdAt
 * @property {string} verifiedAt
 * @property {boolean} isPrimary
 */

/**
 * @typedef {object} AccountCard
 * @property {string} id
 * @property {string} cardType
 * @property {string} maskedCardNumber
 * @property {string} expiryDate
 * @property {boolean} isDefault
 */

/**
 * @typedef {object} AccountNotification
 * @property {int} id
 * @property {string} created_at
 * @property {string} read_at
 * @property {string} character
 * @property {string} message
 */

/**
 * @typedef {object} MuckDbref
 * @property {int} dbref
 * @property {string} type
 * @property {string} name
 * @property {string} created
 */

/**
 * @typedef {object} Character
 * @property {string} name
 * @property {boolean} approved
 * @property {number} dbref
 * @property {number} level
 * @property {number} [staffLevel]
 */

/**
 * @typedef {Object} AvatarItem
 * @property {string} id
 * @property {string} name
 * @property {string} type
 * @property {string} desc
 * @property {string} requirement
 * @property {date} [created_at]
 * @property {int} cost
 * @property {boolean} free
 * @property {Account} [owner]
 * @property {string} url
 * @property {int} x
 * @property {int} y
 * @property {int} rotate
 * @property {float} scale
 */

/**
 * @typedef {object} AvatarGradient
 * @property {string} name
 * @property {string} desc
 * @property {date} [created_at]
 * @property {boolean} free
 * @property {number} [owner_aid]
 * @property {string} [owner_url]
 * @property {string} url
 */
exports.unused = {};
