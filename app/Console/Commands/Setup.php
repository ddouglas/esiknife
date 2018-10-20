<?php

namespace ESIK\Console\Commands;

use Illuminate\Console\Command;
use ESIK\Http\Controllers\DataController;
use ESIK\Models\SDE\{Ancestry, Bloodline, Category, Constellation, Faction, Group, Race, Region};

class Setup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'setup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Loads the Database with static data from the SDE and ESI';

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
        $this->info("Starting SDE Import");

        foreach (config('services.eve.sde.import') as $type) {
            $this->{$type}();
        }
        $this->info("SDE Import Completed Successfully");

        $groups = Group::whereIn('category_id', [6,7,16])->where('published', 1)->get();
        $bar = $this->output->createProgressBar($groups->count());
        $types = collect();
        $groups->each(function ($group) use (&$types, &$bar) {
            $groupRequest = $this->dataCont->getGroup($group->id);
            $status = $groupRequest->status;
            $payload = $groupRequest->payload;
            if (!$status) {
                return true;
            }
            $bar->advance();
            $types = $types->merge(collect($payload->response->types));
        });
        $count = $types->count();
        $now = now(); $x = 1;
        $bar = $this->output->createProgressBar($count);
        $types->each(function ($type) use ($count, &$now, &$x, $bar) {
            $getType = $this->dataCont->getType($type);
            $status = $getType->status;
            $payload = $getType->payload;
            if (!$status) {
                $this->error($payload->message);
                $bar->advance();
            }
            $bar->advance();
            if ($x%20==0) {
                sleep(1);
            }
            $x++;
        });
    }

    public function chrAncestries()
    {
        $getAncestries = collect($this->dataCont->getChrAncestries())->recursive();
        $status = $getAncestries->get('status');
        $payload = $getAncestries->get('payload');
        if (!$status) {
            $this->alert(__FUNCTION__. "entcountered an error while requesting data. Error: ". $payload->get('message'));
            activity(__FUNCTION__)->withProperties($payload->toArray())->log($payload->get('message'));
            return $status;
        }
        $data =collect($payload->get('response'))->recursive();
        $this->info("Importing Data for ". __FUNCTION__);
        $bar = $this->output->createProgressBar($data->count());
        $data->each(function ($ancestry) use ($bar) {
            $import = Ancestry::firstOrNew(['id' => $ancestry->get('ancestryID')])->fill([
                'name' => $ancestry->get('ancestryName'),
                'bloodline_id' => $ancestry->get('bloodlineID')
            ]);
            $import->save();
            $bar->advance();

            usleep(1000);
        });
        print "\n";
        return $status;
    }

    public function chrBloodlines()
    {
        $getBloodlines = collect($this->dataCont->getChrBloodlines())->recursive();
        $status = $getBloodlines->get('status');
        $payload = $getBloodlines->get('payload');
        if (!$status) {
            $this->alert(__FUNCTION__. "entcountered an error while requesting data. Error: ". $payload->get('message'));
            activity(__FUNCTION__)->withProperties($payload->toArray())->log($payload->get('message'));
            return $status;
        }
        $data = collect($payload->get('response'));
        $this->info("Importing Data for ". __FUNCTION__);
        $bar = $this->output->createProgressBar($data->count());
        $data->each(function ($bloodline) use ($bar) {
            $import = Bloodline::firstOrNew(['id' => $bloodline->get('bloodlineID')])->fill([
                'name' => $bloodline->get('bloodlineName'),
                'race_id' => $bloodline->get('raceID')
            ]);
            $import->save();
            $bar->advance();
            usleep(1000);
        });
        print "\n";
        return $status;
    }

    public function chrFactions()
    {
        $getFactions = collect($this->dataCont->getChrFactions())->recursive();
        $status = $getFactions->get('status');
        $payload = $getFactions->get('payload');
        if (!$status) {
            if ($payload->code >= 400 && $payload->code < 500) {
                activity(__FUNCTION__)->withProperties($payload)->log($payload->message);
            }
            return $status;
        }
        $data = collect($payload->get('response'));
        $this->info("Importing Data for ". __FUNCTION__);
        $bar = $this->output->createProgressBar($data->count());
        collect($payload->get('response'))->recursive()->each(function ($faction) use ($bar) {
            $import = Faction::firstOrNew(['id' => $faction->get('factionID')])->fill([
                'name' => $faction->get('factionName'),
                'race_id' => $faction->get('raceID')
            ]);
            $import->save();
            $bar->advance();
            usleep(1000);
        });
        print "\n";
        return $status;
    }

    public function chrRaces()
    {
        $getRaces = collect($this->dataCont->getChrRaces())->recursive();
        $status = $getRaces->get('status');
        $payload = $getRaces->get('payload');
        if (!$status) {
            $this->alert(__FUNCTION__. "entcountered an error while requesting data. Error: ". $payload->get('message'));
            activity(__FUNCTION__)->withProperties($payload->toArray())->log($payload->get('message'));
            return $status;
        }
        $data = collect($payload->get('response'));
        $this->info("Importing Data for ". __FUNCTION__);
        $bar = $this->output->createProgressBar($data->count());
        $data->each(function ($race) use ($bar) {
            $import = Race::firstOrNew(['id' => $race->get('raceID')])->fill([
                'name' => $race->get('raceName')
            ]);
            $import->save();
            $bar->advance();
            usleep(1000);
        });
        print "\n";
        return $status;
    }

    public function invGroups()
    {
        $getInvGroups = collect($this->dataCont->getInvGroups())->recursive();
        $status = $getInvGroups->get('status');
        $payload = $getInvGroups->get('payload');
        if (!$status) {
            $this->alert(__FUNCTION__. "entcountered an error while requesting data. Error: ". $payload->get('message'));
            activity(__FUNCTION__)->withProperties($payload->toArray())->log($payload->get('message'));
            return $status;
        }
        $data = collect($payload->get('response'));
        $this->info("Importing Data for ". __FUNCTION__);
        $bar = $this->output->createProgressBar($data->count());
        $data->each(function ($group) use ($bar) {
            $import = Group::firstOrNew(['id' => $group->get('groupID')])->fill([
                'name' => $group->get('groupName'),
                'published' => $group->get('published'),
                'category_id' => $group->get('categoryID')
            ]);
            $import->save();
            $bar->advance();
            usleep(1000);
        });
        print "\n";
        return $status;
    }

    public function invCategories()
    {
        $getInvCategories = collect($this->dataCont->getInvCategories())->recursive();
        $status = $getInvCategories->get('status');
        $payload = $getInvCategories->get('payload');
        if (!$status) {
            $this->alert(__FUNCTION__. "entcountered an error while requesting data. Error: ". $payload->get('message'));
            activity(__FUNCTION__)->withProperties($payload->toArray())->log($payload->get('message'));
            return $status;
        }
        $data = collect($payload->get('response'));
        $this->info("Importing Data for ". __FUNCTION__);
        $bar = $this->output->createProgressBar($data->count());
        $data->each(function ($group) use ($bar) {
            $import = Category::firstOrNew(['id' => $group->get('categoryID')])->fill([
                'name' => $group->get('categoryName'),
                'published' => $group->get('published')
            ]);
            $import->save();
            $bar->advance();
            usleep(1000);
        });
        print "\n";
        return $status;
    }

    public function mapRegions()
    {
        $getMapRegions = collect($this->dataCont->getMapRegions())->recursive();
        $status = $getMapRegions->get('status');
        $payload = $getMapRegions->get('payload');
        if (!$status) {
            $this->alert(__FUNCTION__. "entcountered an error while requesting data. Error: ". $payload->get('message'));
            activity(__FUNCTION__)->withProperties($payload->toArray())->log($payload->get('message'));
            return false;
        }
        $data = collect($payload->get('response'));
        $this->info("Importing Data for ". __FUNCTION__);
        $bar = $this->output->createProgressBar($data->count());
        $data->each(function ($group) use ($bar) {
            $import = Region::firstOrNew(['id' => $group->get('regionID')])->fill([
                'name' => $group->get('regionName'),
                'pos_x' => $group->get('x'),
                'pos_y' => $group->get('y'),
                'pos_z' => $group->get('z')
            ]);
            $import->save();
            $bar->advance();
            usleep(1000);
        });
        print "\n";
        return $status;
    }

    public function mapConstellations()
    {
        $getMapConstellations = collect($this->dataCont->getMapConstellations())->recursive();
        $status = $getMapConstellations->get('status');
        $payload = $getMapConstellations->get('payload');
        if (!$status) {
            $this->alert(__FUNCTION__. "entcountered an error while requesting data. Error: ". $payload->get('message'));
            activity(__FUNCTION__)->withProperties($payload->toArray())->log($payload->get('message'));
            return $status;
        }
        $data = collect($payload->get('response'));
        $this->info("Importing Data for ". __FUNCTION__);
        $bar = $this->output->createProgressBar($data->count());
        $data->each(function ($group) use ($bar) {
            $import = Constellation::firstOrNew(['id' => $group->get('constellationID')])->fill([
                'name' => $group->get('constellationName'),
                'pos_x' => $group->get('x'),
                'pos_y' => $group->get('y'),
                'pos_z' => $group->get('z'),
                'region_id' => $group->get('regionID')
            ]);
            $import->save();
            $bar->advance();

            usleep(1000);
        });
        print "\n";
        return $status;
    }
}
