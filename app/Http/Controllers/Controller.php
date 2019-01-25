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
    public function setDatabaseConfig(Request $request)
    {
        $user = $request->user();
        $organisation = Organisation::find($user->org_id);
        $database = strtolower($organisation->name).'_'.$user->org_id;

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
