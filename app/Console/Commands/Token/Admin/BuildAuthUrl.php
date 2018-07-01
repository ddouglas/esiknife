<?php

namespace ESIK\Console\Commands\Token\Admin;

use Illuminate\Console\Command;

use ESIK\Http\Controllers\DataController;

class BuildAuthUrl extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'token:buildAdminAuthUrl';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generates the SSO Url that will be used to authenticate the Admin Character that will be used to pull in Wallet Data and whatever else needs to be imported.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->dataCont = new DataController;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->info("Admin Authentication URL Constructor.\n\nFollow the prompts and this should go smoothly");
        $authorization = base64_encode(config("services.eve.sso.admin.id").":".config("services.eve.sso.admin.secret"));

        while (true) {
            $urlType = $this->choice("Build Url for Character, Corporation, or Both?", ['character', 'corporation', 'both']);

            $this->info("Copy the Following Url into an web browser. Login via the SSO and authorize the scopes. You will be redirected to a blank screen, possibly an error screen from your browser. This is fine. Reference here when you done.");

            if ($urlType === "character") {
                $scopeString = "esi-wallet.read_character_wallet.v1";
            }
            if ($urlType === "corporation") {
                $scopeString = "esi-wallet.read_corporation_wallets.v1";
            }
            if ($urlType === "both") {
                $scopeString = "esi-wallet.read_character_wallet.v1 esi-wallet.read_corporation_wallets.v1";
            }

            $this->info(config("services.eve.urls.sso")."/oauth/authorize?response_type=code&redirect_uri=" . config('services.eve.sso.admin.callback') . "&client_id=".config('services.eve.sso.admin.id')."&scope={$scopeString}");

            $authorized = $this->choice('Have you authorized the scope(s)?', ['yes', 'no']);
            if ($authorized === "yes") {
                break;
            }
        }

        $code = $this->ask("Look at the address bar in your browser. There is a query parameter called code in the url. Copy and paste the value of the code parameter into the console:");
        $authorization = base64_encode(config("services.eve.sso.admin.id").":".config("services.eve.sso.admin.secret"));

        $verifyAuthCode = $this->dataCont->verifyAuthCode($code, $authorization);
        if (!$verifyAuthCode->status) {
            $this->error($verifyAuthCode->payload->message);
            return false;
        }
        $response = collect($verifyAuthCode->payload->response);

        $verifyAccessToken = $this->dataCont->verifyAccessToken($response->get('access_token'));
        if (!$verifyAccessToken->status) {
            $this->error($verifyAuthCode->payload->message);
            return false;
        }
        $response = $response->merge(collect($verifyAccessToken->payload->response));

        if ($response->get("Scopes") !== $scopeString) {
            $this->error("Scopes requested and scopes authorized don't match. Please try again");
            return false;
        }


        $this->info("Successfully Authorized Character ". $response->get('CharacterName'). " (" . $response->get("CharacterID") . ")");
        $this->info("Copy the Following Information into your .env file\n");
        $this->info("EVESSO_ADMIN_CHARACTER_ID={$response->get("CharacterID")}");
        $this->info("EVESSO_ADMIN_CHARACTER_REFRESH_TOKEN={$response->get("refresh_token")}");
        return true;
    }
}
