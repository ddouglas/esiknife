<?php

namespace ESIK\Http\Controllers;

use Curl\Curl;
use Illuminate\Support\Collection;

class HttpController extends Controller
{

    public function request (array $headers, string $method, string $url, string $path, $data)
    {
        $curl = new Curl();
        foreach ($headers as $key=>$value) {
            $curl->setHeader($key, $value);
        }
        $curl->{$method}($url.$path, $data);

        $status = $curl->httpStatusCode >= 200 && $curl->httpStatusCode <=299 ? true : false;
        $message = null;
        $response = (object)[
            'status' => $status,
            'payload' => (object)[
                'log_id' => str_random(16),
                'message' => $message,
                'url' => $curl->url,
                'code' => $curl->httpStatusCode,
                'headers' => (object)[
                    'request' => collect($curl->requestHeaders)->put('data', $data)->toArray(),
                    'response' => collect($curl->responseHeaders)->toArray()
                ],
                'response' => $curl->response
            ]
        ];

        if (!$status){
            $message = "Failed HTTP Request ". strtoupper($method). " " . $path . " : Http Status ". $curl->httpStatusCode;
            if ($url === config('services.eve.urls.sso')) {
                if (property_exists($curl->response, 'error') && property_exists($curl->response, 'error_description')) {
                    $message .= " || Error: ". $curl->response->error . " || Error Description: ". $curl->response->error_description;
                }

            }
            if ($url === config('services.eve.urls.esi')) {
                if (property_exists($curl->response, 'error')) {
                    $message .= " || Error: ". $curl->response->error;
                }
            }
            $response->payload->message = $message;
            activity((new \ReflectionClass($this))->getShortName().'::'.$response->payload->log_id)->withProperties($response->payload)->log($message);
        }
        return $response;
    }

    public function oauthVerifyAuthCode (string $code, string $authorization = null)
    {
        return $this->request([
            "Authorization" => "Basic ". (!is_null($authorization) ?  $authorization : base64_encode(config("services.eve.sso.id").":".config("services.eve.sso.secret"))),
            "Content-Type" => "application/x-www-form-urlencoded",
            "Host" => "login.eveonline.com",
            "User-Agent" => config("services.eve.userAgent")
        ], 'post', config('services.eve.urls.sso'),"/oauth/token", [
            'grant_type' => "authorization_code",
            'code' => $code
        ]);
    }

    public function oauthVerifyAccessToken (string $token)
    {
        return $this->request([
            "Authorization" => "Bearer ".$token,
            "Content-Type" => "application/json",
            "User-Agent" => config("services.eve.userAgent")
        ], 'get', config('services.eve.urls.esi'),"/verify/", []);
    }

    public function postRefreshToken (string $token)
    {
        return $this->request([
            "Authorization" => "Basic ".base64_encode(config("services.eve.sso.id").":".config("services.eve.sso.secret")),
            "Content-Type" => "application/json",
            "Host" => "login.eveonline.com",
            "User-Agent" => config("services.eve.userAgent")
        ], 'post', config("services.eve.urls.sso"),"/oauth/token", json_encode([
            "grant_type" => "refresh_token",
            "refresh_token" => $token
        ]));
    }

    public function postRevokeToken (string $token, string $hint = "refresh_token")
    {
        return $this->request([
            "Authorization" => "Basic ".base64_encode(config("services.eve.sso.id").":".config("services.eve.sso.secret")),
            "Content-Type" => "application/json",
            "Host" => "login.eveonline.com",
            "User-Agent" => config("services.eve.userAgent")
        ], 'post', config("services.eve.urls.sso"),"/oauth/revoke", json_encode([
            "token_type_hint" => $hint,
            "token" => $token
        ]));
    }

    public function getCharactersCharacterId ($id)
    {
        return $this->request([
            "Content-Type" => "application/json",
            "User-Agent" => config("services.eve.userAgent")
        ], 'get', config('services.eve.urls.esi'), "/v4/characters/{$id}/", []);
    }

    public function getCorporationsCorporationId ($id)
    {
        return $this->request([
            "Content-Type" => "application/json",
            "User-Agent" => config("services.eve.userAgent")
        ], 'get', config('services.eve.urls.esi'),"/v4/corporations/{$id}/", []);
    }

