<?php

namespace ESIK\Http\Controllers;

use ESIK\Models\SDE\{Ancestry, Bloodline, Race, Group, Category, Region, Constellation};

class SdeController extends Controller
{
    public static function chrAncestries()
        {
            $dataCont = new DataController();
            dump(__FUNCTION__ . " is being requested");
            $getAncestries = $dataCont->getChrAncestries();
            $status = $getAncestries->status;
            $payload = $getAncestries->payload;

            if (!$status) {
                if ($payload->code >= 400 && $payload->code < 500) {
                    activity(__FUNCTION__)->withProperties($payload)->log($payload->message);
                }
                return $status;
            }
            dump(__FUNCTION__ . " requested successfully");
            dump(__FUNCTION__ . " is being imported");
            collect($payload->response)->recursive()->each(function ($ancestry) {
                $import = Ancestry::firstOrNew(['id' => $ancestry->get('ancestryID')])->fill([
                    'name' => $ancestry->get('ancestryName'),
                    'description' => $ancestry->get('description'),
                    'bloodline_id' => $ancestry->get('bloodlineID')
                ]);
                $import->save();
                usleep(500);
            });
            dump(__FUNCTION__ . " imported successfully");
            return $status;
        }

        public static function chrBloodlines()
        {
            $dataCont = new DataController();
            dump(__FUNCTION__ . " is being requested");
            $getBloodlines = $dataCont->getChrBloodlines();
            $status = $getBloodlines->status;
            $payload = $getBloodlines->payload;
            if (!$status) {
                if ($payload->code >= 400 && $payload->code < 500) {
                    activity(__FUNCTION__)->withProperties($payload)->log($payload->message);
                }
                return $status;
            }
            dump(__FUNCTION__ . " requested successfully");
            dump(__FUNCTION__ . " is being imported");
            collect($payload->response)->recursive()->each(function ($bloodline) {
                $import = Bloodline::firstOrNew(['id' => $bloodline->get('bloodlineID')])->fill([
                    'name' => $bloodline->get('bloodlineName'),
                    'description' => $bloodline->get('description'),
                    'race_id' => $bloodline->get('raceID')
                ]);
                $import->save();
                usleep(500);
            });
            dump(__FUNCTION__ . " imported successfully");
            return $status;
        }

        public static function chrRaces()
        {
            $dataCont = new DataController();
            dump(__FUNCTION__ . " is being requested");
            $getRaces = $dataCont->getChrRaces();
            $status = $getRaces->status;
            $payload = $getRaces->payload;
            if (!$status) {
                if ($payload->code >= 400 && $payload->code < 500) {
                    activity(__FUNCTION__)->withProperties($payload)->log($payload->message);
                }
                return $status;
            }
            dump(__FUNCTION__ . " requested successfully");
            dump(__FUNCTION__ . " is being imported");
            collect($payload->response)->recursive()->each(function ($race) {
                $import = Race::firstOrNew(['id' => $race->get('raceID')])->fill([
                    'name' => $race->get('raceName'),
                    'description' => $race->get('description')
                ]);
                $import->save();
                usleep(500);
            });
            dump(__FUNCTION__ . " imported successfully");
            return $status;
        }

        public static function invGroups()
        {
            $dataCont = new DataController();
            dump(__FUNCTION__ . " is being requested");
            $getInvGroups = $dataCont->getInvGroups();
            $status = $getInvGroups->status;
            $payload = $getInvGroups->payload;
            if (!$status) {
                if ($payload->code >= 400 && $payload->code < 500) {
                    activity(__FUNCTION__)->withProperties($payload)->log($payload->message);
                }
                return $status;
            }
            dump(__FUNCTION__ . " requested successfully");
            dump(__FUNCTION__ . " is being imported");
            collect($payload->response)->recursive()->each(function ($group) {
                $import = Group::firstOrNew(['id' => $group->get('groupID')])->fill([
                    'name' => $group->get('groupName'),
                    'published' => $group->get('published'),
                    'category_id' => $group->get('categoryID')
                ]);
                $import->save();
                usleep(500);
            });
            dump(__FUNCTION__ . " imported successfully");
            return $status;
        }

        public static function invCategories()
        {
            $dataCont = new DataController();
            dump(__FUNCTION__ . " is being requested");
            $getInvCategories = $dataCont->getInvCategories();
            $status = $getInvCategories->status;
            $payload = $getInvCategories->payload;
            if (!$status) {
                if ($payload->code >= 400 && $payload->code < 500) {
                    activity(__FUNCTION__)->withProperties($payload)->log($payload->message);
                }
                return $status;
            }
            dump(__FUNCTION__ . " requested successfully");
            dump(__FUNCTION__ . " is being imported");
            collect($payload->response)->recursive()->each(function ($group) {
                $import = Category::firstOrNew(['id' => $group->get('categoryID')])->fill([
                    'name' => $group->get('categoryName'),
                    'published' => $group->get('published')
                ]);
                $import->save();
                usleep(500);
            });
            dump(__FUNCTION__ . " imported successfully");
            return $status;
        }

        public static function mapRegions()
        {
            $dataCont = new DataController();
            dump(__FUNCTION__ . " is being requested");
            $getMapRegions = $dataCont->getMapRegions();
            $status = $getMapRegions->status;
            $payload = $getMapRegions->payload;
            if (!$status) {
                if ($payload->code >= 400 && $payload->code < 500) {
                    activity(__FUNCTION__)->withProperties($payload)->log($payload->message);
                }
                return false;
            }
            dump(__FUNCTION__ . " requested successfully");
            dump(__FUNCTION__ . " is being imported");
            collect($payload->response)->recursive()->each(function ($group) {
                $import = Region::firstOrNew(['id' => $group->get('regionID')])->fill([
                    'name' => $group->get('regionName'),
                    'pos_x' => $group->get('x'),
                    'pos_y' => $group->get('y'),
                    'pos_z' => $group->get('z')
                ]);
                $import->save();
                usleep(500);
            });
            dump(__FUNCTION__ . " imported successfully");
            return $status;
        }

        public static function mapConstellations()
        {
            $dataCont = new DataController();
            dump(__FUNCTION__ . " is being requested");
            $getMapConstellations = $dataCont->getMapConstellations();
            $status = $getMapConstellations->status;
            $payload = $getMapConstellations->payload;
            if (!$status) {
                if ($payload->code >= 400 && $payload->code < 500) {
                    activity(__FUNCTION__)->withProperties($payload)->log($payload->message);
                }
                return $status;
            }
            dump(__FUNCTION__ . " requested successfully");
            dump(__FUNCTION__ . " is being imported");
            collect($payload->response)->recursive()->each(function ($group) {
                $import = Constellation::firstOrNew(['id' => $group->get('constellationID')])->fill([
                    'name' => $group->get('constellationName'),
                    'pos_x' => $group->get('x'),
                    'pos_y' => $group->get('y'),
                    'pos_z' => $group->get('z'),
                    'region_id' => $group->get('regionID')
                ]);
                $import->save();
                usleep(500);
            });
            dump(__FUNCTION__ . " imported successfully");
            return $status;
        }
}
