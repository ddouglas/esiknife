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
        'userAgent' => env("USER_AGENT", "ESIKnife (ESIK) || SELF HOSTED VERSION"),
        'sso' => [
            'id' => env('EVESSO_CLIENT_ID'),
            'secret' => env('EVESSO_CLIENT_SECRET'),
            'callback' => 'sso.callback', // name of the route that generates the sso call back url
            'admin' => [
                'id' => env('EVESSO_CLIENT_ID_ADMIN'),
                'secret' => env('EVESSO_CLIENT_SECRET_ADMIN'),
                'callback' => env('EVESSO_CLIENT_CALLBACK_ADMIN')
            ]
        ],
        'urls' => [
            'sso' => [
                'authorize' => 'https://login.eveonline.com/v2/oauth/authorize',
                'token' => 'https://login.eveonline.com/v2/oauth/token',
                'revoke' => 'https://login.eveonline.com/v2/oauth/revoke',
                'meta' => 'https://login.eveonline.com/oauth/jwks',
            ],
            'esi' => "https://esi.evetech.net",
            'img' => "https://imageserver.eveonline.com",
            'km' => "https://zkillboard.com/",
            'dotlan' => "https://evemaps.dotlan.net/",
            'who' => "https://evewho.com/",
            'sde' => "http://sde.zzeve.com"
        ],
        'sde' => [
            'import' => [
                'ancestries', 'bloodlines', 'categories', 'constellations',
                'factions', 'groups',  'races', 'regions', 'types', 'attributes'
            ]
        ],
        'scopes'=> [
            "readCharacterAssets" => [
                'key' => 'readCharacterAssets',
                'display' => "Read Character Assets",
                'scope' => "esi-assets.read_assets.v1"
            ],
            "readCharacterBookmarks" => [
                'key' => 'readCharacterBookmarks',
                'display' => "Read Character Bookmarks",
                'scope' => "esi-bookmarks.read_character_bookmarks.v1"
            ],
            "readCharacterClones" => [
                'key' => 'readCharacterClones',
                'display' => "Read Character Clones",
                'scope' => "esi-clones.read_clones.v1"
            ],
            "readCharacterContacts" => [
                'key' => 'readCharacterContacts',
                'display' => "Read Character Contacts",
                'scope' => "esi-characters.read_contacts.v1"
            ],
            "readCharacterContracts" => [
                'key' => 'readCharacterContracts',
                'display' => "Read Character Contracts",
                'scope' => "esi-contracts.read_character_contracts.v1"
            ],
            "readCharacterFittings" => [
                'key' => 'readCharacterFittings',
                'display' => "Read Character Fittings",
                'scope' => "esi-fittings.read_fittings.v1"
            ],
            "readCharacterImplants" => [
                'key' => 'readCharacterImplants',
                'display' => "Read Character Implants",
                'scope' => "esi-clones.read_implants.v1"
            ],
            "readCharacterLocation" => [
                'key' => 'readCharacterLocation',
                'display' => "Read Character Location",
                'scope' => "esi-location.read_location.v1"
            ],
            "readCharacterMails" => [
                'key' => 'readCharacterMails',
                'display' => "Read Character Mails",
                'scope' => "esi-mail.read_mail.v1"
            ],
            "readCharacterShip" => [
                'key' => 'readCharacterShip',
                'display' => "Read Character Ship",
                'scope' => "esi-location.read_ship_type.v1"
            ],
            "readCharacterSkills" => [
                'key' => 'readCharacterSkills',
                'display' => "Read Character Skills",
                'scope' => "esi-skills.read_skills.v1"
            ],
            "readCharacterSkillQueue" => [
                'key' => 'readCharacterSkillQueue',
                'display' => "Read Character SkillQueue",
                'scope' => "esi-skills.read_skillqueue.v1"
            ],
            "readCharacterWallet" => [
                'key' => 'readCharacterWallet',
                'display' => "Read Character Wallet",
                'scope' => "esi-wallet.read_character_wallet.v1"
            ],
            "readUniverseStructures" => [
                'key' => 'readUniverseStructures',
                'display' => "Read Universe Structures",
                'scope' => "esi-universe.read_structures.v1"
            ],
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
        ],
        'mails' => [
            'pages' => env('MAILHEADER_PAGES', 1)
        ],
        'updateInterval' => env('JOB_STATUS_REFRESH_INTERVAL', 10)
    ],

    "bitbucket" => [
        "urls" => [
            "issues" => "https://bitbucket.org/devoverlord/esiknife/issues",
            "commit" => "https://bitbucket.org/devoverlord/esiknife/commits",
            "branches" => "https://bitbucket.org/devoverlord/esiknife/branches"
        ]
    ],
    'github' => [
        'urls' => [
            "repo" => "https://github.com/ddavaham/esiknife",
            "issues" => "https://github.com/ddavaham/esiknife/issues",
            "commit" => "https://github.com/ddavaham/esiknife/commits",
            "branches" => "https://github.com/ddavaham/esiknife/branches"
        ]
    ]

];