    public function getAlliancesAllianceId ($id)
    {
        return $this->request([
            "Content-Type" => "application/json",
            "User-Agent" => config("services.eve.userAgent")
        ], 'get', config('services.eve.urls.esi'),"/v3/alliances/{$id}/", []);
    }

    public function getStatus ()
    {
        return $this->request([
            "Content-Type" => "application/json",
            "User-Agent" => config("services.eve.userAgent")
        ], 'get', config('services.eve.urls.esi'),"/v1/status/", []);
    }

    public function getCharacterCharacterIdAssets(int $id, string $token, int $page)
    {
        return $this->request([
            "Authorization" => "Bearer ". $token,
            "Content-Type" => "application/json",
            "User-Agent" => config("services.eve.userAgent")
        ], 'get', config('services.eve.urls.esi'),"/v3/characters/{$id}/assets/", [
            'page' => $page
        ]);
    }

    public function headCharactersCharacterIdAssets(int $id, string $token)
    {
        return $this->request([
            "Authorization" => "Bearer ". $token,
            "Content-Type" => "application/json",
            "User-Agent" => config("services.eve.userAgent")
        ], 'head', config('services.eve.urls.esi'),"/v3/characters/{$id}/assets/", []);
    }

    public function postCharactersCharacterIdAssetsLocations(int $id, string $token, array $ids)
    {
        return $this->request([
            "Authorization" => "Bearer ". $token,
            "Content-Type" => "application/json",
            "User-Agent" => config("services.eve.userAgent")
        ], 'post', config('services.eve.urls.esi'),"/v2/characters/{$id}/assets/locations/", json_encode($ids));
    }

    public function postCharactersCharacterIdAssetsNames(int $id, string $token, array $ids)
    {
        return $this->request([
            "Authorization" => "Bearer ". $token,
            "Content-Type" => "application/json",
            "User-Agent" => config("services.eve.userAgent")
        ], 'post', config('services.eve.urls.esi'),"/v1/characters/{$id}/assets/names/", json_encode($ids));
    }

    public function headCharactersCharacterIdBookmarks(int $id, string $token)
    {
        return $this->request([
            "Authorization" => "Bearer ". $token,
            "Content-Type" => "application/json",
            "User-Agent" => config("services.eve.userAgent")
        ], 'head', config('services.eve.urls.esi'),"/v2/characters/{$id}/bookmarks/", []);
    }

    public function getCharactersCharacterIdBookmarks(int $id, string $token, int $page)
    {
        return $this->request([
            "Authorization" => "Bearer ". $token,
            "Content-Type" => "application/json",
            "User-Agent" => config("services.eve.userAgent")
        ], 'get', config('services.eve.urls.esi'),"/v2/characters/{$id}/bookmarks/", [
            'page' => $page
        ]);
    }

    public function getCharactersCharacterIdBookmarksFolders($id, $token)
    {
        return $this->request([
            "Authorization" => "Bearer ". $token,
            "Content-Type" => "application/json",
            "User-Agent" => config("services.eve.userAgent")
        ], 'get', config('services.eve.urls.esi'),"/v2/characters/{$id}/bookmarks/folders/", []);
    }

    public function getCharactersCharacterIdClones($id, $token)
    {
        return $this->request([
            "Authorization" => "Bearer ". $token,
            "Content-Type" => "application/json",
            "User-Agent" => config("services.eve.userAgent")
        ], 'get', config('services.eve.urls.esi'),"/v3/characters/{$id}/clones/", []);
    }

    public function getCharactersCharacterIdCorporationHistory ($id)
    {
        return $this->request([
            "Content-Type" => "application/json",
            "User-Agent" => config("services.eve.userAgent")
        ], 'get', config('services.eve.urls.esi'),"/v1/characters/{$id}/corporationhistory/", []);
    }

    public function getCharactersCharacterIdFittings ($id, $token)
    {
        return $this->request([
            "Authorization" => "Bearer ". $token,
            "Content-Type" => "application/json",
            "User-Agent" => config("services.eve.userAgent")
        ], 'get', config('services.eve.urls.esi'),"/v1/characters/{$id}/fittings/", []);
    }

