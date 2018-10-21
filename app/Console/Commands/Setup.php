<?php

namespace ESIK\Console\Commands;

use Carbon, Storage;
use Illuminate\Console\Command;
use ESIK\Http\Controllers\DataController;
use ESIK\Models\SDE\{Ancestry, Bloodline, Category, Constellation, Faction, Group, Race, Region};
use ESIK\Models\ESI\Type;

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
         $start = Carbon::now();
         $this->info("Starting SDE Import");
         foreach (config('services.eve.sde.import') as $type) {
             $this->{$type}();
             sleep(1);
         }
         $end = Carbon::now();
         $diff = $end->timestamp - $start->timestamp;
         $this->info("SDE Import Completed Successfully");
         $this->info("SDE Import Start: $start - End: $end Duration: $diff seconds");

         if ($this->confirm('Delete SDE Cache?')) {
             $this->info("Deleting SDE Cache");
             $files = Storage::disk('local')->files();
             $files = collect($files)->filter(function ($file) {
                 return $file !== ".gitignore";
             });
             Storage::disk('local')->delete($files->toArray());
             $this->info($files->count() . " files deleted successfully");
         }
         return true;
     }

     public function ancestries ()
     {
         $file = "chrAncestries.json";
         $exists = Storage::disk('local')->exists($file);
         if (!$exists) {
             $this->dataCont->downloadSDE($file, storage_path("app/$file"));
         }
         $data = collect(json_decode(Storage::get($file)))->recursive();
         $bar = $this->output->createProgressBar($data->count());
         $bar->setFormat('Importing Ancestries: %current% of %max% %percent%%');
         $bar->start();
         $data->each(function ($ancestry) use ($bar) {
             $import = Ancestry::firstOrNew(['id' => $ancestry->get('ancestryID')])->fill([
                 'name' => $ancestry->get('ancestryName'),
                 'bloodline_id' => $ancestry->get('bloodlineID')
             ]);
             $import->save();
             $bar->advance();
             usleep(1000);
         });
         $bar->finish();
         print "\n";
         return true;
     }

     public function bloodlines ()
     {
         $file = "chrBloodlines.json";
         $exists = Storage::disk('local')->exists($file);
         if (!$exists) {
             $this->dataCont->downloadSDE($file, storage_path("app/$file"));
         }
         $data = collect(json_decode(Storage::get($file)))->recursive();
         $bar = $this->output->createProgressBar($data->count());
         $bar->setFormat('Importing Bloodlines: %current% of %max% %percent%%');
         $bar->start();
         $data->each(function ($bloodline) use ($bar) {
             $import = Bloodline::firstOrNew(['id' => $bloodline->get('bloodlineID')])->fill([
                 'name' => $bloodline->get('bloodlineName'),
                 'race_id' => $bloodline->get('raceID')
             ]);
             $import->save();
             $bar->advance();
             usleep(1000);
         });
         $bar->finish();
         print "\n";
         return true;
     }

     public function categories ()
     {
         $file = "invCategories.json";
         $exists = Storage::disk('local')->exists($file);
         if (!$exists) {
             $this->dataCont->downloadSDE($file, storage_path("app/$file"));
         }
         $data = collect(json_decode(Storage::get($file)))->recursive();
         $bar = $this->output->createProgressBar($data->count());
         $bar->setFormat('Importing Categories: %current% of %max% %percent%%');
         $bar->start();
         $data->each(function ($group) use ($bar) {
             $import = Category::firstOrNew(['id' => $group->get('categoryID')])->fill([
                 'name' => $group->get('categoryName'),
                 'published' => $group->get('published')
             ]);
             $import->save();
             $bar->advance();
             usleep(1000);
         });
         $bar->finish();
         print "\n";
         return true;
     }

     public function constellations ()
     {
         $file = "mapConstellations.json";
         $exists = Storage::disk('local')->exists($file);
         if (!$exists) {
             $this->dataCont->downloadSDE($file, storage_path("app/$file"));
         }
         $data = collect(json_decode(Storage::get($file)))->recursive();
         $bar = $this->output->createProgressBar($data->count());
         $bar->setFormat('Importing Constellations: %current% of %max% %percent%%');
         $bar->start();
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
         $bar->finish();
         print "\n";
         return true;
     }

     public function factions ()
     {
         $file = "chrFactions.json";
         $exists = Storage::disk('local')->exists($file);
         if (!$exists) {
             $this->dataCont->downloadSDE($file, storage_path("app/$file"));
         }
         $data = collect(json_decode(Storage::get($file)))->recursive();
         $bar = $this->output->createProgressBar($data->count());
         $bar->setFormat('Importing Factions: %current% of %max% %percent%%');
         $bar->start();
         $data->each(function ($faction) use ($bar) {
             $import = Faction::firstOrNew(['id' => $faction->get('factionID')])->fill([
                 'name' => $faction->get('factionName'),
                 'race_id' => $faction->get('raceID')
             ]);
             $import->save();
             $bar->advance();
             usleep(1000);
         });
         $bar->finish();
         print "\n";
         return true;
     }

     public function groups ()
     {
         $file = "invGroups.json";
         $exists = Storage::disk('local')->exists($file);
         if (!$exists) {
             $this->dataCont->downloadSDE($file, storage_path("app/$file"));
         }
         $data = collect(json_decode(Storage::get($file)))->recursive();
         $bar = $this->output->createProgressBar($data->count());
         $bar->setFormat('Importing Groups: %current% of %max% %percent%%');
         $bar->start();
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
         $bar->finish();
         print "\n";
         return true;
     }

     public function modules ()
     {
         $groups = Group::where('category_id', 7)->get()->pluck('id');
         $files = ["invTypes.json", "dgmTypeAttributes.json"];
         foreach($files as $file) {
             $exists = Storage::disk('local')->exists($file);
             if (!$exists) {
                 $this->dataCont->downloadSDE($file, storage_path("app/$file"));
             }
         }
         $data = collect(json_decode(Storage::get("invTypes.json")))->recursive()->whereIn('groupID', $groups->toArray());
         $attributes = collect(json_decode(Storage::get("dgmTypeAttributes.json")))->recursive();
         $bar = $this->output->createProgressBar($data->count());
         $bar->setFormat('Importing Modules: %current% of %max% %percent%%');
         $bar->start();
         $data->each(function ($module)  use ($attributes, $bar) {
             Type::firstOrNew([
                 'id' => $module->get('typeID')
             ], [
                 'name' => $module->get('typeName'),
                 'published' => $module->get('published'),
                 'group_id' => $module->get('groupID'),
                 'volume' => $module->get('volume')
             ])->save();
             $bar->advance();
             usleep(1000);
         });
         $bar->finish();
         print "\n";
         return true;
     }

     public function races ()
     {
         $file = "chrRaces.json";
         $exists = Storage::disk('local')->exists($file);
         if (!$exists) {
             $this->dataCont->downloadSDE($file, storage_path("app/$file"));
         }
         $data = collect(json_decode(Storage::get($file)))->recursive();
         $bar = $this->output->createProgressBar($data->count());
         $bar->setFormat('Importing Races: %current% of %max% %percent%%');
         $bar->start();
         $data->each(function ($race) use ($bar) {
             $import = Race::firstOrNew(['id' => $race->get('raceID')])->fill([
                 'name' => $race->get('raceName')
             ]);
             $import->save();
             $bar->advance();
             usleep(1000);
         });
         $bar->finish();
         print "\n";
         return true;
     }

     public function regions ()
     {
         $file = "mapRegions.json";
         $exists = Storage::disk('local')->exists($file);
         if (!$exists) {
             $this->dataCont->downloadSDE($file, storage_path("app/$file"));
         }
         $data = collect(json_decode(Storage::get($file)))->recursive();
         $bar = $this->output->createProgressBar($data->count());
         $bar->setFormat('Importing Regions: %current% of %max% %percent%%');
         $bar->start();
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
         $bar->finish();
         print "\n";
         return true;
     }

     public function ships ()
     {
         $groups = Group::where('category_id', 6)->get()->pluck('id');
         $files = ["invTypes.json", "dgmTypeAttributes.json"];
         foreach($files as $file) {
             $exists = Storage::disk('local')->exists($file);
             if (!$exists) {
                 $this->dataCont->downloadSDE($file, storage_path("app/$file"));
             }
         }
         $data = collect(json_decode(Storage::get("invTypes.json")))->recursive()->whereIn('groupID', $groups->toArray());
         $attributes = collect(json_decode(Storage::get("dgmTypeAttributes.json")))->recursive();
         $bar = $this->output->createProgressBar($data->count());
         $bar->setFormat('Importing Ships: %current% of %max% %percent%%');
         $bar->start();
         $data->each(function ($ship)  use ($attributes, $bar) {
             Type::firstOrNew([
                 'id' => $ship->get('typeID')
             ], [
                 'name' => $ship->get('typeName'),
                 'published' => $ship->get('published'),
                 'group_id' => $ship->get('groupID'),
                 'volume' => $ship->get('volume')
             ])->save();
             $bar->advance();
             usleep(1000);
         });
         $bar->finish();
         print "\n";
         return true;
     }

     public function skillz ()
     {
         $groups = Group::where('category_id', 16)->get()->pluck('id');
         $files = ["invTypes.json", "dgmTypeAttributes.json"];
         foreach($files as $file) {
             $exists = Storage::disk('local')->exists($file);
             if (!$exists) {
                 $this->dataCont->downloadSDE($file, storage_path("app/$file"));
             }
         }
         $data = collect(json_decode(Storage::get("invTypes.json")))->recursive()->whereIn('groupID', $groups->toArray());
         $attributes = collect(json_decode(Storage::get("dgmTypeAttributes.json")))->recursive();
         $bar = $this->output->createProgressBar($data->count());
         $bar->setFormat('Importing Skillz: %current% of %max% %percent%%');
         $bar->start();
         $data->each(function ($skills)  use ($attributes, $bar) {
             Type::firstOrNew([
                 'id' => $skills->get('typeID')
             ], [
                 'name' => $skills->get('typeName'),
                 'published' => $skills->get('published'),
                 'group_id' => $skills->get('groupID'),
                 'volume' => $skills->get('volume')
             ])->save();
             $bar->advance();
             usleep(1000);
         });
         $bar->finish();
         print "\n";
         return true;
     }

}
