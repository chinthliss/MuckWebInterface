/**
 * Type Definitions that are used on multiple pages
 */
export type Account = {
    id: number
    createdAt: string
    verifiedAt: string
    lockedAt: string
    lastConnected: string
    primaryEmail: string
    url: string
    referrals: number
    supporterPoints: number
    veterancy: number
    currency: number
    flags: string[]
    roles: string[]
    emails: AccountEmail[]
    notes: AccountNote[]
    characters: MuckDbref[]
    patreon?: {
        patreonId: string
        name: string
        email: string
        url: string
        thumbUrl: string
        updatedAt: string
        totalSupportUsd: number
        totalRewardedUsd: number
    }
    subscriptionActive: boolean
    subscriptionRenewing: boolean
    subscriptionExpires: string
    subscriptions: any[]
}

export type AccountTransaction = {
    id: string
    account_id: number
    subscription_id: number
    purchase_description: string
    type: string
    open: boolean
    created_at: string
    paid_at: string
    completed_at: string
    total_usd: number
    account_currency_quoted: number
    account_currency_rewarded: number
    account_currency_rewarded_items: number
    total_account_currency_rewarded: number
    items: number
    result: string
    url: string
    subscription_url?: string
}

export type AccountSubscription = {
    id: string
    account_id: number
    type: string
    amount_usd: number
    recurring_interval: number
    status: string
    created_at: string
    next_charge_at: string
    last_charge_at: string
    closed_at: string
    url: string
}

export type AccountNote = {
    accountId: number
    whenAt: string
    body: string
    staffMember: string
    game: string
}

export type AccountEmail = {
    email: string
    createdAt: string
    verifiedAt: string
    isPrimary: boolean
}

export type AccountCard = {
    id: number
    cardType: string
    maskedCardNumber: string
    expiryDate: string
    isDefault: boolean
}

export type AccountNotification = {
    id: number
    created_at: string
    read_at: string
    character: string
    message: string
}

// More of an AccountCurrencyHistoryEntry
export type AccountHistoryEntry = {
    id: number
    createdAt: string
    balance: number
    message: string
    game: string
}

export type MuckDbref = {
    dbref: number
    type: string
    name: string
    created: string
}

export type Character = {
    dbref: number,
    name: string,
    approved?: boolean,
    staffLevel?: number,
    level?: number
}

export type AvatarItem = {
    id: string
    name: string
    type?: string
    desc?: string
    requirement?: string
    earned?: boolean
    created_at?: string
    cost?: number
    free?: boolean
    owner?: Account
    url?: string
    x: number
    y: number
    rotate: number
    scale: number,
    filename?: string
    preview_url?: string
}

export type AvatarItemInstance = AvatarItem & {
    base: AvatarItem
    z: number
    image: HTMLImageElement
}

export type AvatarGradient = {
    name: string
    desc: string
    created_at?: string
    free: boolean
    owner_aid?: number
    owner_url?: string
    url: string
}

// Taken from Datatables documentation - https://datatables.net/manual/vue
// Figure there should be an alternative in its own typescript exports but couldn't find one

export interface DataTablesNamedSlotProps {
    /** The data to show in the cell (from the `columns.data` configuration) */
    cellData: string;

    /** The column index for the cell (0-based index) */
    colIndex: Number;

    /** The data object for the whole row */
    rowData: any;

    /** Row index for the cell (data index, not the display index) */
    rowIndex: Number;

    /** Orthogonal data type */
    type: string;
}

import {Api} from "datatables.net-bs5";
export interface DataTablesEvent {
    e: Event;
    dt: Api
}
