<?php

namespace App\Avatar;

enum AvatarPreference: string
{
    /**
     * Avoid displaying where possible.
     * Some pages (e.g. avatar editor) won't reflect this.
     */
    case HIDDEN = 'hidden';

    /**
     * No naughty bits
     */
    case CLEAN = 'clean';

    /**
     * Female naughty bits only
     */
    case DEFAULT = 'default';

    /**
     * All the naughty bits
     */
    case EXPLICIT = 'explicit';
}