    public function getCharactersCharacterIdImplants($id, $token)
    {
        return $this->request([
            "Authorization" => "Bearer ". $token,
            "Content-Type" => "application/json",
            "User-Agent" => config("services.eve.userAgent")
        ], 'get', config('services.eve.urls.esi'),"/v1/characters/{$id}/implants/", []);
    }

    public function getCharactersCharacterIdMail ($id, $token, $lmid = null)
    {
        $params = [];
        if (!is_null($lmid)) {
            $params['last_mail_id'] = $lmid;
        }

        return $this->request([
            "Authorization" => "Bearer ". $token,
            "Content-Type" => "application/json",
            "User-Agent" => config("services.eve.userAgent")
        ], 'get', config('services.eve.urls.esi'),"/v1/characters/{$id}/mail/", $params);
    }

    public function getCharactersCharacterIdMailLists ($id, $token)
    {
        return $this->request([
            "Authorization" => "Bearer ". $token,
            "Content-Type" => "application/json",
            "User-Agent" => config("services.eve.userAgent")
        ], 'get', config('services.eve.urls.esi'),"/v1/characters/{$id}/mail/lists/", []);
    }

    public function getCharactersCharacterIDMailLabels ($id, $token)
    {
        return $this->request([
            "Authorization" => "Bearer ". $token,
            "Content-Type" => "application/json",
            "User-Agent" => config("services.eve.userAgent")
        ], 'get', config('services.eve.urls.esi'),"/v3/characters/{$id}/mail/labels/", []);
    }

    public function getCharactersCharacterIdMailMailId ($id, $token, $mailId)
    {
        return $this->request([
            "Authorization" => "Bearer ". $token,
            "Content-Type" => "application/json",
            "User-Agent" => config("services.eve.userAgent")
        ], 'get', config('services.eve.urls.esi'),"/v1/characters/{$id}/mail/{$mailId}/", []);
    }

    public function getCharactersCharacterIdWallet ($id, $token)
    {
        return $this->request([
            "Authorization" => "Bearer ". $token,
            "Content-Type" => "application/json",
            "User-Agent" => config("services.eve.userAgent")
        ], 'get', config('services.eve.urls.esi'),"/v1/characters/{$id}/wallet/", []);
    }

    public function getCharactersCharacterIdWalletJournal ($id, $token)
    {
        return $this->request([
            "Authorization" => "Bearer ". $token,
            "Content-Type" => "application/json",
            "User-Agent" => config("services.eve.userAgent")
        ], 'get', config('services.eve.urls.esi'),"/v6/characters/{$id}/wallet/journal/", []);
    }

    public function getCharactersCharacterIdWalletTransactions ($id, $token)
    {
        return $this->request([
            "Authorization" => "Bearer ". $token,
            "Content-Type" => "application/json",
            "User-Agent" => config("services.eve.userAgent")
        ], 'get', config('services.eve.urls.esi'),"/v1/characters/{$id}/wallet/transactions/", []);
    }

    public function getCharactersCharacterIdContacts ($id, $token)
    {
        return $this->request([
            "Authorization" => "Bearer ". $token,
            "Content-Type" => "application/json",
            "User-Agent" => config("services.eve.userAgent")
        ], 'get', config('services.eve.urls.esi'),"/v2/characters/{$id}/contacts/", []);
    }

    public function getCharactersCharacterIdContactLabels ($id, $token)
    {
        return $this->request([
            "Authorization" => "Bearer ". $token,
            "Content-Type" => "application/json",
            "User-Agent" => config("services.eve.userAgent")
        ], 'get', config('services.eve.urls.esi'),"/v1/characters/{$id}/contacts/labels/", []);
    }

    public function getCharactersCharacterIdContracts ($id, $token)
    {
        return $this->request([
            "Authorization" => "Bearer ". $token,
            "Content-Type" => "application/json",
            "User-Agent" => config("services.eve.userAgent")
        ], 'get', config('services.eve.urls.esi'),"/v1/characters/{$id}/contracts/", []);
    }

    public function getCharactersCharacterIdContractsContractIdItems ($id, $token, $contractId)
    {
        return $this->request([
            "Authorization" => "Bearer ". $token,
            "Content-Type" => "application/json",
            "User-Agent" => config("services.eve.userAgent")
        ], 'get', config('services.eve.urls.esi'),"/v1/characters/{$id}/contracts/$contractId/items/", []);
    }

