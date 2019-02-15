<?php

namespace App\Http\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use App\Organisation;

class Controller extends BaseController
{
    /**
     * Sets database configuration
     *
     * @param Request $request
     * @return string
     */
    public function setDatabaseConfig(Request $request, $orgId = null)
    {
        $organisation = null;
        if ($orgId instanceof Organisation) {
            $organisation = $orgId;
        } else {
            if ($orgId === null) {
                $orgId = $request->user()->org_id;
            }
            $organisation = Organisation::find($orgId);
        }
        if ($organisation === null) {
            return;
        }
        $database = $organisation->name.'_'.$organisation->id;

        \Illuminate\Support\Facades\Config::set('database.connections.'.$database, array(
            'driver'    => 'mongodb',
            'host'      => '127.0.0.1',
            'database'  => $database,
            'username'  => '',
            'password'  => '',
        ));
        return $database;
    }
}
