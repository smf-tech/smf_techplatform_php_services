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
        if ($orgId === null) {
            $orgId = $request->user()->org_id;
        }
        $organisation = Organisation::find($orgId);
        $database = $organisation->name.'_'.$orgId;

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