    public function getCharactersCharacterIdLocation ($id, $token)
    {
        return $this->request([
            "Authorization" => "Bearer ". $token,
            "Content-Type" => "application/json",
            "User-Agent" => config("services.eve.userAgent")
        ], 'get', config('services.eve.urls.esi'),"/v1/characters/{$id}/location/", []);
    }

    public function getCharactersCharacterIdRoles ($id, $token)
    {
        return $this->request([
            "Authorization" => "Bearer ". $token,
            "Content-Type" => "application/json",
            "User-Agent" => config("services.eve.userAgent")
        ], 'get', config('services.eve.urls.esi'),"/v1/characters/{$id}/roles/", []);
    }

    public function getCharactersCharacterIdShip ($id, $token)
    {
        return $this->request([
            "Authorization" => "Bearer ". $token,
            "Content-Type" => "application/json",
            "User-Agent" => config("services.eve.userAgent")
        ], 'get', config('services.eve.urls.esi'),"/v1/characters/{$id}/ship/", []);
    }

    public function getCharactersCharacterIdSkillz ($id, $token)
    {
        return $this->request([
            "Authorization" => "Bearer ". $token,
            "Content-Type" => "application/json",
            "User-Agent" => config("services.eve.userAgent")
        ], 'get', config('services.eve.urls.esi'),"/v4/characters/{$id}/skills/", []);
    }

    public function getCharactersCharacterIdSkillqueue ($id, $token)
    {
        return $this->request([
            "Authorization" => "Bearer ". $token,
            "Content-Type" => "application/json",
            "User-Agent" => config("services.eve.userAgent")
        ], 'get', config('services.eve.urls.esi'),"/v2/characters/{$id}/skillqueue/", []);
    }

    public function getCharactersCharacterIdAttributes ($id, $token)
    {
        return $this->request([
            "Authorization" => "Bearer ". $token,
            "Content-Type" => "application/json",
            "User-Agent" => config("services.eve.userAgent")
        ], 'get', config('services.eve.urls.esi'),"/v1/characters/{$id}/attributes/", []);
    }

    public function getCorporationsCorporationIdMembers ($id, $token)
    {
        return $this->request([
            "Authorization" => "Bearer ". $token,
            "Content-Type" => "application/json",
            "User-Agent" => config("services.eve.userAgent")
        ], 'get', config('services.eve.urls.esi'),"/v2/corporations/{$id}/members/", []);
    }

    public function getAlliancesAllianceIdCorporations ($id)
    {
        return $this->request([
            "Content-Type" => "application/json",
            "User-Agent" => config("services.eve.userAgent")
        ], 'get', config('services.eve.urls.esi'),"/v1/alliances/{$id}/corporations/", []);
    }

    public function getMarketStatJson ($params){
        return $this->request([
            "User-Agent" => config("services.eve.userAgent"),
            "Content-Type" => "application/json"
        ], 'get', config('base.marketerUrl'),'/marketstat/json', $params);
    }

    public function postUIOpenwindowMarketDetails ($id, $token)
    {
        return $this->request([
            "Authorization" => "Bearer ". $token,
            "Content-Type" => "application/json",
            "User-Agent" => config("services.eve.userAgent")
        ], 'post', config('services.eve.urls.esi'),"/v1/ui/openwindow/marketdetails/", [
            'type_id' => $id
        ]);
    }

    public function getUniversePlanetsPlanetId ($id)
    {
        return $this->request([
            "Content-Type" => "application/json",
            "User-Agent" => config("services.eve.userAgent")
        ], 'get', config('services.eve.urls.esi'),"/v1/universe/planets/{$id}/", []);
    }

    public function getUniverseStationsStationId ($id)
    {
        return $this->request([
            "Content-Type" => "application/json",
            "User-Agent" => config("services.eve.userAgent")
        ], 'get', config('services.eve.urls.esi'),"/v2/universe/stations/{$id}/", []);
    }

    public function getUniverseStructuresStructureId ($id, $token)
    {
        return $this->request([
            "Authorization" => "Bearer ". $token,
            "Content-Type" => "application/json",
            "User-Agent" => config("services.eve.userAgent")
        ], 'get', config('services.eve.urls.esi'),"/v2/universe/structures/{$id}/", []);
    }

