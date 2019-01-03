<?php
use Illuminate\Database\Seeder;
use App\User;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $users_data = array();
        $users_data[]=array(
            'name' => 'Supresh',
            'email' => 'supresh_colaco@persistent.com',
            'password' =>  bcrypt('123456')
            );
        $users_data[]=array(
            'name' => 'Peter',
            'email' => 'peter_braganza@persistent.com',
            'password' =>  bcrypt('123456')
        );
       
       foreach($users_data as $user){
         User::create($user);
       }
       


    }
}
