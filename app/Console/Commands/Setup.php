<?php

namespace ESIK\Console\Commands;

use DB, Carbon, Storage;
use Illuminate\Console\Command;
use ESIK\Http\Controllers\DataController;
use ESIK\Models\SDE\{Ancestry, Bloodline, Category, Constellation, Faction, Group, Race, Region};
use ESIK\Models\ESI\{Type, TypeDogmaAttribute};

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
         $this->info("SDE Import Start: $start - End: $end  - Duration: $diff seconds");

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
         $insert = collect();
         DB::table('ancestries')->delete();
         foreach ($data as $ancestry) {
             $insert->push([
                 'id' => $ancestry->get('ancestryID'),
                 'name' => $ancestry->get('ancestryName'),
                 'bloodline_id' => $ancestry->get('bloodlineID'),
                 'created_at' => now(),
                 'updated_at' => now()
             ]);
         }
         DB::table('ancestries')->insert($insert->toArray());
         usleep(1000);
         $bar->finish();
         print "\n";
         return true;
     }

     public function attributes ()
     {
         $file = "dgmTypeAttributes.json";
         $exists = Storage::disk('local')->exists($file);
         if (!$exists) {
             $this->dataCont->downloadSDE($file, storage_path("app/$file"));
         }
         $groups = Group::select('id')->whereIn('category_id', [6,7,16])->get()->pluck('id')->toArray();
         $typeIDs = Type::select('id')->whereIn('group_id', $groups)->get()->pluck('id')->toArray();
         $data = collect(json_decode(Storage::get($file)))->recursive()->whereIn('typeID', $typeIDs);
         $bar = $this->output->createProgressBar($data->count());
         $bar->setFormat('Importing Dogma Type Attributes: %current% of %max% %percent%%');
         $bar->start();
         foreach ($data->chunk(250) as $chunk) {
             $insert = collect();
             foreach ($chunk as $attribute) {
                 $value = null;
                 if (!is_null($attribute->get('valueInt'))) {
                     $value = $attribute->get('valueInt');
                 } else if (!is_null($attribute->get('valueFloat'))) {
                     $value = $attribute->get('valueFloat');
                 }
                 $insert->push([
                     'type_id' => $attribute->get('typeID'),
                     'attribute_id' => $attribute->get('attributeID'),
                     'value' => $value
                 ]);
             }
             DB::table('type_dogma_attributes')->insert($insert->toArray());
             $bar->advance(250);
             usleep(1000);
         }
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
         $insert = collect();
         DB::table('bloodlines')->delete();
         foreach ($data as $bloodline) {
             $insert->push([
                 'id' => $bloodline->get('bloodlineID'),
                 'name' => $bloodline->get('bloodlineName'),
                 'race_id' => $bloodline->get('raceID'),
                 'created_at' => now(),
                 'updated_at' => now()
             ]);
         }
         DB::table('bloodlines')->insert($insert->toArray());
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
         $insert = collect();
         DB::table('categories')->delete();
         foreach ($data as $category) {
             $insert->push([
                 'id' => $category->get('categoryID'),
                 'name' => $category->get('categoryName'),
                 'published' => $category->get('published'),
                 'created_at' => now(),
                 'updated_at' => now()
             ]);
         }
         DB::table('categories')->insert($insert->toArray());
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
         $insert = collect();
         DB::table('constellations')->delete();
         foreach ($data as $constellation) {
             $insert->push([
                 'id' => $constellation->get('constellationID'),
                 'name' => $constellation->get('constellationName'),
                 'pos_x' => $constellation->get('x'),
                 'pos_y' => $constellation->get('y'),
                 'pos_z' => $constellation->get('z'),
                 'region_id' => $constellation->get('regionID'),
                 'created_at' => now(),
                 'updated_at' => now()
             ]);
         }
         DB::table('constellations')->insert($insert->toArray());
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
         $insert = collect();
         DB::table('factions')->delete();
         foreach ($data as $faction) {
             $insert->push([
                 'id' => $faction->get('factionID'),
                 'name' => $faction->get('factionName'),
                 'race_id' => $faction->get('raceID'),
                 'created_at' => now(),
                 'updated_at' => now()
             ]);
         }
         DB::table('factions')->insert($insert->toArray());
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
         DB::table('groups')->delete();
         foreach ($data->chunk(100) as $chunk) {
             $insert = collect();
             foreach ($chunk as $group) {
                 $insert->push([
                     'id' => $group->get('groupID'),
                     'name' => $group->get('groupName'),
                     'published' => $group->get('published'),
                     'category_id' => $group->get('categoryID'),
                     'created_at' => now(),
                     'updated_at' => now()
                ]);
             }
             DB::table('groups')->insert($insert->toArray());
             $bar->advance(100);
             usleep(1000);
         }
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
         $insert = collect();
         DB::table('races')->delete();
         foreach ($data as $race) {
             $insert->push([
                 'id' => $race->get('raceID'),
                 'name' => $race->get('raceName'),
                 'created_at' => now(),
                 'updated_at' => now()
             ]);
         }
         DB::table('races')->insert($insert->toArray());
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
         $insert = collect();
         DB::table('regions')->delete();
         foreach ($data as $group) {
             $insert->push([
                 'id' => $group->get('regionID'),
                 'name' => $group->get('regionName'),
                 'pos_x' => $group->get('x'),
                 'pos_y' => $group->get('y'),
                 'pos_z' => $group->get('z'),
                 'created_at' => now(),
                 'updated_at' => now()
             ]);
         }
         DB::table('regions')->insert($insert->toArray());
         $bar->finish();
         print "\n";
         return true;
     }

     public function types ()
     {
         $groups = Group::whereIn('category_id', [6,7,16])->get()->pluck('id');
         $file = "invTypes.json";
         $exists = Storage::disk('local')->exists($file);
         if (!$exists) {
             $this->dataCont->downloadSDE($file, storage_path("app/$file"));
         }
         $data = collect(json_decode(Storage::get("invTypes.json")))->recursive()->whereIn('groupID', $groups->toArray());
         $bar = $this->output->createProgressBar($data->count());
         $bar->setFormat('Importing Types: %current% of %max% %percent%%');
         $bar->start();
         $insert = collect();
         DB::table('types')->whereIn('group_id', $groups->toArray())->delete();
         foreach ($data->chunk(250) as $chunk) {
             $insert = collect();
             foreach ($chunk as $types) {
                 $insert->push([
                     'id' => $types->get('typeID'),
                     'name' => $types->get('typeName'),
                     'published' => $types->get('published'),
                     'group_id' => $types->get('groupID'),
                     'volume' => $types->get('volume'),
                     'created_at' => now(),
                     'updated_at' => now()
                 ]);
             }
             DB::table('types')->insert($insert->toArray());
             $bar->advance(250);
             usleep(1000);
         }
         $bar->finish();
         print "\n";
         return true;
     }

}
