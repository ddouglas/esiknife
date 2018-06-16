<?php

namespace ESIK\Http\Controllers;

use Bus,Request, Session;
use ESIK\Models\{Member};
use ESIK\Jobs\ESI\GetCharacter;
use ESIK\Models\ESI\{MailRecipient};

use Illuminate\Events\Dispatcher;


class HackingController extends Controller
{
    public function __construct()
    {
        $this->httpCont = new HttpController;
        $this->dataCont = new DataController;
        $this->ssoCont = new SSOController;
    }

    public function index()
    {
        // $this->cleanup();
        
    }

    public function cleanup()
    {
        \ESIK\Models\Member::whereNotNull('id')->delete();
        \ESIK\Models\ESI\Contract::whereNotNull('id')->delete();
        \ESIK\Models\ESI\MailHeader::whereNotNull('id')->delete();
        \ESIK\Models\ESI\Character::whereNotNull('id')->delete();
        \ESIK\Models\ESI\Corporation::whereNotNull('id')->delete();
        \ESIK\Models\ESI\Alliance::whereNotNull('id')->delete();
    }

    public function typesWithAttributesEffects ($type_id)
    {
        $request = $this->httpCont->getUniverseTypesTypeId($type_id);
        $status = $request->status;
        $payload = $request->payload;
        $response = $payload->response;
        if (!$status) {
            dd($payload->message, __METHOD__.":".__LINE__);
        }
        if (property_exists($response, 'dogma_attributes')) {
            $attributes = collect($response->dogma_attributes)->recursive()->keyBy('attribute_id');
            $attributes->each(function ($attribute) use ($attributes) {
                $request = $this->httpCont->getDogmaAttributesAttributeId($attribute->get('attribute_id'));
                $status = $request->status;
                $payload = $request->payload;
                $response = $payload->response;
                if (!$status) {
                    dd($payload->message, __METHOD__.":".__LINE__);
                }
                $attributes->get($attribute->get('attribute_id'))->put('name', $response->name);
                $attributes->get($attribute->get('attribute_id'))->put('display_name', $response->display_name);
                usleep(10000);
            });
            dump($attributes);
        }
        if (property_exists($response, 'dogma_effects')) {
            $effects = collect($response->dogma_effects)->recursive()->keyBy('effect_id');
            $effects->each(function ($effect) use ($effects) {
                $request = $this->httpCont->getDogmaEffectsEffectId($effect->get('effect_id'));
                $status = $request->status;
                $payload = $request->payload;
                $response = $payload->response;
                if (!$status) {
                    dd($payload->message, __METHOD__.":".__LINE__);
                }
                $effects->get($effect->get('effect_id'))->put('name', $response->name);
                $effects->get($effect->get('effect_id'))->put('display_name', $response->display_name);
                usleep(10000);
            });
            dump($effects);
        }
        return response(200);
    }
}
