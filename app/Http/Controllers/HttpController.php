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
                    'request' => collect($curl->requestHeaders)->toArray(),
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

    public function oauthVerifyAuthCode (Collection $payload)
    {
        return $this->request([
            "Authorization" => "Basic ".base64_encode($payload->get('cid').":".$payload->get('cs')),
            "Content-Type" => "application/x-www-form-urlencoded",
            "Host" => "login.eveonline.com",
            "User-Agent" => config('base.userAgent')
        ], 'post', config('services.eve.urls.sso'),"/oauth/token", [
            'grant_type' => "authorization_code",
            'code' => $payload->get('c')
        ]);
    }

    public function oauthVerifyAccessToken (string $token)
    {
        return $this->request([
            "Authorization" => "Bearer ".$token,
            "Content-Type" => "application/json",
            "User-Agent" => config('base.userAgent')
        ], 'get', config('services.eve.urls.esi'),"/verify/", []);
    }

    public function postRefreshToken (Collection $payload)
    {
        return $this->request([
            "Authorization" => "Basic " . base64_encode($payload->get("cid").":".$payload->get("cs")),
            "Content-Type" => "application/json",
            "Host" => "login.eveonline.com",
            "User-Agent" => config("base.userAgent")
        ], 'post', config("services.eve.urls.sso"),"/oauth/token", json_encode([
            "grant_type" => "refresh_token",
            "refresh_token" => $payload->get("rt")
        ]));
    }

    public function postRevokeToken (Collection $payload)
    {
        return $this->request([
            "Authorization" => "Basic " . base64_encode($payload->get("cid").":".$payload->get("cs")),
            "Content-Type" => "application/json",
            "Host" => "login.eveonline.com",
            "User-Agent" => config("base.userAgent")
        ], 'post', config("services.eve.urls.sso"),"/oauth/revoke", json_encode([
            "token_type_hint" => $payload->get("t"),
            "token" => $payload->get("rt")
        ]));
    }

    public function getCharactersCharacterId ($id)
    {
        return $this->request([
            "Content-Type" => "application/json",
            "User-Agent" => config('base.userAgent')
        ], 'get', config('services.eve.urls.esi'), "/v4/characters/{$id}/", []);
    }

    public function getCorporationsCorporationId ($id)
    {
        return $this->request([
            "Content-Type" => "application/json",
            "User-Agent" => config('base.userAgent')
        ], 'get', config('services.eve.urls.esi'),"/v4/corporations/{$id}/", []);
    }

    public function getAlliancesAllianceId ($id)
    {
        return $this->request([
            "Content-Type" => "application/json",
            "User-Agent" => config('base.userAgent')
        ], 'get', config('services.eve.urls.esi'),"/v3/alliances/{$id}/", []);
    }

    public function getStatus ()
    {
        return $this->request([
            "Content-Type" => "application/json",
            "User-Agent" => config('base.userAgent')
        ], 'get', config('services.eve.urls.esi'),"/v1/status/", []);
    }

    public function getCharactersCharacterIdBookmarks($id, $token)
    {
        return $this->request([
            "Authorization" => "Bearer ". $token,
            "Content-Type" => "application/json",
            "User-Agent" => config('base.userAgent')
        ], 'get', config('services.eve.urls.esi'),"/v2/characters/{$id}/bookmarks/", []);
    }

    public function getCharactersCharacterIdBookmarksFolders($id, $token)
    {
        return $this->request([
            "Authorization" => "Bearer ". $token,
            "Content-Type" => "application/json",
            "User-Agent" => config('base.userAgent')
        ], 'get', config('services.eve.urls.esi'),"/v2/characters/{$id}/bookmarks/folders/", []);
    }

    public function getCharactersCharacterIdClones($id, $token)
    {
        return $this->request([
            "Authorization" => "Bearer ". $token,
            "Content-Type" => "application/json",
            "User-Agent" => config('base.userAgent')
        ], 'get', config('services.eve.urls.esi'),"/v3/characters/{$id}/clones/", []);
    }

    public function getCharactersCharacterIdCorporationHistory ($id)
    {
        return $this->request([
            "Content-Type" => "application/json",
            "User-Agent" => config('base.userAgent')
        ], 'get', config('services.eve.urls.esi'),"/v1/characters/{$id}/corporationhistory/", []);
    }

    public function getCharactersCharacterIdFittings ($id, $token)
    {
        return $this->request([
            "Authorization" => "Bearer ". $token,
            "Content-Type" => "application/json",
            "User-Agent" => config('base.userAgent')
        ], 'get', config('services.eve.urls.esi'),"/v1/characters/{$id}/fittings/", []);
    }

    public function postCharactersCharacterIdFittings ($id, $token, $payload)
    {
        return $this->request([
            "Authorization" => "Bearer ". $token,
            "Content-Type" => "application/json",
            "User-Agent" => config('base.userAgent')
        ], 'post', config('services.eve.urls.esi'),"/v1/characters/{$id}/fittings/", $payload);
    }

    public function deleteCharactersCharacterIdFittings ($id, $token, $fitting_id)
    {
        return $this->request([
            "Authorization" => "Bearer ". $token,
            "Content-Type" => "application/json",
            "User-Agent" => config('base.userAgent')
        ], 'delete', config('services.eve.urls.esi'),"/v1/characters/{$id}/fittings/{$fitting_id}/", []);
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
            "User-Agent" => config('base.userAgent')
        ], 'get', config('services.eve.urls.esi'),"/v1/characters/{$id}/mail/", $params);
    }

    public function getCharactersCharacterIdMailLists ($id, $token)
    {
        return $this->request([
            "Authorization" => "Bearer ". $token,
            "Content-Type" => "application/json",
            "User-Agent" => config('base.userAgent')
        ], 'get', config('services.eve.urls.esi'),"/v1/characters/{$id}/mail/lists/", []);
    }

    public function getCharactersCharacterIDMailLabels ($id, $token)
    {
        return $this->request([
            "Authorization" => "Bearer ". $token,
            "Content-Type" => "application/json",
            "User-Agent" => config('base.userAgent')
        ], 'get', config('services.eve.urls.esi'),"/v3/characters/{$id}/mail/labels/", []);
    }

    public function getCharactersCharacterIdMailMailId ($id, $token, $mailId)
    {
        return $this->request([
            "Authorization" => "Bearer ". $token,
            "Content-Type" => "application/json",
            "User-Agent" => config('base.userAgent')
        ], 'get', config('services.eve.urls.esi'),"/v1/characters/{$id}/mail/{$mailId}/", []);
    }

    public function postCharactersCharacterIdMailMail($id, $token, $payload)
    {
        return $this->request([
            "Authorization" => "Bearer ". $token,
            "Content-Type" => "application/json",
            "User-Agent" => config('base.userAgent')
        ], 'post', config('services.eve.urls.esi'),"/v1/characters/{$id}/mail/", json_encode($payload));
    }

    public function putCharactersCharacterIdMailMailId(int $id, string $token, int $mail_id, array $payload)
    {
        return $this->request([
            "Authorization" => "Bearer ". $token,
            "Content-Type" => "application/json",
            "User-Agent" => config('base.userAgent')
        ], 'put', config('services.eve.urls.esi'),"/v1/characters/{$id}/mail/{$mail_id}/", json_encode($payload));
    }

    public function deleteCharactersCharacterIdMailMailId(int $id, string $token, int $mail_id)
    {
        return $this->request([
            "Authorization" => "Bearer ". $token,
            "Content-Type" => "application/json",
            "User-Agent" => config('base.userAgent')
        ], 'delete', config('services.eve.urls.esi'),"/v1/characters/{$id}/mail/{$mail_id}/", []);
    }

    public function getCharactersCharacterIdContacts ($id, $token)
    {
        return $this->request([
            "Authorization" => "Bearer ". $token,
            "Content-Type" => "application/json",
            "User-Agent" => config('base.userAgent')
        ], 'get', config('services.eve.urls.esi'),"/v1/characters/{$id}/contacts/", []);
    }

    public function getCharactersCharacterIdWallet ($id, $token)
    {
        return $this->request([
            "Authorization" => "Bearer ". $token,
            "Content-Type" => "application/json",
            "User-Agent" => config('base.userAgent')
        ], 'get', config('services.eve.urls.esi'),"/v1/characters/{$id}/wallet/", []);
    }

    public function getCharactersCharacterIdWalletJournal ($id, $token)
    {
        return $this->request([
            "Authorization" => "Bearer ". $token,
            "Content-Type" => "application/json",
            "User-Agent" => config('base.userAgent')
        ], 'get', config('services.eve.urls.esi'),"/v3/characters/{$id}/wallet/journal/", []);
    }

    public function getCharactersCharacterIdWalletTransactions ($id, $token)
    {
        return $this->request([
            "Authorization" => "Bearer ". $token,
            "Content-Type" => "application/json",
            "User-Agent" => config('base.userAgent')
        ], 'get', config('services.eve.urls.esi'),"/v1/characters/{$id}/wallet/transactions/", []);
    }

    public function getCharactersCharacterIdContracts ($id, $token)
    {
        return $this->request([
            "Authorization" => "Bearer ". $token,
            "Content-Type" => "application/json",
            "User-Agent" => config('base.userAgent')
        ], 'get', config('services.eve.urls.esi'),"/v1/characters/{$id}/contracts/", []);
    }

    public function getCharactersCharacterIdContractsContractIdItems ($id, $token, $contractId)
    {
        return $this->request([
            "Authorization" => "Bearer ". $token,
            "Content-Type" => "application/json",
            "User-Agent" => config('base.userAgent')
        ], 'get', config('services.eve.urls.esi'),"/v1/characters/{$id}/contracts/$contractId/items/", []);
    }

    public function getCharactersCharacterIdLocation ($id, $token)
    {
        return $this->request([
            "Authorization" => "Bearer ". $token,
            "Content-Type" => "application/json",
            "User-Agent" => config('base.userAgent')
        ], 'get', config('services.eve.urls.esi'),"/v1/characters/{$id}/location/", []);
    }

    public function getCharactersCharacterIdRoles ($id, $token)
    {
        return $this->request([
            "Authorization" => "Bearer ". $token,
            "Content-Type" => "application/json",
            "User-Agent" => config('base.userAgent')
        ], 'get', config('services.eve.urls.esi'),"/v1/characters/{$id}/roles/", []);
    }

    public function getCharactersCharacterIdShip ($id, $token)
    {
        return $this->request([
            "Authorization" => "Bearer ". $token,
            "Content-Type" => "application/json",
            "User-Agent" => config('base.userAgent')
        ], 'get', config('services.eve.urls.esi'),"/v1/characters/{$id}/ship/", []);
    }

    public function getCharactersCharacterIdSkillz ($id, $token)
    {
        return $this->request([
            "Authorization" => "Bearer ". $token,
            "Content-Type" => "application/json",
            "User-Agent" => config('base.userAgent')
        ], 'get', config('services.eve.urls.esi'),"/v4/characters/{$id}/skills/", []);
    }

    public function getCharactersCharacterIdSkillqueue ($id, $token)
    {
        return $this->request([
            "Authorization" => "Bearer ". $token,
            "Content-Type" => "application/json",
            "User-Agent" => config('base.userAgent')
        ], 'get', config('services.eve.urls.esi'),"/v2/characters/{$id}/skillqueue/", []);
    }

    public function getCharactersCharacterIdAttributes ($id, $token)
    {
        return $this->request([
            "Authorization" => "Bearer ". $token,
            "Content-Type" => "application/json",
            "User-Agent" => config('base.userAgent')
        ], 'get', config('services.eve.urls.esi'),"/v1/characters/{$id}/attributes/", []);
    }

    public function getCorporationsCorporationIdMembers ($id, $token)
    {
        return $this->request([
            "Authorization" => "Bearer ". $token,
            "Content-Type" => "application/json",
            "User-Agent" => config('base.userAgent')
        ], 'get', config('services.eve.urls.esi'),"/v2/corporations/{$id}/members/", []);
    }

    public function getAlliancesAllianceIdCorporations ($id)
    {
        return $this->request([
            "Content-Type" => "application/json",
            "User-Agent" => config('base.userAgent')
        ], 'get', config('services.eve.urls.esi'),"/v1/alliances/{$id}/corporations/", []);
    }

    public function getMarketStatJson ($params){
        return $this->request([
            "User-Agent" => config('base.userAgent'),
            "Content-Type" => "application/json"
        ], 'get', config('base.marketerUrl'),'/marketstat/json', $params);
    }

    public function postUIOpenwindowContract ($id, $token)
    {
        return $this->request([
            "Authorization" => "Bearer ". $token,
            "Content-Type" => "application/json",
            "User-Agent" => config('base.userAgent')
        ], 'post', config('services.eve.urls.esi'),"/v1/ui/openwindow/contract/", [
            'contract_id' => $id,
        ]);
    }

    public function postUIOpenwindowMarketDetails ($id, $token)
    {
        return $this->request([
            "Authorization" => "Bearer ". $token,
            "Content-Type" => "application/json",
            "User-Agent" => config('base.userAgent')
        ], 'post', config('services.eve.urls.esi'),"/v1/ui/openwindow/marketdetails/", [
            'type_id' => $id
        ]);
    }

    public function getUniversePlanetsPlanetId ($id)
    {
        return $this->request([
            "Content-Type" => "application/json",
            "User-Agent" => config('base.userAgent')
        ], 'get', config('services.eve.urls.esi'),"/v1/universe/planets/{$id}/", []);
    }

    public function getUniverseStationsStationId ($id)
    {
        return $this->request([
            "Content-Type" => "application/json",
            "User-Agent" => config('base.userAgent')
        ], 'get', config('services.eve.urls.esi'),"/v2/universe/stations/{$id}/", []);
    }

    public function getUniverseStructuresStructureId ($id, $token)
    {
        return $this->request([
            "Authorization" => "Bearer ". $token,
            "Content-Type" => "application/json",
            "User-Agent" => config('base.userAgent')
        ], 'get', config('services.eve.urls.esi'),"/v1/universe/structures/{$id}/", []);
    }

    public function getUniverseSystemsSystemId ($id)
    {
        return $this->request([
            "Content-Type" => "application/json",
            "User-Agent" => config('base.userAgent')
        ], 'get', config('services.eve.urls.esi'),"/v2/universe/systems/{$id}/", []);
    }

    public function getUniverseTypesTypeId ($id)
    {
        return $this->request([
            "Content-Type" => "application/json",
            "User-Agent" => config('base.userAgent')
        ], 'get', config('services.eve.urls.esi'),"/v3/universe/types/{$id}/", []);
    }

    public function postUniverseNames ($ids)
    {
        return $this->request([
            "Content-Type" => "application/json",
            "Accept" => "application/json",
            "User-Agent" => config('base.userAgent')
        ], 'post', config('services.eve.urls.esi'),"/v2/universe/names/", json_encode($ids));
    }

    public function postUniverseIds ($names)
    {
        return $this->request([
            "Content-Type" => "application/json",
            "Accept" => "application/json",
            "User-Agent" => config('base.userAgent')
        ], 'post', config('services.eve.urls.esi'),"/v1/universe/ids/", json_encode($names));
    }

    public function getChrAncestries()
    {
        return $this->request([
            "Content-Type" => "application/json",
            "User-Agent" => config('base.userAgent')
        ], 'get', config('services.eve.urls.sde'),"/chrAncestries.json", []);
    }
    public function getChrBloodlines()
    {
        return $this->request([
            "Content-Type" => "application/json",
            "User-Agent" => config('base.userAgent')
        ], 'get', config('services.eve.urls.sde'),"/chrBloodlines.json", []);
    }
    public function getChrRaces()
    {
        return $this->request([
            "Content-Type" => "application/json",
            "User-Agent" => config('base.userAgent')
        ], 'get', config('services.eve.urls.sde'),"/chrRaces.json", []);
    }
    public function getInvCategories()
    {
        return $this->request([
            "Content-Type" => "application/json",
            "User-Agent" => config('base.userAgent')
        ], 'get', config('services.eve.urls.sde'),"/invCategories.json", []);
    }
    public function getInvGroups()
    {
        return $this->request([
            "Content-Type" => "application/json",
            "User-Agent" => config('base.userAgent')
        ], 'get', config('services.eve.urls.sde'),"/invGroups.json", []);
    }

    public function getMapConstellations()
    {
        return $this->request([
            "Content-Type" => "application/json",
            "User-Agent" => config('base.userAgent')
        ], 'get', config('services.eve.urls.sde'),"/mapConstellations.json", []);
    }

    public function getMapRegions()
    {
        return $this->request([
            "Content-Type" => "application/json",
            "User-Agent" => config('base.userAgent')
        ], 'get', config('services.eve.urls.sde'),"/mapRegions.json", []);
    }
}