    public function getUniverseSystemsSystemId ($id)
    {
        return $this->request([
            "Content-Type" => "application/json",
            "User-Agent" => config("services.eve.userAgent")
        ], 'get', config('services.eve.urls.esi'),"/v4/universe/systems/{$id}/", []);
    }

    public function getUniverseTypesTypeId ($id)
    {
        return $this->request([
            "Content-Type" => "application/json",
            "User-Agent" => config("services.eve.userAgent")
        ], 'get', config('services.eve.urls.esi'),"/v3/universe/types/{$id}/", []);
    }

    public function getDogmaAttributesAttributeId ($id)
    {
        return $this->request([
            "Content-Type" => "application/json",
            "User-Agent" => config("services.eve.userAgent")
        ], 'get', config('services.eve.urls.esi'),"/v1/dogma/attributes/{$id}/", []);
    }

    public function getDogmaEffectsEffectId ($id)
    {
        return $this->request([
            "Content-Type" => "application/json",
            "User-Agent" => config("services.eve.userAgent")
        ], 'get', config('services.eve.urls.esi'),"/v2/dogma/effects/{$id}/", []);
    }

    public function getUniverseGroupsGroupId ($id)
    {
        return $this->request([
            "Content-Type" => "application/json",
            "User-Agent" => config("services.eve.userAgent")
        ], 'get', config('services.eve.urls.esi'),"/v1/universe/groups/{$id}/", []);
    }

    public function getSearch ($search, $category, $strict=false)
    {
        return $this->request([
            "Content-Type" => "application/json",
            "User-Agent" => config('services.eve.userAgent')
        ], 'get', config('services.eve.urls.esi'),"/v2/search/", [
            'search' => $search,
            'categories' => $category,
            'strict' => $strict
        ]);
    }

    public function postUniverseNames ($ids)
    {
        return $this->request([
            "Content-Type" => "application/json",
            "Accept" => "application/json",
            "User-Agent" => config("services.eve.userAgent")
        ], 'post', config('services.eve.urls.esi'),"/v2/universe/names/", json_encode($ids));
    }

    public function postUniverseIds ($names)
    {
        return $this->request([
            "Content-Type" => "application/json",
            "Accept" => "application/json",
            "User-Agent" => config("services.eve.userAgent")
        ], 'post', config('services.eve.urls.esi'),"/v1/universe/ids/", json_encode($names));
    }

    public function getChrAncestries()
    {
        return $this->request([
            "Content-Type" => "application/json",
            "User-Agent" => config("services.eve.userAgent")
        ], 'get', config('services.eve.urls.sde'),"/chrAncestries.json", []);
    }
    public function getChrBloodlines()
    {
        return $this->request([
            "Content-Type" => "application/json",
            "User-Agent" => config("services.eve.userAgent")
        ], 'get', config('services.eve.urls.sde'),"/chrBloodlines.json", []);
    }

    public function getChrFactions()
    {
        return $this->request([
            "Content-Type" => "application/json",
            "User-Agent" => config("services.eve.userAgent")
        ], 'get', config('services.eve.urls.sde'),"/chrFactions.json", []);
    }

    public function getChrRaces()
    {
        return $this->request([
            "Content-Type" => "application/json",
            "User-Agent" => config("services.eve.userAgent")
        ], 'get', config('services.eve.urls.sde'),"/chrRaces.json", []);
    }
    public function getInvCategories()
    {
        return $this->request([
            "Content-Type" => "application/json",
            "User-Agent" => config("services.eve.userAgent")
        ], 'get', config('services.eve.urls.sde'),"/invCategories.json", []);
    }
    public function getInvGroups()
    {
        return $this->request([
            "Content-Type" => "application/json",
            "User-Agent" => config("services.eve.userAgent")
        ], 'get', config('services.eve.urls.sde'),"/invGroups.json", []);
    }

    public function getMapConstellations()
    {
        return $this->request([
            "Content-Type" => "application/json",
            "User-Agent" => config("services.eve.userAgent")
        ], 'get', config('services.eve.urls.sde'),"/mapConstellations.json", []);
    }

    public function getMapRegions()
    {
        return $this->request([
            "Content-Type" => "application/json",
            "User-Agent" => config("services.eve.userAgent")
        ], 'get', config('services.eve.urls.sde'),"/mapRegions.json", []);
    }
}
