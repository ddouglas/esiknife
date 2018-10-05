<?php

namespace ESIK\Http\Controllers;

use Auth, Bus, Carbon, DB, Request, Redis, Session;
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
        $token = "eyJhbGciOiJSUzI1NiIsImtpZCI6IkpXVC1TaWduYXR1cmUtS2V5IiwidHlwIjoiSldUIn0.eyJzY3AiOlsiZXNpLWFzc2V0cy5yZWFkX2Fzc2V0cy52MSIsImVzaS1ib29rbWFya3MucmVhZF9jaGFyYWN0ZXJfYm9va21hcmtzLnYxIiwiZXNpLWNoYXJhY3RlcnMucmVhZF9jb250YWN0cy52MSIsImVzaS1jbG9uZXMucmVhZF9jbG9uZXMudjEiLCJlc2ktY2xvbmVzLnJlYWRfaW1wbGFudHMudjEiLCJlc2ktY29udHJhY3RzLnJlYWRfY2hhcmFjdGVyX2NvbnRyYWN0cy52MSIsImVzaS1sb2NhdGlvbi5yZWFkX2xvY2F0aW9uLnYxIiwiZXNpLWxvY2F0aW9uLnJlYWRfc2hpcF90eXBlLnYxIiwiZXNpLW1haWwucmVhZF9tYWlsLnYxIiwiZXNpLXNraWxscy5yZWFkX3NraWxscXVldWUudjEiLCJlc2ktc2tpbGxzLnJlYWRfc2tpbGxzLnYxIiwiZXNpLXVuaXZlcnNlLnJlYWRfc3RydWN0dXJlcy52MSIsImVzaS13YWxsZXQucmVhZF9jaGFyYWN0ZXJfd2FsbGV0LnYxIl0sImp0aSI6Ijk3YTY4OTgzLTRmN2QtNDIzYS04MDAzLWQyYzkzMmYzM2RlZSIsImtpZCI6IkpXVC1TaWduYXR1cmUtS2V5Iiwic3ViIjoiQ0hBUkFDVEVSOkVWRTo5NDk0MTk3NyIsImF6cCI6IjI3YTBkMzE1MDE5YzRkMTViZjkwOWFiZWZlNjcyODJiIiwibmFtZSI6IlNsaWZhIEJhZGRhbm9sZCIsIm93bmVyIjoiZkdWRVNpaGJ3VTU5RDl6OWs1NFpuRmdnUmUwPSIsImV4cCI6MTUzODczMDgyNSwiaXNzIjoibG9naW4uZXZlb25saW5lLmNvbSJ9.EVAFdq6z767g-Qp2hhay8bTFr9HL09-524tIIjFlbB6fuECVAw7pXkbtqTVLW74_gpmpp3xxgFufhIQ0-afHsObw4BcEOfZYHZ_mjZsHztO61nKRK2b230T4cussrXzx3LIkEr6k8mv5VQ9vw6ty0CYGsjyakQ2kc0odAELl-kFfmTpOPdEtVEeph7stAJfKHKkA6iIuvt-u8ky9wFZXTvS6Osy2cWqLSpNFUemXEUsJSVfPXkecfwep2BcJZV4kV_TBpLIGBcCJw-9pDdUkQl6iBPveIG6S52SBaaDPsS-hBh7yycALNr8K-S9OmUN2Jr9pUoobigt0E-6DodOlxg";

        $tokenExplode = explode('.', $token);

        $headerPayload = $tokenExplode[0].'.'.$tokenExplode[1];

        $hash = "nehPQ7FQ1YK-leKyIg-aACZaT-DbTL5V1XpXghtLX_bEC-fwxhdE_4yQKDF6cA-V4c-5kh8wMZbfYw5xxgM9DynhMkVrmQFyYB3QMZwydr922UWs3kLz-nO6vi0ldCn-ffM9odUPRHv9UbhM5bB4SZtCrpr9hWQgJ3FjzWO2KosGQ8acLxLtDQfU_lq0OGzoj_oWwUKaN_OVfu80zGTH7mxVeGMJqWXABKd52ByvYZn3wL_hG60DfDWGV_xfLlHMt_WoKZmrXT4V3BCBmbitJ6lda3oNdNeHUh486iqaL43bMR2K4TzrspGMRUYXcudUQ9TycBQBrUlT85NRY9TeOw";

        $hmacsha256 = hash_hmac('sha256', $headerPayload, $hash);

        dd($tokenExplode, $hmacsha256, base64_encode($hmacsha256), $tokenExplode[2]);

        dd($tokenExplode, $token);

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
