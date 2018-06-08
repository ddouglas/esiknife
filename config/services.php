<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Stripe, Mailgun, SparkPost and others. This file provides a sane
    | default location for this type of information, allowing packages
    | to have a conventional place to find your various credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
    ],

    'eve' => [
        'userAgent' => "ESIKnife (ESIK) || David Davaham (David Douglas) || ddouglas@douglaswebdev.net",
        'sso' => [
            'id' => env('EVESSO_CLIENT_ID'),
            'secret' => env('EVESSO_CLIENT_SECRET'),
            'callback' => 'sso.callback' // name of the route that generates the sso call back url
        ],
        'urls' => [
            'sso' => "https://login.eveonline.com",
            'esi' => "https://esi.tech.ccp.is",
            'img' => "https://imageserver.eveonline.com",
            'km' => "https://zkillboard.com/",
            'dotlan' => "https://evemaps.dotlan.net/",
            'who' => "https://evewho.com",
            'sde' => "http://sde.zzeve.com"
        ],
        'sde' => [
            'import' => [
                'invGroups','invCategories','mapRegions',
                'mapConstellations', 'chrAncestries', 'chrBloodlines',
                'chrRaces', 'chrFactions'
            ]
        ],
        'scopes'=>[
            'readCharacterAssets' => "esi-assets.read_assets.v1",
            'readCharacterBookmarks' => "esi-bookmarks.read_character_bookmarks.v1",
            'readCharacterClones' => "esi-clones.read_clones.v1",
            'readCharacterContacts' => "esi-characters.read_contacts.v1",
            'readCharacterContracts' => "esi-contracts.read_character_contracts.v1",
            'readCharacterImplants' => "esi-clones.read_implants.v1",
            'readCharacterLocation' => "esi-location.read_location.v1",
            'readCharacterMails' => "esi-mail.read_mail.v1",
            'readCharacterShip' => "esi-location.read_ship_type.v1",
            'readCharacterSkills' => "esi-skills.read_skills.v1",
            'readCharacterSkillQueue' => "esi-skills.read_skillqueue.v1",
            'readCharacterWallet' => "esi-wallet.read_character_wallet.v1",
            'readUniverseStructures' => "esi-universe.read_structures.v1",
        ],
        'dogma' => [
            'attributes' => [
                'skillz' => [
                    'all' => [
                        182,183,184,1285,1289,1290,277,278,279,1286,1287,1288
                    ],
                    'indicators' => [
                        182,183,184,1285,1289,1290
                    ],
                    'levels' => [
                        277,278,279,1286,1287,1288
                    ],
                    'map' => [
                        182 => 277,
                        183 => 278,
                        184 => 279,
                        1285 => 1286,
                        1289 => 1287,
                        1290 => 1288
                    ]
                ]
            ]
        ]
    ],

    'bitbucket' => [
        'url' => "https://bitbucket.org/douglaswebdev/esim/issues"
    ]

];
