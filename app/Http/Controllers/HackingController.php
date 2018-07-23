<?php

namespace ESIK\Http\Controllers;

use Auth, Bus, Carbon, DB, Request, Session;
use ESIK\Models\{Member};
use ESIK\Jobs\ESI\GetCharacter;
use ESIK\Models\ESI\{Character, Corporation, System};

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
        
    }

    public function typesWithAttributes ($type_id)
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
        return response(200);
    }
}
